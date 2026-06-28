<?php

namespace Tests\Feature\Cart;

use App\Mail\NewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $freeShipping;

    /**
     * A complete shipping address that satisfies the checkout address guard.
     *
     * @return array<string, string>
     */
    private function completeAddress(): array
    {
        return [
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'street' => 'Musterstraße',
            'house_number' => '1',
            'zip' => '44135',
            'city' => 'Dortmund',
            'country' => 'DE',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a free (Selbstabholung) shipping method as default so totals are predictable
        $this->freeShipping = ShippingMethod::factory()->create([
            'name' => 'Selbstabholung',
            'price' => '0.00',
            'sort_order' => 1,
        ]);
    }

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
                ->where('cart.total', '39.98'));
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
                ->where('cart.total', '19.99'));
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
                'shipping_method' => (string) $this->freeShipping->id,
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
        $expressShipping = ShippingMethod::factory()->create([
            'name' => 'Express',
            'price' => '15.00',
            'sort_order' => 2,
        ]);

        $this->withSession([
            'cart' => [
                'items' => [],
                'shipping_method' => (string) $this->freeShipping->id,
                'payment_method' => 'invoice',
            ],
        ])->patch(route('cart.shipping.update'), [
            'shipping_method' => (string) $expressShipping->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('cart.shipping_method', (string) $expressShipping->id);
    }

    public function test_checkout_confirm_redirects_when_cart_is_empty(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->get(route('checkout.confirm'))
            ->assertRedirect(route('checkout.index'));
    }

    public function test_authenticated_customer_can_place_an_order(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Order Product', 'price' => '10.00']);

        $response = $this->actingAs($customer)
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
                    'shipping_method' => (string) $this->freeShipping->id,
                    'payment_method' => 'invoice',
                    'shipping_address' => $this->completeAddress(),
                ],
            ])->post(route('checkout.submit'), [
                'agreed_to_terms' => true,
            ]);

        $order = Order::query()->first();

        $response->assertRedirect(route('checkout.success', ['order_id' => $order->id]));

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_method' => 'invoice',
            'total_amount' => '20.00',
        ]);

        $this->assertSame(1, Order::query()->count());

        // Both the customer confirmation and the admin notification go out.
        Mail::assertSent(OrderConfirmationMail::class);
        Mail::assertSent(NewOrderMail::class);
    }

    public function test_order_submit_requires_a_complete_shipping_address(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Guarded Product', 'price' => '10.00']);

        $this->actingAs($customer)
            ->withSession([
                'cart' => [
                    'items' => [
                        $product->id => [
                            'quantity' => 1,
                            'unit_price' => '10.00',
                            'name' => $product->name,
                            'product_number' => (string) $product->id,
                        ],
                    ],
                    'shipping_method' => (string) $this->freeShipping->id,
                    'payment_method' => 'invoice',
                ],
            ])->post(route('checkout.submit'), [
                'agreed_to_terms' => true,
            ])
            ->assertSessionHasErrors('shipping_address');

        $this->assertSame(0, Order::query()->count());
        Mail::assertNothingSent();
    }
}
