<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // PayPal — Sandbox
            'paypal.sandbox.client_id' => env('SEED_PAYPAL_SANDBOX_CLIENT_ID'),
            'paypal.sandbox.client_secret' => env('SEED_PAYPAL_SANDBOX_CLIENT_SECRET'),
            'paypal.sandbox.merchant_id' => env('SEED_PAYPAL_SANDBOX_MERCHANT_ID'),

            // PayPal — Live
            'paypal.live.client_id' => env('SEED_PAYPAL_LIVE_CLIENT_ID'),
            'paypal.live.client_secret' => env('SEED_PAYPAL_LIVE_CLIENT_SECRET'),
            'paypal.live.app_id' => env('SEED_PAYPAL_LIVE_APP_ID'),
            'paypal.live.merchant_id' => env('SEED_PAYPAL_LIVE_MERCHANT_ID'),

            // PayPal — Webhook
            'paypal.webhook_id' => env('SEED_PAYPAL_WEBHOOK_ID'),
        ];

        foreach ($settings as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::set($key, $value);
            }
        }
    }
}
