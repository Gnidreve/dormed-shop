<?php

namespace App\Support;

class PaymentMode
{
    public static function current(): string
    {
        return app()->environment('production') ? 'live' : 'sandbox';
    }

    public static function isLive(): bool
    {
        return self::current() === 'live';
    }
}
