<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'stars' => fake()->numberBetween(1, 5),
            'content' => fake()->sentence(),
        ];
    }
}
