<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $days = 90;
        $from = Carbon::today()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->select(
                DB::raw('date(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue'),
            )
            ->where('created_at', '>=', $from)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartData = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($from, $rows): array {
                $date = $from->copy()->addDays($offset)->toDateString();
                $row = $rows->get($date);

                return [
                    'date' => $date,
                    'orders' => (int) ($row?->orders ?? 0),
                    'revenue' => (float) ($row?->revenue ?? 0),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'chartData' => $chartData->values(),
        ]);
    }
}
