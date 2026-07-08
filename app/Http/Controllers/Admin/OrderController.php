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
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
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
     * Confirm manual receipt of payment for an invoice order (bank transfer
     * or direct debit). This is the only order-status transition an admin
     * may trigger by hand: PayPal orders are exclusively managed by the
     * capture flow and webhook (see PayPalController) and must never be
     * edited here.
     */
    public function confirmPayment(Request $request, Order $order, OrderManager $orderManager): JsonResponse
    {
        if ($order->payment_method !== 'invoice' || $order->status !== 'pending') {
            return response()->json([
                'message' => 'Zahlungseingang kann nur für offene Rechnungsbestellungen bestätigt werden.',
            ], 422);
        }

        $validated = $request->validate([
            'notify' => ['sometimes', 'boolean'],
        ]);

        if ($validated['notify'] ?? false) {
            // markPaid sets the status and sends the confirmation mails once.
            $orderManager->markPaid($order);
        } else {
            $order->update(['status' => 'paid']);
        }

        return response()->json([
            'message' => 'Zahlungseingang bestätigt.',
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
