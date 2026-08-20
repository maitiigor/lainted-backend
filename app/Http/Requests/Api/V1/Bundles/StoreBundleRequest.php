<?php

namespace App\Http\Requests\Api\V1\Bundles;

use Illuminate\Foundation\Http\FormRequest;

class StoreBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'required_item_count' => ['required', 'integer', 'min:2'],
            'bundle_price' => ['required', 'numeric', 'min:0'],
            'listing_ids' => ['required', 'array', 'min:2'],
            'listing_ids.*' => ['string', 'exists:listings,uuid'],
        ];
    }
}
