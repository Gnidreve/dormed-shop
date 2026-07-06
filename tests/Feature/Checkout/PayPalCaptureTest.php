<?php

namespace Tests\Feature\Checkout;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPalCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_requires_authentication(): void
    {
        $payment = Payment::factory()->created()->create();

        $this->post(route('paypal.order.capture'), [
            'paypal_order_id' => $payment->paypal_order_id,
        ])->assertRedirect(route('login'));
    }

    public function test_capture_rejects_another_customers_payment(): void
    {
        $owner = Customer::factory()->create();
        $order = Order::factory()->for($owner)->create(['status' => 'pending']);
        $payment = Payment::factory()->created()->for($order)->create();

        $attacker = Customer::factory()->create();

        $this->actingAs($attacker)
            ->postJson(route('paypal.order.capture'), [
                'paypal_order_id' => $payment->paypal_order_id,
            ])
            ->assertNotFound();

        $this->assertSame('CREATED', $payment->fresh()->status);
    }

    public function test_capture_succeeds_for_own_payment(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->for($customer)->create(['status' => 'pending']);
        $payment = Payment::factory()->created()->for($order)->create();

        $this->mock(PayPalService::class, function ($mock) use ($payment): void {
            $mock->shouldReceive('captureOrder')
                ->once()
                ->with($payment->paypal_order_id)
                ->andReturn(['status' => 'COMPLETED']);
            $mock->shouldReceive('getCaptureIdFromOrder')
                ->once()
                ->andReturn('CAPTURE123456789');
        });

        $this->actingAs($customer)
            ->postJson(route('paypal.order.capture'), [
                'paypal_order_id' => $payment->paypal_order_id,
            ])
            ->assertOk()
            ->assertJson(['status' => 'COMPLETED']);

        $this->assertSame('COMPLETED', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_create_order_error_response_does_not_leak_internals(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => '10.00']);

        $this->mock(PayPalService::class, function ($mock): void {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andThrow(new \RuntimeException('secret internal detail'));
        });

        $response = $this->actingAs($customer)
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
                    'shipping_address' => [
                        'first_name' => 'Erika',
                        'last_name' => 'Mustermann',
                        'street' => 'Musterstraße',
                        'house_number' => '1',
                        'zip' => '44135',
                        'city' => 'Dortmund',
                        'country' => 'DE',
                    ],
                ],
            ])
            ->postJson(route('paypal.order.create'), ['agreed_to_terms' => true]);

        $response->assertStatus(500);
        $this->assertArrayNotHasKey('debug', $response->json());
    }
}
