<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /** Top-level URL segments reserved by other routes — slugs must not collide. */
    private const RESERVED_SLUGS = [
        'admin', 'products', 'checkout', 'warenkorb', 'customer',
        'settings', 'stripe', 'versand', 'impressum', 'agb',
        'datenschutz', 'zahlung', 'widerrufsbelehrung', 'hilfe',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique('categories', 'slug')->ignore($category),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Der Slug darf nur Kleinbuchstaben, Ziffern und Bindestriche enthalten.',
            'slug.not_in' => 'Dieser Slug ist reserviert und kann nicht verwendet werden.',
        ];
    }
}
