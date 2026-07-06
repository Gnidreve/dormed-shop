<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_only_counts_real_completed_orders(): void
    {
        Order::factory()->create(['status' => 'paid', 'total_amount' => '100.00', 'is_test' => false]);
        Order::factory()->create(['status' => 'processing', 'total_amount' => '50.00', 'is_test' => false]);
        Order::factory()->create(['status' => 'completed', 'total_amount' => '25.00', 'is_test' => false]);

        Order::factory()->create(['status' => 'paid', 'total_amount' => '9999.00', 'is_test' => true]);
        Order::factory()->create(['status' => 'pending', 'total_amount' => '500.00', 'is_test' => false]);
        Order::factory()->create(['status' => 'failed', 'total_amount' => '500.00', 'is_test' => false]);
        Order::factory()->create(['status' => 'cancelled', 'total_amount' => '500.00', 'is_test' => false]);

        $admin = User::factory()->admin()->create();
        $today = now()->toDateString();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertOk();

        $chartData = collect($response->viewData('page')['props']['chartData']);
        $todayRow = $chartData->firstWhere('date', $today);

        $this->assertSame(3, $todayRow['orders']);
        $this->assertSame(175.0, $todayRow['revenue']);
    }
}
