<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\UpdateCartPaymentMethodRequest;
use App\Support\Cart\CartService;
use Illuminate\Http\RedirectResponse;
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
}
