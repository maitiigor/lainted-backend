<?php

namespace App\Http\Requests\Api\V1\Bundles;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Item pool is fixed at creation - only these are mutable.
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'bundle_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
