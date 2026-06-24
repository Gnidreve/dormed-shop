<?php

return [
    'cart' => [
        'session_key' => 'cart',
        'vat_rate' => 19,

        /*
        |--------------------------------------------------------------------------
        | Payment Providers
        |--------------------------------------------------------------------------
        | Jeder Provider kann per .env aktiviert/deaktiviert werden.
        | Nur aktive Provider werden im Checkout als Zahlungsarten angezeigt.
        */
        'providers' => [
            'stripe' => [
                'enabled' => env('PAYMENT_STRIPE_ENABLED', true),
                'methods' => [
                    [
                        'id' => 'stripe_card',
                        'label' => 'Kreditkarte (Stripe)',
                        'description' => 'Sicher bezahlen per Kreditkarte über Stripe.',
                    ],
                ],
            ],
            'paypal' => [
                'enabled' => env('PAYMENT_PAYPAL_ENABLED', false),
                'methods' => [
                    [
                        'id' => 'paypal',
                        'label' => 'PayPal',
                        'description' => 'Sicher bezahlen mit PayPal – Lastschrift, Kreditkarte oder PayPal-Guthaben.',
                    ],
                ],
            ],
        ],

        'shipping_methods' => [
            [
                'id' => 'dpd_standard',
                'label' => 'Standardversand (DPD)',
                'description' => 'Lieferung innerhalb Deutschlands.',
                'price' => '9.52',
            ],
            [
                'id' => 'self_pickup',
                'label' => 'Selbstabholung',
                'description' => 'Abholung nach Terminvereinbarung.',
                'price' => '0.00',
            ],
        ],
    ],
];
