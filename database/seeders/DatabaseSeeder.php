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
            UserSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
