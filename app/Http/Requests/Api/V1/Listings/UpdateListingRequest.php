<?php

namespace App\Http\Requests\Api\V1\Listings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'condition_grade' => ['sometimes', 'in:A++,A+,A,B,C,D,X'],
            'fault_description' => ['nullable', 'string', 'max:1000', 'required_if:condition_grade,D,X'],
            'size' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'minimum_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'lga_id' => ['sometimes', 'exists:lgas,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_bale' => ['sometimes', 'boolean'],
            'bale_type' => ['nullable', 'in:premium,mixed,economy,sorted', 'required_if:is_bale,true'],
            'bale_item_count' => ['nullable', 'integer', 'min:1', 'required_if:is_bale,true'],
            'bale_origin_country' => ['nullable', 'string', 'max:100', 'required_if:is_bale,true'],
            'is_wardrobe_cleanout' => ['sometimes', 'boolean'],
        ];
    }
}
