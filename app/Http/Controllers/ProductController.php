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
        $sort = $request->input('sort', 'name_asc');

        [$column, $direction] = match ($sort) {
            'name_desc' => ['name', 'desc'],
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            default => ['name', 'asc'],
        };

        $baseQuery = Product::with(['manufacturer', 'images' => fn ($q) => $q->where('sort_order', 0)])
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy($column, $direction)
            ->orderBy('id');

        $total = $baseQuery->count();

        return Inertia::render('Products/Index', [
            'products' => Inertia::scroll(fn () => $baseQuery->paginate(24)),
            'total' => $total,
            'query' => $query->toString(),
            'sort' => $sort,
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load(['manufacturer', 'ratings', 'images', 'variants']);
        $product->loadAvg('ratings', 'stars');

        return Inertia::render('Products/Show', [
            'product' => array_merge($product->toArray(), [
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'sort_order' => $img->sort_order,
                ])->values(),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'label' => $v->label,
                    'price' => $v->price,
                    'is_default' => $v->is_default,
                ])->values(),
            ]),
            'ratings' => $product->ratings->map(fn ($rating) => [
                'id' => $rating->id,
                'stars' => $rating->stars,
                'content' => $rating->content,
                'created_at' => $rating->created_at?->format('d.m.Y'),
            ])->values(),
            'ratingSummary' => [
                'average' => $product->ratings_avg_stars !== null
                    ? number_format((float) $product->ratings_avg_stars, 1, ',', '.')
                    : null,
                'count' => $product->ratings->count(),
            ],
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
            ->get(['id', 'name', 'price']);

        $total = Product::where('name', 'like', "%{$query}%")->count();

        return response()->json([
            'results' => $results,
            'total' => $total,
        ]);
    }
}
