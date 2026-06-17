<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\UpdateCartShippingMethodRequest;
use App\Models\Product;
use App\Support\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Checkout/Index', [
            'cart' => $this->cartService->cart(),
        ]);
    }

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $this->cartService->add($product, $request->integer('quantity'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produkt wurde in den Warenkorb gelegt.']);

        return back();
    }

    public function update(UpdateCartItemRequest $request, Product $product): RedirectResponse
    {
        $this->cartService->updateQuantity($product, $request->integer('quantity'));

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->cartService->remove($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produkt wurde aus dem Warenkorb entfernt.']);

        return back();
    }

    public function updateShipping(UpdateCartShippingMethodRequest $request): RedirectResponse
    {
        $this->cartService->setShippingMethod($request->string('shipping_method')->toString());

        return back();
    }
}
