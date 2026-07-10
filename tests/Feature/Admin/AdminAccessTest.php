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
            'settings general' => ['admin.settings.general'],
            'settings payment' => ['admin.settings.payment'],
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

    public function test_settings_index_redirects_to_general(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index'))
            ->assertRedirect('/admin/settings/general');
    }

    public function test_customer_session_does_not_grant_admin_access(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_denied_admin_access(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_is_admin_is_not_mass_assignable(): void
    {
        // Das Privileg-Flag darf nie aus (potenziell request-basierten)
        // Massenzuweisungen gesetzt werden.
        $user = new User;
        $user->fill([
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $this->assertFalse((bool) $user->is_admin);
    }

    public function test_add_admin_command_creates_a_flagged_admin(): void
    {
        $this->artisan('add:admin')
            ->expectsQuestion('Name', 'Neuer Admin')
            ->expectsQuestion('E-Mail', 'neuer-admin@dormed24.de')
            ->expectsQuestion('Passwort', 'ein-sicheres-passwort')
            ->assertSuccessful();

        $user = User::query()->where('email', 'neuer-admin@dormed24.de')->first();

        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->is_admin);
    }
}
