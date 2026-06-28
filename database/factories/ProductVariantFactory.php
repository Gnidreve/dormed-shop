<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['1er-Pack', '4er-Pack', '8er-Pack', '12er-Pack']),
            'price' => $this->faker->randomFloat(2, 5, 200),
            'sort_order' => 0,
            'is_default' => false,
        ];
    }
}
