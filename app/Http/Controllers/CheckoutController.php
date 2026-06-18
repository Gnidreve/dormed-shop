<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\UpdateCartPaymentMethodRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Mail\NewOrderMail;
use App\Models\Order;
use App\Support\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

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

        return Inertia::render('Checkout/Confirm', [
            'cart' => $cart,
        ]);
    }

    public function updatePayment(UpdateCartPaymentMethodRequest $request): RedirectResponse
    {
        $this->cartService->setPaymentMethod($request->string('payment_method')->toString());

        return back();
    }

    public function submit(PlaceOrderRequest $request): RedirectResponse
    {
        $cart = $this->cartService->cart();

        if ($cart['is_empty']) {
            return to_route('checkout.index');
        }

        $order = Order::query()->create([
            'customer_id' => $request->user()->id,
            'status' => 'pending',
            'total_amount' => $cart['total'],
        ]);

        session()->flash('checkout_success', [
            'order_id' => $order->id,
            'items' => $cart['items'],
            'subtotal' => $cart['subtotal'],
            'shipping_total' => $cart['shipping_total'],
            'vat_rate' => $cart['vat_rate'],
            'vat_amount' => $cart['vat_amount'],
            'total' => $cart['total'],
            'customer_email' => $request->user()->email,
        ]);

        $this->cartService->clear();

        $recipients = ['l.everding@dormed.de', 'l.everding@web.de'];
        $customerEmail = $request->user()->email;

        try {
            Mail::to($recipients)->send(new NewOrderMail($order, $cart, $request->user()));

            foreach ($recipients as $recipient) {
                Log::channel('mail')->info("Order-Confirmation successfully sent to {$recipient}", [
                    'customer' => $customerEmail,
                    'order_id' => $order->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('mail')->error('Order-Confirmation failed to send', [
                'to' => implode(', ', $recipients),
                'customer' => $customerEmail,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return to_route('checkout.success');
    }

    public function success(): Response|RedirectResponse
    {
        $data = session('checkout_success');

        if (! $data) {
            return to_route('home');
        }

        return Inertia::render('Checkout/Success', $data);
    }
}
