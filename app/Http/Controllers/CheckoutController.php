<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\UpdateCartPaymentMethodRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    private const ADDRESS_FIELDS = [
        'shipping_address.company',
        'shipping_address.salutation',
        'shipping_address.first_name',
        'shipping_address.last_name',
        'shipping_address.street',
        'shipping_address.house_number',
        'shipping_address.address_line2',
        'shipping_address.zip',
        'shipping_address.city',
        'shipping_address.country',
        'shipping_address.phone',
        'billing_same_as_shipping',
        'billing_address.company',
        'billing_address.salutation',
        'billing_address.first_name',
        'billing_address.last_name',
        'billing_address.street',
        'billing_address.house_number',
        'billing_address.address_line2',
        'billing_address.zip',
        'billing_address.city',
        'billing_address.country',
        'billing_address.phone',
    ];

    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function confirm(): Response|RedirectResponse
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        $selectedPayment = $cart['selected_payment_method'] ?? [];
        $paypalClientId = null;

        if (($selectedPayment['id'] ?? '') === 'paypal') {
            $mode = config('app.test_mode') ? 'sandbox' : (Setting::get('payment.mode') ?? 'sandbox');
            $paypalClientId = $mode === 'live'
                ? Setting::get('paypal.live.client_id')
                : Setting::get('paypal.sandbox.client_id');
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

    public function updateAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Shipping address
            'shipping_address.company' => ['nullable', 'string', 'max:255'],
            'shipping_address.salutation' => ['nullable', 'string', 'in:Herr,Frau'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.street' => ['required', 'string', 'max:255'],
            'shipping_address.house_number' => ['required', 'string', 'max:20'],
            'shipping_address.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.zip' => ['required', 'string', 'max:20'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            // Billing
            'billing_same_as_shipping' => ['boolean'],
            'billing_address.company' => ['nullable', 'string', 'max:255'],
            'billing_address.salutation' => ['nullable', 'string', 'in:Herr,Frau'],
            'billing_address.first_name' => ['nullable', 'string', 'max:255'],
            'billing_address.last_name' => ['nullable', 'string', 'max:255'],
            'billing_address.street' => ['nullable', 'string', 'max:255'],
            'billing_address.house_number' => ['nullable', 'string', 'max:20'],
            'billing_address.address_line2' => ['nullable', 'string', 'max:255'],
            'billing_address.zip' => ['nullable', 'string', 'max:20'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Save shipping address
        $this->cartService->setShippingAddress($validated['shipping_address']);

        // Save billing address (or null = same as shipping)
        if (($validated['billing_same_as_shipping'] ?? true) === true) {
            $this->cartService->setBillingAddress(null);
        } else {
            $this->cartService->setBillingAddress($validated['billing_address'] ?? []);
        }

        return back();
    }

    public function submit(PlaceOrderRequest $request): mixed
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        $shippingAmount = (float) ($cart['shipping_total'] ?? 0);
        $shippingAddress = $this->cartService->getShippingAddress();
        $billingAddress = $this->cartService->getBillingAddress();

        $order = Order::query()->create([
            'customer_id' => $request->user()->id,
            'status' => 'pending',
            'is_test' => config('app.test_mode', false),
            'total_amount' => $cart['total'],
            'shipping_amount' => $shippingAmount,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
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

        $mode = config('app.test_mode') ? 'sandbox' : (Setting::get('payment.mode') ?? 'sandbox');
        $stripeKey = $mode === 'live'
            ? Setting::get('stripe.live.secret_key')
            : Setting::get('stripe.sandbox.secret_key');
        $stripe = new StripeClient($stripeKey ?? config('services.stripe.key'));

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
            'shipping_address' => $order->shipping_address,
            'billing_address' => $order->billing_address,
        ]);
    }
}
