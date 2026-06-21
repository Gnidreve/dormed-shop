<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateManufacturerRequest;
use App\Models\Manufacturer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ManufacturerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Manufacturers/Index', [
            'manufacturers' => Manufacturer::latest()->paginate(20),
        ]);
    }

    public function edit(Manufacturer $manufacturer): Response
    {
        return Inertia::render('Admin/Manufacturers/Edit', [
            'manufacturer' => $manufacturer,
        ]);
    }

    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer): RedirectResponse
    {
        $manufacturer->update($request->validated());

        return redirect()->route('admin.manufacturers.index')
            ->with('success', 'Hersteller aktualisiert.');
    }

    public function destroy(Manufacturer $manufacturer): RedirectResponse
    {
        $manufacturer->delete();

        return redirect()->route('admin.manufacturers.index')
            ->with('success', 'Hersteller gelöscht.');
    }
}
