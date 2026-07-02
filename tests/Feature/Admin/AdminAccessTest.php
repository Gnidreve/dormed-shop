<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function guardedRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'products index' => ['admin.products.index'],
            'orders index' => ['admin.orders.index'],
            'customers index' => ['admin.customers.index'],
            'categories index' => ['admin.categories.index'],
            'manufacturers index' => ['admin.manufacturers.index'],
            'settings index' => ['admin.settings.index'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_guests_are_redirected_to_admin_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('guardedRoutes')]
    public function test_authenticated_admin_can_access(string $routeName): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_customer_session_does_not_grant_admin_access(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
