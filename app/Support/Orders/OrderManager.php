<?php

namespace App\Support\Orders;

use App\Mail\NewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Support\PaymentMode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Central place for turning a cart into an order and for the post-payment
 * notification flow. Used by every payment gateway (invoice, PayPal, Stripe)
 * so order creation and confirmation mails behave identically everywhere.
 */
class OrderManager
{
    /**
     * Create a pending order (incl. items) from a cart snapshot.
     *
     * @param  array<string, mixed>  $cart  Result of CartService::cart()
     */
    public function createFromCart(Customer $customer, array $cart, string $paymentMethod): Order
    {
        /** @var Order $order */
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'is_test' => ! PaymentMode::isLive(),
            'total_amount' => $cart['total'],
            'shipping_amount' => (float) ($cart['shipping_total'] ?? 0),
            'shipping_address' => $cart['shipping_address'] ?? null,
            'billing_address' => $cart['billing_address'] ?? null,
        ]);

        foreach ($cart['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $order;
    }

    /**
     * Mark an order as paid and send the confirmation mails exactly once.
     *
     * Idempotent: returns false (and sends nothing) if the order is already paid,
     * so the PayPal capture, the PayPal return URL and the webhooks can all call
     * it without producing duplicate mails.
     */
    public function markPaid(Order $order): bool
    {
        if ($order->status === 'paid') {
            return false;
        }

        $order->update(['status' => 'paid']);

        $this->sendConfirmations($order);

        return true;
    }

    /**
     * Send the customer confirmation and the admin notification for an order.
     */
    public function sendConfirmations(Order $order): void
    {
        $order->loadMissing(['customer', 'items']);
        $customer = $order->customer;

        if (! $customer) {
            Log::channel('mail')->warning('Order confirmation skipped: no customer', ['order_id' => $order->id]);

            return;
        }

        try {
            Mail::to($customer->email)->send(new OrderConfirmationMail($order, $customer));
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Order confirmation mail failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            Mail::to($this->adminRecipients())->send(new NewOrderMail($order, $this->summaryFromOrder($order), $customer));
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Admin order notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the cart-shaped totals/items summary from a persisted order.
     *
     * Reused by the admin notification mail and the checkout success page so the
     * VAT/net maths lives in exactly one place.
     *
     * @return array{items: array<int, array<string, mixed>>, subtotal: string, shipping_total: string, vat_rate: int, vat_amount: string, total: string}
     */
    public function summaryFromOrder(Order $order): array
    {
        $order->loadMissing('items');

        $vatRate = (int) config('shop.cart.vat_rate', 19);
        $totalCents = (int) round((float) $order->total_amount * 100);
        $shippingCents = (int) round((float) $order->shipping_amount * 100);
        $subtotalCents = $totalCents - $shippingCents;
        $netTotalCents = (int) round($totalCents / (1 + ($vatRate / 100)));
        $vatAmountCents = $totalCents - $netTotalCents;

        return [
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'product_number' => $item->product_id ?? $item->id,
                'quantity' => $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'line_total' => number_format((float) $item->unit_price * $item->quantity, 2, '.', ''),
            ])->all(),
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
            'shipping_total' => number_format($shippingCents / 100, 2, '.', ''),
            'vat_rate' => $vatRate,
            'vat_amount' => number_format($vatAmountCents / 100, 2, '.', ''),
            'total' => number_format($totalCents / 100, 2, '.', ''),
        ];
    }

    /**
     * Resolve the admin notification recipients.
     *
     * @return array<int, string>
     */
    private function adminRecipients(): array
    {
        $configured = Setting::get('shop.notification_emails');

        if (filled($configured)) {
            return array_values(array_filter(array_map('trim', explode(',', $configured))));
        }

        return array_values(array_filter([
            Setting::get('mail.admin_address') ?? config('mail.from.address'),
        ]));
    }
}
