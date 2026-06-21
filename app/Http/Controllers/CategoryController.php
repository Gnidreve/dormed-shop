<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function show(Category $category): Response
    {
        $products = $category->products()
            ->with('manufacturer')
            ->orderBy('name')
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
        ]);
    }
}
