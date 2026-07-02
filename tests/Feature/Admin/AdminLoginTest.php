<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Login'));
    }

    public function test_admin_can_log_in_with_valid_credentials(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@dormed.test',
            'password' => 'geheim1234',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@dormed.test',
            'password' => 'geheim1234',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@dormed.test',
            'password' => 'geheim1234',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@dormed.test',
            'password' => 'falsch',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->post(route('admin.login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_admin_can_log_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertFalse(Auth::guard('admin')->check());
    }
}
