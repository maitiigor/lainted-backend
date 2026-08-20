<?php

namespace App\Http\Requests\Api\V1\Users;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,uuid'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
