<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Listings\Models\Listing;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Orders\Services\OrderService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Orders\StoreDeliveryQuoteRequest;
use App\Support\Enums\DeliveryTier;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Live-preview only - checkout_screen.dart calls this so the buyer sees
 * a real fee before submitting, but OrderService::buildOrder() always
 * re-quotes fresh at actual checkout time regardless of what this
 * returned. Reuses OrderService::resolveDeliveryFee() so the fallback
 * logic only lives in one place.
 */
class DeliveryQuoteController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function store(StoreDeliveryQuoteRequest $request): JsonResponse
    {
        $listing = Listing::where('uuid', $request->validated('listing_id'))->firstOrFail();
        $address = DeliveryAddress::where('uuid', $request->validated('delivery_address_id'))->firstOrFail();
        $tier = DeliveryTier::from($request->validated('delivery_tier'));

        $fee = $this->orders->resolveDeliveryFee($listing->seller, $address, $tier, (float) $listing->price);

        return ApiResponse::success(['fee' => $fee]);
    }
}
