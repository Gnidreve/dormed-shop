<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@dormed24.de',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Max Mustermann',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $customer->addresses()->create([
            'type'         => 'shipping',
            'is_default'   => true,
            'salutation'   => 'Herr',
            'first_name'   => 'Max',
            'last_name'    => 'Mustermann',
            'company'      => 'Dormed GmbH',
            'street'       => 'Musterstraße',
            'house_number' => '12',
            'zip'          => '10115',
            'city'         => 'Berlin',
            'country'      => 'DE',
            'phone'        => '+49 30 123456',
        ]);

        $categories = collect([
            ['name' => 'Ultraschallsysteme', 'slug' => 'ultraschallsysteme', 'description' => null],
            ['name' => 'Zubehör', 'slug' => 'zubehoer', 'description' => null],
            ['name' => 'Verbrauchsartikel', 'slug' => 'verbrauchsartikel', 'description' => null],
        ])->map(fn (array $data) => Category::create($data));

        $manufacturers = Manufacturer::factory(5)->create();
        Product::factory(30)->recycle($manufacturers)->make()->each(function ($product) use ($categories) {
            $product->category_id = $categories->random()->id;
            $product->save();
        });
    }
}
