<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Customers/Index', [
            'customers' => Customer::latest()->paginate(20),
        ]);
    }


    public function show(Customer $customer): Response
    {
        $customer->load(['addresses', 'orders' => fn ($q) => $q->with('items')->latest()->limit(10)]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
        ]);
    }

}
