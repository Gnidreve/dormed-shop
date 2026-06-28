<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\UpdateCartPaymentMethodRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Mail\NewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Cart\CartService;
use App\Support\PaymentMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    public function confirm(Request $request): Response|RedirectResponse
    {
        $this->prefillAddressFromProfile($request);

        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        $selectedPayment = $cart['selected_payment_method'] ?? [];
        $paypalClientId = null;

        if (($selectedPayment['id'] ?? '') === 'paypal') {
            $paypalClientId = PaymentMode::isLive()
                ? Setting::get('paypal.live.client_id')
                : Setting::get('paypal.sandbox.client_id');
        }

        return Inertia::render('Checkout/Confirm', [
            'cart' => $cart,
            'paypal_client_id' => $paypalClientId,
        ]);
    }

    private function prefillAddressFromProfile(Request $request): void
    {
        if (! $request->user() || $this->cartService->isAddressComplete()) {
            return;
        }

        $customer = $request->user();

        $shipping = $customer->addresses()
            ->whereIn('type', ['shipping', 'both'])
            ->where('is_default', true)
            ->first();

        if ($shipping) {
            $this->cartService->setShippingAddress($shipping->toAddressArray());
        }

        $billing = $customer->addresses()
            ->where('type', 'billing')
            ->where('is_default', true)
            ->first();

        if ($billing) {
            $this->cartService->setBillingAddress($billing->toAddressArray());
        }
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

        $paymentMethodId = $cart['selected_payment_method']['id'] ?? '';

        if ($paymentMethodId === 'invoice') {
            return $this->submitInvoice($request, $cart);
        }

        return $this->submitStripe($request, $cart);
    }

    private function submitInvoice(PlaceOrderRequest $request, array $cart): RedirectResponse
    {
        $order = $this->createOrder($request, $cart, 'invoice');

        $customer = $request->user();

        try {
            Mail::to($customer->email)->send(new OrderConfirmationMail($order, $customer));
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Order confirmation mail failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify admin
        try {
            $adminRecipients = array_filter([
                Setting::get('mail.admin_address') ?? config('mail.from.address'),
            ]);
            Mail::to($adminRecipients)->send(new NewOrderMail($order, $cart, $customer));
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Admin order notification failed after invoice order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->cartService->clear();

        return to_route('checkout.success', ['order_id' => $order->id]);
    }

    private function submitStripe(PlaceOrderRequest $request, array $cart): mixed
    {
        $order = $this->createOrder($request, $cart, 'stripe');

        $shippingAmount = (float) ($cart['shipping_total'] ?? 0);

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

        $stripeKey = PaymentMode::isLive()
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

    private function createOrder(PlaceOrderRequest $request, array $cart, string $paymentMethod): Order
    {
        $order = Order::query()->create([
            'customer_id' => $request->user()->id,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'is_test' => ! PaymentMode::isLive(),
            'total_amount' => $cart['total'],
            'shipping_amount' => (float) ($cart['shipping_total'] ?? 0),
            'shipping_address' => $this->cartService->getShippingAddress(),
            'billing_address' => $this->cartService->getBillingAddress(),
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

    public function success(Request $request): Response|RedirectResponse
    {
        $sessionId = $request->query('session_id');
        $paypalOrderId = $request->query('paypal_order_id');
        $orderId = $request->query('order_id');

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
        } elseif ($orderId) {
            $order = Order::query()
                ->with(['items', 'customer'])
                ->where('id', $orderId)
                ->where('customer_id', $request->user()?->id)
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
