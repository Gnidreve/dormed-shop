<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Payment;
use App\Services\PayPalService;
use App\Support\Cart\CartService;
use App\Support\Orders\OrderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;

class PayPalController extends Controller
{
    public function __construct(
        private readonly PayPalService $payPalService,
        private readonly CartService $cartService,
        private readonly OrderManager $orderManager,
    ) {}

    /**
     * Step 1: Create a PayPal Order via REST API.
     *
     * Called from the frontend PayPal button. Creates the order in PayPal
     * and returns the Order ID so the JS SDK can launch the approval flow.
     */
    public function createOrder(PlaceOrderRequest $request): JsonResponse
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return response()->json(['error' => 'Warenkorb ist leer.'], 400);
        }

        if (! $this->cartService->isAddressComplete()) {
            return response()->json(['error' => 'Bitte vervollständigen Sie Ihre Lieferadresse.'], 422);
        }

        $order = $this->orderManager->createFromCart($request->user(), $cart, 'paypal');

        try {
            $response = $this->payPalService->createOrder(
                amount: (float) $cart['total'],
                address: $cart['shipping_address'] ?? null,
            );

            if (! isset($response['id'])) {
                Log::error('PayPal createOrder failed', ['response' => $response]);

                return response()->json([
                    'error' => 'PayPal-Order konnte nicht erstellt werden.',
                    'debug' => $response['error'] ?? 'Unbekannter Fehler',
                ], 500);
            }

            // Save a payment record immediately with CREATED status. The cart is
            // intentionally NOT cleared here — only after a successful capture —
            // so an aborted PayPal approval leaves the cart intact.
            $this->payPalService->recordPayment($order, $response, 'CREATED');

            return response()->json([
                'id' => $response['id'],
                'status' => $response['status'],
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPal createOrder exception', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            $order->update(['status' => 'failed']);

            return response()->json([
                'error' => 'PayPal-Zahlung konnte nicht initialisiert werden.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2: Capture a PayPal Order after the buyer approved it.
     *
     * Called from the frontend after PayPal JS SDK approval.
     * On success: updates order to "paid" and payment to "COMPLETED".
     */
    public function captureOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paypal_order_id' => 'required|string',
        ]);

        $paypalOrderId = $validated['paypal_order_id'];

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('paypal_order_id', $paypalOrderId)
            ->with('order')
            ->first();

        if (! $payment) {
            return response()->json(['error' => 'Zahlung nicht gefunden.'], 404);
        }

        if ($payment->status === 'COMPLETED') {
            return response()->json(['error' => 'Zahlung bereits abgeschlossen.'], 409);
        }

        try {
            $response = $this->payPalService->captureOrder($paypalOrderId);

            $captureStatus = $response['status'] ?? 'FAILED';

            if ($captureStatus === 'COMPLETED') {
                $captureId = $this->payPalService->getCaptureIdFromOrder($response);

                $payment->update([
                    'status' => 'COMPLETED',
                    'paypal_capture_id' => $captureId,
                    'paypal_payer_id' => $response['payer']['payer_id'] ?? $payment->paypal_payer_id,
                    'payer_email' => $response['payer']['email_address'] ?? $payment->payer_email,
                    'payer_name' => ($response['payer']['name']['given_name'] ?? '').' '.($response['payer']['name']['surname'] ?? ''),
                    'response_data' => $response,
                ]);

                $this->orderManager->markPaid($payment->order);
                $this->cartService->clear();
            } else {
                $payment->update([
                    'status' => 'FAILED',
                    'response_data' => $response,
                ]);

                $payment->order->update(['status' => 'failed']);
            }

            return response()->json([
                'status' => $captureStatus,
                'paypal_order_id' => $paypalOrderId,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPal captureOrder exception', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'FAILED',
                'response_data' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            $payment->order->update(['status' => 'failed']);

            return response()->json([
                'error' => 'Zahlung konnte nicht abgeschlossen werden.',
            ], 500);
        }
    }

    /**
     * Handle incoming PayPal webhook events.
     *
     * Verifies the webhook signature, then updates the payment and order status.
     */
    public function webhook(Request $request): Response
    {
        // Verify webhook authenticity
        if (! $this->payPalService->verifyWebhook($request)) {
            Log::warning('PayPal webhook verification failed', [
                'headers' => $request->headers->all(),
            ]);

            return response('Verification failed', 400);
        }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        Log::info('PayPal webhook received', [
            'event_type' => $eventType,
            'resource_id' => $resource['id'] ?? null,
        ]);

        match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $this->handleOrderApproved($resource),
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($resource),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleCaptureRefunded($resource),
            'PAYMENT.CAPTURE.DENIED' => $this->handleCaptureDenied($resource),
            default => Log::info('PayPal webhook: unhandled event type', ['event_type' => $eventType]),
        };

        return response('', 200);
    }

    /**
     * After-payment return URL (when buyer is redirected back).
     * Looks up the most recent payment for the authenticated customer's order.
     */
    public function afterPayment(Request $request): RedirectResponse|InertiaResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return to_route('checkout.index');
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('paypal_order_id', $token)
            ->with('order')
            ->first();

        if (! $payment) {
            return to_route('checkout.index');
        }

        if ($payment->status === 'COMPLETED') {
            return to_route('checkout.success');
        }

        // If still pending/created, try to capture
        try {
            $response = $this->payPalService->captureOrder($token);
            $captureStatus = $response['status'] ?? 'FAILED';

            if ($captureStatus === 'COMPLETED') {
                $captureId = $this->payPalService->getCaptureIdFromOrder($response);

                $payment->update([
                    'status' => 'COMPLETED',
                    'paypal_capture_id' => $captureId,
                    'response_data' => $response,
                ]);

                $this->orderManager->markPaid($payment->order);
                $this->cartService->clear();

                return to_route('checkout.success');
            }

            return to_route('checkout.error');
        } catch (\Throwable $e) {
            Log::error('PayPal afterPayment capture failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return to_route('checkout.error');
        }
    }

    /**
     * Handle CHECKOUT.ORDER.APPROVED webhook.
     */
    private function handleOrderApproved(array $resource): void
    {
        $orderId = $resource['id'] ?? null;

        if (! $orderId) {
            return;
        }

        Payment::query()
            ->where('paypal_order_id', $orderId)
            ->where('status', 'CREATED')
            ->update(['status' => 'APPROVED']);
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED webhook.
     */
    private function handleCaptureCompleted(array $resource): void
    {
        $captureId = $resource['id'] ?? null;

        if (! $captureId) {
            return;
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('paypal_capture_id', $captureId)
            ->orWhere('response_data->purchase_units[0]->payments->captures[0]->id', $captureId)
            ->first();

        if (! $payment) {
            // Try to find by related order in resource
            $supplementaryData = $resource['supplementary_data'] ?? [];
            $relatedIds = $supplementaryData['related_ids'] ?? [];
            $orderId = $relatedIds['order_id'] ?? null;

            if ($orderId) {
                $payment = Payment::query()
                    ->where('paypal_order_id', $orderId)
                    ->first();
            }
        }

        if ($payment) {
            $payment->update([
                'status' => 'COMPLETED',
                'paypal_capture_id' => $captureId,
                'response_data' => $resource,
            ]);
            $this->orderManager->markPaid($payment->order);
        }
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED webhook.
     */
    private function handleCaptureRefunded(array $resource): void
    {
        $captureId = $resource['id'] ?? null;

        if (! $captureId) {
            return;
        }

        Payment::query()
            ->where('paypal_capture_id', $captureId)
            ->update(['status' => 'REFUNDED']);
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED webhook.
     */
    private function handleCaptureDenied(array $resource): void
    {
        $captureId = $resource['id'] ?? null;

        if (! $captureId) {
            return;
        }

        Payment::query()
            ->where('paypal_capture_id', $captureId)
            ->update(['status' => 'FAILED']);
    }
}
