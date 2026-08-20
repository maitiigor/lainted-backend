<?php

namespace App\Http\Requests\Api\V1\Users;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ConfirmPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = PhoneNumber::normalize($this->input('phone'));

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid Nigerian phone number.',
            ]);
        }

        $this->merge(['phone' => $normalized]);
    }
}
