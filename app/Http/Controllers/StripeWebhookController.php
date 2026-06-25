<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderMail;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = Setting::get('stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook payload invalid', ['error' => $e->getMessage()]);

            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
            default => null,
        };

        return response('', 200);
    }

    private function handleCheckoutSessionCompleted(Event $event): void
    {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id ?? null;

        if (! $orderId) {
            Log::warning('Stripe webhook: checkout.session.completed missing order_id in metadata');

            return;
        }

        /** @var Order|null $order */
        $order = Order::query()->with(['items', 'customer'])->find($orderId);

        if (! $order) {
            Log::warning('Stripe webhook: order not found', ['order_id' => $orderId]);

            return;
        }

        $order->update([
            'status' => 'paid',
            'stripe_payment_intent_id' => $session->payment_intent,
        ]);

        $this->sendConfirmationEmail($order);
    }

    private function sendConfirmationEmail(Order $order): void
    {
        $vatRate = (int) config('shop.cart.vat_rate', 19);
        $totalCents = (int) round((float) $order->total_amount * 100);
        $shippingCents = (int) round((float) $order->shipping_amount * 100);
        $subtotalCents = $totalCents - $shippingCents;
        $netTotalCents = (int) round($totalCents / (1 + ($vatRate / 100)));
        $vatAmountCents = $totalCents - $netTotalCents;

        $cart = [
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

        $recipients = ['l.everding@dormed.de', 'l.everding@web.de'];

        try {
            Mail::to($recipients)->send(new NewOrderMail($order, $cart, $order->customer));

            Log::channel('mail')->info('Order-Confirmation sent after Stripe payment', [
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Order-Confirmation failed after Stripe payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
