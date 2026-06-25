<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->get(['id', 'status', 'total_amount', 'created_at']);

        return Inertia::render('settings/Orders', ['orders' => $orders]);
    }
}
