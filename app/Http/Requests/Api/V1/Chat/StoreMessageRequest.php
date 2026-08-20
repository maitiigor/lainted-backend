<?php

namespace App\Http\Requests\Api\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:text,image'],
            'body' => ['required_if:type,text', 'nullable', 'string', 'max:2000'],
            'image' => ['required_if:type,image', 'nullable', 'image', 'max:8192'],
        ];
    }
}
