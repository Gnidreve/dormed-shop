<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->string('q')->trim();

        $products = Product::with('manufacturer')
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'query' => $query->toString(),
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load('manufacturer');

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();

        if ($query->isEmpty()) {
            return response()->json(['results' => [], 'total' => 0]);
        }

        $results = Product::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'price']);

        $total = Product::where('name', 'like', "%{$query}%")->count();

        return response()->json([
            'results' => $results,
            'total' => $total,
        ]);
    }
}
