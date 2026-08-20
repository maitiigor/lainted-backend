<?php

namespace App\Http\Requests\Api\V1\Listings;

use App\Support\Enums\SellerType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreListingRequest extends FormRequest
{
    /**
     * A buyer (seller_type=none, the signup default - see AuthService)
     * has no seller role and no business setting listings up for sale.
     * Every other Listing action (update/publish/destroy/photos) is
     * already gated by ListingController::authorizeOwner() checking
     * seller_id, which a buyer could never satisfy once creation itself
     * is blocked here - this is the one real gap in the chain.
     */
    public function authorize(): bool
    {
        return $this->user()?->seller_type !== SellerType::None;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Set up a seller profile before creating a listing.');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'condition_grade' => ['required', 'in:A++,A+,A,B,C,D,X'],
            'fault_description' => ['nullable', 'string', 'max:1000', 'required_if:condition_grade,D,X'],
            'size' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'minimum_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'lga_id' => ['required', 'exists:lgas,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_bale' => ['sometimes', 'boolean'],
            'bale_type' => ['nullable', 'in:premium,mixed,economy,sorted', 'required_if:is_bale,true'],
            'bale_item_count' => ['nullable', 'integer', 'min:1', 'required_if:is_bale,true'],
            'bale_origin_country' => ['nullable', 'string', 'max:100', 'required_if:is_bale,true'],
            'is_wardrobe_cleanout' => ['sometimes', 'boolean'],
        ];
    }
}
