<?php

namespace Tests\Feature\Settings;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function shippingAddress(): array
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

    public function test_guests_cannot_access_address_settings(): void
    {
        $this->get(route('addresses.edit'))->assertRedirect(route('login'));
    }

    public function test_edit_page_renders(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->get(route('addresses.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('settings/Addresses'));
    }

    public function test_shipping_address_is_created(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->put(route('addresses.update'), [
                'shipping' => $this->shippingAddress(),
                'billing_same_as_shipping' => true,
            ])
            ->assertRedirect(route('addresses.edit'));

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'city' => 'Dortmund',
            'is_default' => true,
        ]);

        $this->assertSame(0, $customer->addresses()->where('type', 'billing')->count());
    }

    public function test_update_validates_required_shipping_fields(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->put(route('addresses.update'), [
                'shipping' => ['first_name' => 'Erika'],
                'billing_same_as_shipping' => true,
            ])
            ->assertSessionHasErrors([
                'shipping.last_name',
                'shipping.street',
                'shipping.zip',
                'shipping.city',
                'shipping.country',
            ]);
    }

    public function test_separate_billing_address_is_created(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->put(route('addresses.update'), [
                'shipping' => $this->shippingAddress(),
                'billing_same_as_shipping' => false,
                'billing' => array_merge($this->shippingAddress(), [
                    'first_name' => 'Max',
                    'city' => 'Bochum',
                ]),
            ])
            ->assertRedirect(route('addresses.edit'));

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'type' => 'billing',
            'first_name' => 'Max',
            'city' => 'Bochum',
        ]);
    }

    public function test_existing_shipping_address_is_updated_not_duplicated(): void
    {
        $customer = Customer::factory()->create();
        $customer->addresses()->create(array_merge($this->shippingAddress(), [
            'type' => 'shipping',
            'is_default' => true,
            'city' => 'Essen',
        ]));

        $this->actingAs($customer)
            ->put(route('addresses.update'), [
                'shipping' => $this->shippingAddress(),
                'billing_same_as_shipping' => true,
            ])
            ->assertRedirect(route('addresses.edit'));

        $this->assertSame(1, $customer->addresses()->where('type', 'shipping')->count());
        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'city' => 'Dortmund',
        ]);
    }
}
