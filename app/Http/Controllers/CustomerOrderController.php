<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Orders\OrderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->get(['id', 'status', 'total_amount', 'created_at']);

        // JSON für das User-Settings-Modal (lädt die Liste per fetch).
        if ($request->wantsJson()) {
            return response()->json(['orders' => $orders]);
        }

        return Inertia::render('settings/Orders', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order, OrderManager $orderManager): Response
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        $order->load([
            'items.product.images' => fn ($query) => $query->where('sort_order', 0),
            'payments',
        ]);

        $summary = $orderManager->summaryFromOrder($order);
        $paymentLabels = collect(config('shop.cart.providers', []))
            ->flatMap(fn (array $provider) => $provider['methods'] ?? [])
            ->mapWithKeys(fn (array $method) => [$method['id'] => $method['label']])
            ->all();

        return Inertia::render('settings/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_method_label' => $paymentLabels[$order->payment_method] ?? $order->payment_method,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'unit_price' => (string) $item->unit_price,
                    'quantity' => $item->quantity,
                    'image_url' => $item->product?->images->first()?->url,
                ])->values(),
                'summary' => $summary,
                'customer_email' => $request->user()->email,
            ],
        ]);
    }
}
