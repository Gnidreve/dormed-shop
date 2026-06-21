<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected static array $encryptedKeys = [
        'stripe.secret_key',
        'stripe.webhook_secret',
        'mail.smtp_password',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::find($key)?->value ?? $default;

        if ($value !== null && in_array($key, static::$encryptedKeys, true)) {
            try {
                return decrypt($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    public static function set(string $key, ?string $value): void
    {
        if ($value !== null && in_array($key, static::$encryptedKeys, true)) {
            $value = encrypt($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
