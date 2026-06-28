<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use App\Support\Orders\OrderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Order statuses an admin may set manually.
     */
    private const STATUSES = ['pending', 'processing', 'paid', 'completed', 'cancelled', 'failed', 'refunded'];

    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => Order::with('customer')->latest()->paginate(20),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['customer', 'items', 'payments']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Update the order status. When an invoice order is marked "paid", the
     * confirmation flow runs so the customer is notified just like with a
     * gateway payment.
     */
    public function updateStatus(Request $request, Order $order, OrderManager $orderManager): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notify' => ['sometimes', 'boolean'],
        ]);

        $shouldNotify = $validated['status'] === 'paid'
            && $order->status !== 'paid'
            && ($validated['notify'] ?? false);

        if ($shouldNotify) {
            // markPaid sets the status and sends the confirmation mails once.
            $orderManager->markPaid($order);
        } else {
            $order->update(['status' => $validated['status']]);
        }

        return response()->json([
            'message' => 'Status aktualisiert.',
            'status' => $order->fresh()->status,
        ]);
    }

    /**
     * Refund the captured PayPal payment of an order.
     */
    public function refund(Order $order, PayPalService $payPalService): JsonResponse
    {
        /** @var Payment|null $payment */
        $payment = $order->payments()
            ->where('status', 'COMPLETED')
            ->whereNotNull('paypal_capture_id')
            ->latest()
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Keine erstattbare PayPal-Zahlung gefunden.'], 422);
        }

        try {
            $payPalService->refundOrder($payment->paypal_capture_id, (float) $payment->amount);

            $payment->update(['status' => 'REFUNDED']);
            $order->update(['status' => 'refunded']);

            return response()->json(['message' => 'Zahlung erstattet.', 'status' => 'refunded']);
        } catch (\Throwable $e) {
            Log::error('PayPal refund failed', [
                'order_id' => $order->id,
                'capture_id' => $payment->paypal_capture_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Erstattung fehlgeschlagen: '.$e->getMessage()], 422);
        }
    }
}
