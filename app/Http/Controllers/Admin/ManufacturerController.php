<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
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
}
