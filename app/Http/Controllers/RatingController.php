<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatingRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

/**
 * TODO(order-based-ratings): Bewertungen haengen aktuell nur am Produkt, anonym,
 * ohne Kunden-/Bestellbezug. Geplant: Umstellung auf Bewertungen ueber die Order
 * (Kunde bewertet nach Erhalt seiner Bestellung). Siehe routes/public/rating.php.
 */
class RatingController extends Controller
{
    public function store(StoreRatingRequest $request, Product $product): RedirectResponse
    {
        $product->ratings()->create($request->validated());

        return back()->with('success', 'Bewertung wurde gespeichert.');
    }
}
