<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Domain\Users\Exceptions\BlockException;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\BlockService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Users\BlockUserRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedUserController extends Controller
{
    public function __construct(private readonly BlockService $blocks) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->blockedUsers()->get());
    }

    public function store(BlockUserRequest $request): JsonResponse
    {
        $target = User::where('uuid', $request->validated('user_id'))->firstOrFail();

        try {
            $this->blocks->block($request->user(), $target, $request->validated('reason'));
        } catch (BlockException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(null, 'User blocked.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->blocks->unblock($request->user(), $user);

        return ApiResponse::success(null, 'User unblocked.');
    }
}
