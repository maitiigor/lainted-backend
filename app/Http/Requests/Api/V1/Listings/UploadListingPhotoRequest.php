<?php

namespace App\Http\Requests\Api\V1\Listings;

use Illuminate\Foundation\Http\FormRequest;

class UploadListingPhotoRequest extends FormRequest
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
