<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Manufacturer>
 */
class ManufacturerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'country' => fake()->country(),
            'contact_email' => fake()->companyEmail(),
        ];
    }
}
