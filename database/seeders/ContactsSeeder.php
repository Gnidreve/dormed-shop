<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ContactsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'shop.name' => (string) env('APP_NAME', config('app.name', 'dormed-shop')),
            'shop.email' => 'mail@dormed.de',
            'shop.phone' => '02301188600',
            'shop.fax' => '02301188620',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
