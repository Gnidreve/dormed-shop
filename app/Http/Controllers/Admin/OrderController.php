<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => Order::with('customer')->latest()->paginate(20),
        ]);
    }


    public function show(Order $order): Response
    {
        $order->load(['customer', 'items', 'payments']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

}
