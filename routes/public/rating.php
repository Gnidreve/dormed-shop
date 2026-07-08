<?php

use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

// TODO(order-based-ratings): Diese Route haengt eine Bewertung an ein Produkt,
// ohne Bezug zu einer Bestellung oder einem Kunden (siehe RatingController).
// Geplant ist eine Umstellung auf Bewertungen ueber die Order (Kunde bewertet
// nach Erhalt seiner Bestellung), nicht mehr frei ueber die Produktseite.
// Route/Controller bleiben bis dahin bestehen, aber nicht weiter ausbauen -
// neue Rating-Funktionalitaet gehoert in den Order-Flow.
Route::post('/products/{product}/ratings', [RatingController::class, 'store'])->name('ratings.store');
