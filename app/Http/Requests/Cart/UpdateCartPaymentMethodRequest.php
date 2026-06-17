<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCartPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                Rule::in(collect(config('shop.cart.payment_methods', []))->pluck('id')->all()),
            ],
        ];
    }
}
