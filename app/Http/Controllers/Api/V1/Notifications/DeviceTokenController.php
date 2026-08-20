<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Domain\Notifications\Models\DeviceToken;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Notifications\StoreDeviceTokenRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        // Keyed by token, not user - a device re-registering while a
        // different user is now logged in on it must reassign ownership,
        // not create a duplicate row (token has a unique constraint).
        DeviceToken::updateOrCreate(
            ['token' => $request->validated('token')],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->validated('platform'),
                'last_used_at' => now(),
            ],
        );

        return ApiResponse::success(null, 'Device registered.');
    }

    public function destroy(Request $request): JsonResponse
    {
        DeviceToken::where('token', $request->string('token'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return ApiResponse::success(null, 'Device unregistered.');
    }
}
