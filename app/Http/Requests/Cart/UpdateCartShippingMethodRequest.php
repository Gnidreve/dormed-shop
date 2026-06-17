<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCartShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_method' => [
                'required',
                'string',
                Rule::in(collect(config('shop.cart.shipping_methods', []))->pluck('id')->all()),
            ],
        ];
    }
}
