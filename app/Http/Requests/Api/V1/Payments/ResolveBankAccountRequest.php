<?php

namespace App\Http\Requests\Api\V1\Payments;

use Illuminate\Foundation\Http\FormRequest;

class ResolveBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_number' => ['required', 'string', 'max:20'],
            'bank_code' => ['required', 'string', 'max:20'],
        ];
    }
}
