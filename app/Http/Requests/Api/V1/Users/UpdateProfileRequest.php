<?php

namespace App\Http\Requests\Api\V1\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
            'photo' => ['nullable', 'image', 'max:5120'],
            'lga_id' => ['nullable', 'exists:lgas,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'seller_type' => ['required', 'in:none,personal,business'],
            'verification_type' => ['nullable', 'in:nin,bvn'],
            'verification_number' => ['nullable', 'required_with:verification_type', 'string'],
            'accepted_agreement' => ['required', 'accepted'],
            // Optional - lets a returning user skip paying for an SMS on every login.
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
    }
}
