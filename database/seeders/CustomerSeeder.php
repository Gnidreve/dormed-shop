<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Max Mustermann',
            'email' => 'l.everding@web.de',
            'password' => Hash::make('password'),
        ]);

        $customer->addresses()->create([
            'type' => 'shipping',
            'is_default' => true,
            'salutation' => 'Herr',
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'company' => 'Dormed GmbH',
            'street' => 'Musterstraße',
            'house_number' => '12',
            'zip' => '10115',
            'city' => 'Berlin',
            'country' => 'DE',
            'phone' => '+49 30 123456',
        ]);
    }
}
