<?php

return [
    'cart' => [
        'session_key' => 'cart',
        'vat_rate' => 19,

        /*
        |--------------------------------------------------------------------------
        | Payment Providers
        |--------------------------------------------------------------------------
        | Invoice ist immer verfügbar. Welcher Gateway (paypal/stripe) zusätzlich
        | angeboten wird, steuert das Admin-Setting `payment.provider`
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
            'stripe' => [
                'methods' => [
                    [
                        'id' => 'stripe_card',
                        'label' => 'Kreditkarte (Stripe)',
                        'description' => 'Sicher bezahlen per Kreditkarte über Stripe.',
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
