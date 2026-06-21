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

        // Categories
        $categories = collect([
            ['name' => 'Ultraschallsysteme', 'slug' => 'ultraschallsysteme', 'description' => null],
            ['name' => 'Zubehör', 'slug' => 'zubehoer', 'description' => null],
            ['name' => 'Verbrauchsartikel', 'slug' => 'verbrauchsartikel', 'description' => null],
        ])->map(fn (array $data) => Category::create($data));

        // Manufacturers & Products
        $manufacturers = Manufacturer::factory(5)->create();
        Product::factory(30)->recycle($manufacturers)->make()->each(function ($product) use ($categories) {
            $product->category_id = $categories->random()->id;
            $product->save();
        });

        // Orders: 7 Tage, Variation zwischen Volumentagen und Hochwerttagen
        $scenarios = [
            ['daysAgo' => 6, 'count' => 9,  'min' => 12,   'max' => 55],    // Volumentag
            ['daysAgo' => 5, 'count' => 3,  'min' => 650,  'max' => 1800],  // Hochwerttag
            ['daysAgo' => 4, 'count' => 14, 'min' => 8,    'max' => 40],    // Starker Volumentag
            ['daysAgo' => 3, 'count' => 2,  'min' => 900,  'max' => 2400],  // Sehr hohe Einzelbestellungen
            ['daysAgo' => 2, 'count' => 7,  'min' => 25,   'max' => 90],    // Gemischter Tag
            ['daysAgo' => 1, 'count' => 4,  'min' => 400,  'max' => 1200],  // Hochwerttag
            ['daysAgo' => 0, 'count' => 11, 'min' => 15,   'max' => 65],    // Volumentag (heute)
        ];

        foreach ($scenarios as $scenario) {
            for ($i = 0; $i < $scenario['count']; $i++) {
                $date = now()
                    ->subDays($scenario['daysAgo'])
                    ->setHour(random_int(7, 21))
                    ->setMinute(random_int(0, 59))
                    ->setSecond(random_int(0, 59));

                Order::factory()->recycle($customers)->create([
                    'total_amount' => fake()->randomFloat(2, $scenario['min'], $scenario['max']),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
