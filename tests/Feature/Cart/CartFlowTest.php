<?php

namespace Tests\Feature\Cart;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_added_to_the_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product', 'price' => '19.99']);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Checkout/Index')
                ->where('cart.count', 2)
                ->where('cart.items.0.product_id', $product->id)
                ->where('cart.total', '49.50'));
    }

    public function test_cart_item_quantity_can_be_updated(): void
    {
        $product = Product::factory()->create(['price' => '10.00']);

        $this->withSession([
            'cart' => [
                'items' => [$product->id => 1],
                'shipping_method' => 'dpd_standard',
                'payment_method' => 'invoice',
            ],
        ])->patch(route('cart.items.update', $product), [
            'quantity' => 3,
        ])
            ->assertRedirect()
            ->assertSessionHas("cart.items.{$product->id}", 3);
    }

    public function test_shipping_method_can_be_updated(): void
    {
        $this->withSession([
            'cart' => [
                'items' => [],
                'shipping_method' => 'dpd_standard',
                'payment_method' => 'invoice',
            ],
        ])->patch(route('cart.shipping.update'), [
            'shipping_method' => 'self_pickup',
        ])
            ->assertRedirect()
            ->assertSessionHas('cart.shipping_method', 'self_pickup');
    }

    public function test_checkout_confirm_redirects_when_cart_is_empty(): void
    {
        $this->get(route('checkout.confirm'))
            ->assertRedirect(route('checkout.index'));
    }
}
