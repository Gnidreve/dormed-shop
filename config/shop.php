<?php

return [
    'cart' => [
        'session_key' => 'cart',
        'vat_rate' => 19,
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
        'payment_methods' => [
            [
                'id' => 'invoice',
                'label' => 'Rechnung',
                'description' => 'Zahlung nach Rechnungsstellung.',
            ],
            [
                'id' => 'prepayment',
                'label' => 'Vorkasse',
                'description' => 'Sie erhalten die Zahlungsdaten nach der Bestellung.',
            ],
        ],
    ],
];
