<?php

namespace App\Http\Requests\Api\V1\Trust;

use Illuminate\Foundation\Http\FormRequest;

class UploadDisputePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:8192'],
        ];
    }
}
