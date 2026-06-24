<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\UpdateCartPaymentMethodRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Order;
use App\Support\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function confirm(): Response|RedirectResponse
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        // If PayPal is the selected payment method, pass the client ID
        $selectedPayment = $cart['selected_payment_method'] ?? [];
        $paypalClientId = null;

        if (($selectedPayment['id'] ?? '') === 'paypal') {
            $mode = config('paypal.mode', 'sandbox');
            $paypalClientId = $mode === 'live'
                ? config('paypal.live.client_id')
                : config('paypal.sandbox.client_id');
        }

        return Inertia::render('Checkout/Confirm', [
            'cart' => $cart,
            'paypal_client_id' => $paypalClientId,
        ]);
    }

    public function updatePayment(UpdateCartPaymentMethodRequest $request): RedirectResponse
    {
        $this->cartService->setPaymentMethod($request->string('payment_method')->toString());

        return back();
    }

    public function submit(PlaceOrderRequest $request): mixed
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        $shippingAmount = (float) ($cart['shipping_total'] ?? 0);

        $order = Order::query()->create([
            'customer_id' => $request->user()->id,
            'status' => 'pending',
            'total_amount' => $cart['total'],
            'shipping_amount' => $shippingAmount,
        ]);

        foreach ($cart['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
            ]);
        }

        $lineItems = collect($cart['items'])->map(fn (array $item): array => [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => ['name' => $item['name']],
                'unit_amount' => (int) round((float) $item['unit_price'] * 100),
            ],
            'quantity' => $item['quantity'],
        ])->all();

        if ($shippingAmount > 0) {
            $shippingLabel = $cart['selected_shipping_method']['label'] ?? 'Versand';
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $shippingLabel],
                    'unit_amount' => (int) round($shippingAmount * 100),
                ],
                'quantity' => 1,
            ];
        }

        $stripe = new StripeClient(config('services.stripe.key'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $request->user()->email,
            'metadata' => ['order_id' => $order->id],
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.index'),
        ]);

        $order->update(['stripe_checkout_session_id' => $session->id]);

        $this->cartService->clear();

        return Inertia::location($session->url);
    }

    public function success(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->query('session_id');
        $paypalOrderId = $request->query('paypal_order_id');

        $order = null;

        if ($sessionId) {
            $order = Order::query()
                ->with(['items', 'customer'])
                ->where('stripe_checkout_session_id', $sessionId)
                ->first();
        } elseif ($paypalOrderId) {
            $order = Order::query()
                ->with(['items', 'customer'])
                ->whereHas('payments', fn ($q) => $q->where('paypal_order_id', $paypalOrderId))
                ->first();
        }

        if (! $order) {
            return to_route('home');
        }

        $vatRate = (int) config('shop.cart.vat_rate', 19);
        $totalCents = (int) round((float) $order->total_amount * 100);
        $shippingCents = (int) round((float) $order->shipping_amount * 100);
        $subtotalCents = $totalCents - $shippingCents;
        $netTotalCents = (int) round($totalCents / (1 + ($vatRate / 100)));
        $vatAmountCents = $totalCents - $netTotalCents;

        $items = $order->items->map(fn ($item): array => [
            'name' => $item->product_name,
            'product_number' => $item->product_id ?? $item->id,
            'quantity' => $item->quantity,
            'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
            'line_total' => number_format((float) $item->unit_price * $item->quantity, 2, '.', ''),
        ])->all();

        return Inertia::render('Checkout/Success', [
            'order_id' => $order->id,
            'items' => $items,
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
            'shipping_total' => number_format($shippingCents / 100, 2, '.', ''),
            'vat_rate' => $vatRate,
            'vat_amount' => number_format($vatAmountCents / 100, 2, '.', ''),
            'total' => number_format($totalCents / 100, 2, '.', ''),
            'customer_email' => $order->customer->email,
        ]);
    }
}
