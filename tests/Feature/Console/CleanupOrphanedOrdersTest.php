<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupOrphanedOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_old_pending_orders_without_completed_payment(): void
    {
        $orphan = Order::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        Payment::factory()->created()->for($orphan)->create();

        $this->artisan('orders:cleanup-orphaned')->assertSuccessful();

        $this->assertSame('cancelled', $orphan->fresh()->status);
    }

    public function test_keeps_recent_pending_orders(): void
    {
        $recent = Order::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(1),
        ]);

        $this->artisan('orders:cleanup-orphaned')->assertSuccessful();

        $this->assertSame('pending', $recent->fresh()->status);
    }

    public function test_keeps_old_pending_orders_with_completed_payment(): void
    {
        $paid = Order::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        Payment::factory()->completed()->for($paid)->create();

        $this->artisan('orders:cleanup-orphaned')->assertSuccessful();

        $this->assertSame('pending', $paid->fresh()->status);
    }

    public function test_respects_custom_hours_option(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);

        $this->artisan('orders:cleanup-orphaned', ['--hours' => 1])->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
    }
}
