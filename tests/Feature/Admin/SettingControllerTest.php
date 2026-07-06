<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    public function test_update_stores_whitelisted_settings(): void
    {
        $this->asAdmin()
            ->put(route('admin.settings.update'), [
                'settings' => ['shop.name' => 'dormed 24'],
            ])
            ->assertRedirect();

        $this->assertSame('dormed 24', Setting::get('shop.name'));
    }

    public function test_update_rejects_unknown_settings_key(): void
    {
        $this->asAdmin()
            ->put(route('admin.settings.update'), [
                'settings' => ['not.a.real.key' => 'hacked'],
            ])
            ->assertSessionHasErrors('settings');

        $this->assertNull(Setting::get('not.a.real.key'));
    }
}
