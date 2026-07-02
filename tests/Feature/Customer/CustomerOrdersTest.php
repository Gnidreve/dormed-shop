<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customer.orders'))->assertRedirect(route('login'));
    }

    public function test_customer_sees_only_their_own_orders(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(2)->for($customer)->create();
        Order::factory()->for(Customer::factory())->create();

        $this->actingAs($customer)
            ->get(route('customer.orders'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('settings/Orders')
                    ->has('orders', 2)
            );
    }

    public function test_orders_are_listed_most_recent_first(): void
    {
        $customer = Customer::factory()->create();
        $older = Order::factory()->for($customer)->create(['created_at' => now()->subDay()]);
        $newer = Order::factory()->for($customer)->create(['created_at' => now()]);

        $this->actingAs($customer)
            ->get(route('customer.orders'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->where('orders.0.id', $newer->id)
                    ->where('orders.1.id', $older->id)
            );
    }
}
