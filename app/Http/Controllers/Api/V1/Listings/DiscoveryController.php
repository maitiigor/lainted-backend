<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Domain\Listings\Services\DiscoveryService;
use App\Http\Controllers\Api\V1\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function __construct(private readonly DiscoveryService $discovery) {}

    public function feed(Request $request): JsonResponse
    {
        $filters = $request->only([
            'q', 'category_id', 'item_type', 'size', 'brand',
            'price_min', 'price_max', 'min_grade', 'lga_id', 'seller_type',
            'is_wardrobe_cleanout', 'near_you', 'sort',
        ]);

        return ApiResponse::success($this->discovery->feed($request->user(), $filters));
    }
}
