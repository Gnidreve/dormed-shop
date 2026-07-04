<?php

return [
    'cart' => [
        'session_key' => 'cart',
        'vat_rate' => 19,

        /*
        |--------------------------------------------------------------------------
        | Payment Providers
        |--------------------------------------------------------------------------
        | Invoice ist immer verfügbar. Ob PayPal zusätzlich angeboten wird,
        | steuert das Admin-Setting `payment.provider`
        | (siehe CartService::paymentMethods()).
        */
        'providers' => [
            'invoice' => [
                'methods' => [
                    [
                        'id' => 'invoice',
                        'label' => 'Kauf auf Rechnung',
                        'description' => 'Zahlung per Überweisung nach Erhalt der Rechnung.',
                    ],
                ],
            ],
            'paypal' => [
                'methods' => [
                    [
                        'id' => 'paypal',
                        'label' => 'PayPal',
                        'description' => 'Sicher bezahlen mit PayPal – Lastschrift, Kreditkarte oder PayPal-Guthaben.',
                    ],
                ],
            ],
        ],
    ],
];
