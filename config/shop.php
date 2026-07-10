<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ProductSeeder-Freigabe in Produktion
    |--------------------------------------------------------------------------
    | Der ProductSeeder LÖSCHT den kompletten Katalog inkl. Bilder und
    | importiert die Shopware-CSV neu. In Produktion läuft er nur, wenn dies
    | bewusst über SEED_PRODUCTS_FORCE=true (Erstimport) freigegeben wird.
    | Default false = fail-safe: im Zweifel nichts löschen.
    */
    'seed_products_force' => env('SEED_PRODUCTS_FORCE', false),

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
