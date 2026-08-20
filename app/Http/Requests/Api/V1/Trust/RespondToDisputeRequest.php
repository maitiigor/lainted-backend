<?php

namespace App\Http\Requests\Api\V1\Trust;

use Illuminate\Foundation\Http\FormRequest;

class RespondToDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seller_statement' => ['required', 'string'],
            'seller_photos' => ['nullable', 'array'],
            'seller_photos.*' => ['string'],
            // Omitting this entirely skips straight to admin review - see
            // DisputeService::respondAsSeller.
            'self_resolution_offered' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
