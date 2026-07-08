<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContactsSeeder::class,
            PaymentSeeder::class,
            MailSeeder::class,
            ShippingMethodSeeder::class,
            ProductSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                UserSeeder::class,
                CustomerSeeder::class,
                RatingSeeder::class,
            ]);
        }
    }
}
