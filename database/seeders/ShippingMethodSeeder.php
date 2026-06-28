<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $methods = [
            [
                'name' => 'Standardversand (DPD)',
                'description' => 'Lieferung innerhalb Deutschlands.',
                'price' => '9.00',
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium-Versand (DPD)',
                'description' => 'Versicherter Versand mit Sendungsverfolgung und Haftungsschutz.',
                'price' => '25.00',
                'sort_order' => 2,
            ],
            [
                'name' => 'Selbstabholung',
                'description' => 'Abholung nach Terminvereinbarung.',
                'price' => '0.00',
                'sort_order' => 3,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::firstOrCreate(
                ['name' => $method['name']],
                $method,
            );
        }
    }
}
