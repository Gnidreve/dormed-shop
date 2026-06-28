<?php

namespace App\Support;

use App\Models\Setting;

class PaymentMode
{
    /**
     * Resolve the active payment mode.
     *
     * An explicit `payment.mode` setting (sandbox|live) wins, so the admin can
     * switch in the backend. Without it, the mode follows the environment
     * (production = live, everything else = sandbox).
     */
    public static function current(): string
    {
        $configured = Setting::get('payment.mode');

        if (in_array($configured, ['sandbox', 'live'], true)) {
            return $configured;
        }

        return app()->environment('production') ? 'live' : 'sandbox';
    }

    public static function isLive(): bool
    {
        return self::current() === 'live';
    }
}
