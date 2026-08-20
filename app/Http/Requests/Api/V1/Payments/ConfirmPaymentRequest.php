<?php

namespace App\Http\Requests\Api\V1\Payments;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
        ];
    }
}
