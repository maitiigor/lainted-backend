<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'string', 'exists:listings,uuid'],
        ];
    }
}
