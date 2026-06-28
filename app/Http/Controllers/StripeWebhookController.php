<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Orders\OrderManager;
use App\Support\PaymentMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly OrderManager $orderManager,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = Setting::get('stripe.'.PaymentMode::current().'.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

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

        $order->update(['stripe_payment_intent_id' => $session->payment_intent]);

        $this->orderManager->markPaid($order);
    }
}
