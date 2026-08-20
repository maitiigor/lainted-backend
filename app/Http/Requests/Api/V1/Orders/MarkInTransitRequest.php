<?php

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class MarkInTransitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logistics_provider' => ['nullable', 'string', 'in:self_pickup,gig,kwik,sendbox,other'],
            'tracking_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
