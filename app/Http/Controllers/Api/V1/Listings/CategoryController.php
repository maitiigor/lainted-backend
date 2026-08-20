<?php

namespace App\Http\Controllers\Api\V1\Listings;

use App\Domain\Listings\Models\Category;
use App\Http\Controllers\Api\V1\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        // Only Fashion is active for V1 - without this filter every
        // seeded V2-V4 tree (Electronics, Household, etc.) would leak to
        // the client even though those categories aren't launched yet.
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'uuid', 'parent_id', 'item_type', 'name', 'sort_order']);

        return ApiResponse::success($categories);
    }
}
