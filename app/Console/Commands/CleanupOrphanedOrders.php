<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:cleanup-orphaned {--hours=24 : Minimum age in hours before a pending order is cancelled}')]
#[Description('Storniert alte pending-Orders ohne abgeschlossene Zahlung (z.B. abgebrochene PayPal-Versuche)')]
class CleanupOrphanedOrders extends Command
{
    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $count = Order::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'COMPLETED'))
            ->update(['status' => 'cancelled']);

        $this->info("{$count} verwaiste Order(s) storniert.");

        return self::SUCCESS;
    }
}
