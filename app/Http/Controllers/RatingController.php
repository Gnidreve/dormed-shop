<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request, Product $product): RedirectResponse
    {
        $product->ratings()->create($request->validated());

        return back()->with('success', 'Bewertung wurde gespeichert.');
    }
}
