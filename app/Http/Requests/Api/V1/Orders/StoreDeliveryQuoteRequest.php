<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'string', 'exists:listings,uuid'],
            'delivery_address_id' => ['required', 'string', 'exists:delivery_addresses,uuid'],
            'delivery_tier' => ['required', 'string', 'in:pickup,standard,bulky,furniture'],
        ];
    }
}
