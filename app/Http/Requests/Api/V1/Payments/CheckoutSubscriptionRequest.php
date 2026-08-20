<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Support\Enums\SellerType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutSubscriptionRequest extends FormRequest
{
    /**
     * Doc: "No subscription - casual sellers pay only when they earn."
     * Mobile already hides the Subscription menu/screen from non-
     * business sellers, but never trust a hidden UI element as the only
     * gate - same instinct as StoreListingRequest::authorize().
     */
    public function authorize(): bool
    {
        return $this->user()?->seller_type === SellerType::Business;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Subscriptions are only available to business sellers.');
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ];
    }
}
