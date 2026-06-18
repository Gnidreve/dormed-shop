<?php

namespace Tests\Feature\Cart;

use App\Models\Customer;
use App\Models\Order;
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

    public function test_cart_keeps_the_snapshot_price_when_product_price_changes(): void
    {
        $product = Product::factory()->create(['name' => 'Snapshot Product', 'price' => '19.99']);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $product->update(['price' => '99.99']);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('cart.items.0.unit_price', '19.99')
                ->where('cart.total', '29.51'));
    }

    public function test_cart_item_quantity_can_be_updated(): void
    {
        $product = Product::factory()->create(['name' => 'Quantity Product', 'price' => '10.00']);

        $this->withSession([
            'cart' => [
                'items' => [
                    $product->id => [
                        'quantity' => 1,
                        'unit_price' => '10.00',
                        'name' => $product->name,
                        'product_number' => (string) $product->id,
                    ],
                ],
                'shipping_method' => 'dpd_standard',
                'payment_method' => 'invoice',
            ],
        ])->patch(route('cart.items.update', $product), [
            'quantity' => 3,
        ])
            ->assertRedirect()
            ->assertSessionHas("cart.items.{$product->id}.quantity", 3);
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

    public function test_authenticated_customer_can_place_an_order(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Order Product', 'price' => '10.00']);

        $this->actingAs($customer)
            ->withSession([
                'cart' => [
                    'items' => [
                        $product->id => [
                            'quantity' => 2,
                            'unit_price' => '10.00',
                            'name' => $product->name,
                            'product_number' => (string) $product->id,
                        ],
                    ],
                    'shipping_method' => 'self_pickup',
                    'payment_method' => 'invoice',
                ],
            ])->post(route('checkout.submit'), [
                'agreed_to_terms' => true,
            ])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => '20.00',
        ]);

        $this->assertSame(1, Order::query()->count());
    }
}
