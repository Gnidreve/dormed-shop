<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
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
