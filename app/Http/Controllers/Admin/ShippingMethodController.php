<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingMethodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Shipping', [
            'methods' => ShippingMethod::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sort_order'] = ShippingMethod::max('sort_order') + 1;

        ShippingMethod::create($data);

        return back()->with('success', 'Versandart hinzugefügt.');
    }

    public function update(Request $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shippingMethod->update($data);

        return back()->with('success', 'Versandart aktualisiert.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->delete();

        return back()->with('success', 'Versandart gelöscht.');
    }
}
