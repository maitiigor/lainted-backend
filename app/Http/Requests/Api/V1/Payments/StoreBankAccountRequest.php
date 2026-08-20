<?php

namespace App\Http\Requests\Api\V1\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'max:20'],
            // Resolved via POST /bank-accounts/resolve before this is
            // submitted - the client sends back Paystack's verified name,
            // never a freely-typed one.
            'account_name' => ['required', 'string', 'max:255'],
        ];
    }
}
