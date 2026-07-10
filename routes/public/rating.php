<?php

// DEAKTIVIERT (S-1): Die Rating-Route haengt eine Bewertung anonym und
// ungedrosselt direkt an ein Produkt - jeder Treffer waere jetzt Missbrauch,
// da kein legitimer Client sie mehr aufruft: das zugehoerige Formular auf der
// Produktdetailseite ist ebenfalls auskommentiert
// (resources/js/pages/Products/Show.svelte, `{#if false}` mit TODO).
//
// Beides - diese Route UND das UI - wird GEMEINSAM reaktiviert, sobald
// Bewertungen ueber die Bestellung laufen (Kunde bewertet nur tatsaechlich
// gekaufte Produkte, order-scoped statt anonym). RatingController +
// StoreRatingRequest + Tests bleiben als Grundlage bestehen.
//
// use App\Http\Controllers\RatingController;
// use Illuminate\Support\Facades\Route;
//
// Route::post('/products/{product}/ratings', [RatingController::class, 'store'])->name('ratings.store');
