<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): Response
    {
        $sort = $request->input('sort', 'name_asc');

        [$column, $direction] = match ($sort) {
            'name_desc' => ['name', 'desc'],
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            default => ['name', 'asc'],
        };

        $products = $category->products()
            ->with('manufacturer')
            ->orderBy($column, $direction)
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Products/ByCategory', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'products' => $products,
            'sort' => $sort,
        ]);
    }
}
