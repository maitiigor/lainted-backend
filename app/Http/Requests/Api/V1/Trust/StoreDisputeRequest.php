<?php

namespace App\Http\Requests\Api\V1\Trust;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispute_type' => ['required', 'string', 'in:not_received,grade_mismatch,wrong_item,item_damaged,seller_no_show,other'],
            'buyer_statement' => ['required', 'string'],
            'buyer_photos' => ['nullable', 'array'],
            'buyer_photos.*' => ['string'],
        ];
    }
}
