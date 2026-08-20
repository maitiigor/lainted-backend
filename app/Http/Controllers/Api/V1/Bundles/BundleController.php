<?php

namespace App\Http\Controllers\Api\V1\Bundles;

use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Bundle;
use App\Domain\Listings\Services\BundleService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Bundles\StoreBundleRequest;
use App\Http\Requests\Api\V1\Bundles\UpdateBundleRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function __construct(private readonly BundleService $bundles) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $request->user()->bundles()->with('listings')->latest()->get()
        );
    }

    public function store(StoreBundleRequest $request): JsonResponse
    {
        try {
            $bundle = $this->bundles->create($request->user(), $request->validated());
        } catch (ListingException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($bundle->load('listings'), 'Bundle created.', 201);
    }

    public function update(UpdateBundleRequest $request, Bundle $bundle): JsonResponse
    {
        $this->authorizeOwner($request, $bundle);

        $bundle->update($request->validated());

        return ApiResponse::success($bundle->load('listings'), 'Bundle updated.');
    }

    public function destroy(Request $request, Bundle $bundle): JsonResponse
    {
        $this->authorizeOwner($request, $bundle);
        $bundle->delete();

        return ApiResponse::success(null, 'Bundle deleted.');
    }

    private function authorizeOwner(Request $request, Bundle $bundle): void
    {
        if ($bundle->seller_id !== $request->user()->id) {
            abort(403, 'You do not own this bundle.');
        }
    }
}
