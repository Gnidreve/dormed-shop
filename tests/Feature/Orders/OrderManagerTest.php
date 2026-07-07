<?php

namespace Tests\Feature\Orders;

use App\Mail\NewOrderMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Orders\OrderManager;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_transitions_pending_order_and_sends_confirmations(): void
    {
        Mail::fake();

        $order = Order::factory()->create(['status' => 'pending']);

        $result = app(OrderManager::class)->markPaid($order);

        $this->assertTrue($result);
        $this->assertSame('paid', $order->fresh()->status);
        Mail::assertQueued(OrderConfirmationMail::class);
        Mail::assertQueued(NewOrderMail::class);
    }

    public function test_mark_paid_is_idempotent_and_sends_confirmations_only_once(): void
    {
        Mail::fake();

        $order = Order::factory()->create(['status' => 'pending']);
        $manager = app(OrderManager::class);

        $first = $manager->markPaid($order);
        $second = $manager->markPaid($order->fresh());

        $this->assertTrue($first);
        $this->assertFalse($second);
        Mail::assertQueued(OrderConfirmationMail::class, 1);
        Mail::assertQueued(NewOrderMail::class, 1);
    }

    public function test_create_from_cart_rolls_back_order_when_an_item_fails(): void
    {
        $customer = Customer::factory()->create();

        $cart = [
            'total' => '19.99',
            'shipping_total' => '0.00',
            'shipping_address' => null,
            'billing_address' => null,
            'items' => [
                [
                    'product_id' => null,
                    // NOT-NULL-Verletzung: der Item-Insert schlägt fehl, die
                    // bereits angelegte Order darf nicht stehen bleiben.
                    'name' => null,
                    'unit_price' => '19.99',
                    'quantity' => 1,
                ],
            ],
        ];

        try {
            app(OrderManager::class)->createFromCart($customer, $cart, 'invoice');
            $this->fail('Expected the item insert to fail.');
        } catch (QueryException) {
            // expected
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}
