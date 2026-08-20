<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lga_id' => ['required', 'integer', 'exists:lgas,id'],
            'landmark' => ['required', 'string', 'max:255'],
            'full_address' => ['required', 'string', 'max:1000'],
            'recipient_phone' => ['required', 'string', 'max:20'],
        ];
    }
}
