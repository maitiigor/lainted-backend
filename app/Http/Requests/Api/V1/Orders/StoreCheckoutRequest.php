<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_address_id' => ['required', 'string', 'exists:delivery_addresses,uuid'],
            'delivery_tier' => ['required', 'string', 'in:pickup,standard,bulky,furniture'],
            'payment_method' => ['required', 'string', 'in:online,cash_on_delivery'],
            // Required only for online payment - enforced in OrderService,
            // not here, since that's a business rule, not a format check.
            'email' => ['nullable', 'email'],
        ];
    }
}
