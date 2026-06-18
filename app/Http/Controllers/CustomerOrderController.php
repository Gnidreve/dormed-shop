<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->get(['id', 'status', 'total_amount', 'created_at']);

        return response()->json($orders);
    }
}
