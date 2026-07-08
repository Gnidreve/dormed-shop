<?php

namespace App\Support;

class PaymentMode
{
    /**
     * Resolve the active payment mode from the environment alone
     * (production = live, everything else = sandbox). Not overridable
     * via admin setting or any other mechanism.
     */
    public static function current(): string
    {
        return app()->environment('production') ? 'live' : 'sandbox';
    }

    public static function isLive(): bool
    {
        return self::current() === 'live';
    }
}
