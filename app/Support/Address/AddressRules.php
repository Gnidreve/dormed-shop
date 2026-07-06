<?php

namespace App\Support\Address;

class AddressRules
{
    /**
     * @var array<string, array<int, string>>
     */
    private const BASE = [
        'company' => ['nullable', 'string', 'max:255'],
        'salutation' => ['nullable', 'string', 'in:Herr,Frau'],
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'street' => ['required', 'string', 'max:255'],
        'house_number' => ['required', 'string', 'max:20'],
        'address_line2' => ['nullable', 'string', 'max:255'],
        'zip' => ['required', 'string', 'max:20'],
        'city' => ['required', 'string', 'max:255'],
        'country' => ['required', 'string', 'size:2'],
        'phone' => ['nullable', 'string', 'max:50'],
    ];

    /**
     * Validation rules for an address, keyed by "{$prefix}.{$field}".
     *
     * @return array<string, array<int, string>>
     */
    public static function forPrefix(string $prefix, bool $required = true): array
    {
        return collect(self::BASE)
            ->mapWithKeys(fn (array $rules, string $field) => [
                "{$prefix}.{$field}" => $required ? $rules : array_map(
                    fn (string $rule): string => $rule === 'required' ? 'nullable' : $rule,
                    $rules
                ),
            ])
            ->all();
    }
}
