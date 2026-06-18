<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin users
        User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@dormed24.de',
        ]);
        User::factory(3)->create();

        // Customers
        $customers = Customer::factory(10)->create();
        Customer::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);

        // One category for now
        $category = Category::create([
            'name' => 'Medizintechnik',
            'slug' => 'medizintechnik',
            'description' => null,
        ]);

        // Manufacturers & Products
        $manufacturers = Manufacturer::factory(5)->create();
        $products = Product::factory(20)->recycle($manufacturers)->create([
            'category_id' => $category->id,
        ]);

        // Orders
        Order::factory(15)->recycle($customers)->create();
    }
}
