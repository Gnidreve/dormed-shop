<?php

return [
    'cart' => [
        'session_key' => 'cart',
        'vat_rate' => 19,

        /*
        |--------------------------------------------------------------------------
        | Payment Providers
        |--------------------------------------------------------------------------
        | Festes Line-up: Kauf auf Rechnung + PayPal — mehr gibt es nicht.
        | Reihenfolge hier = Anzeige-Reihenfolge (erste Methode ist Default).
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
