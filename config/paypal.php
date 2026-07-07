<?php

/**
 * PayPal client configuration (srmklive/paypal).
 *
 * Credentials und Webhook-ID leben als Settings in der Datenbank (Admin →
 * Einstellungen → Zahlungsarten; initial befüllbar über den PaymentSeeder
 * mit SEED_PAYPAL_*-env-Keys). Die leeren Werte hier sind nur die Struktur,
 * die PayPalService::buildConfig() mit den Settings überlagert. Der Modus
 * (sandbox/live) kommt aus App\Support\PaymentMode, nicht aus dieser Datei.
 */

return [
    'sandbox' => [
        'client_id' => '',
        'client_secret' => '',
        'app_id' => 'APP-80W284485P519543T', // Sandbox app_id is always this fixed value.
        'merchant_id' => '',
    ],
    'live' => [
        'client_id' => '',
        'client_secret' => '',
        // Live app_id: developer.paypal.com → My Apps & Credentials → App ID ("APP-…").
        'app_id' => '',
        'merchant_id' => '',
    ],

    'webhook_id' => '',

    'payment_action' => 'Sale', // Can only be 'Sale', 'Authorization' or 'Order'
    'currency' => 'EUR',
    'notify_url' => '',
    'locale' => 'de_DE',
    'validate_ssl' => true,
    'timeout' => 30, // Total request timeout in seconds.
    'connect_timeout' => 10, // Connection timeout in seconds.
    'max_retries' => 2, // Retries on 5xx / connection errors (0 to disable). Uses exponential backoff.
];
