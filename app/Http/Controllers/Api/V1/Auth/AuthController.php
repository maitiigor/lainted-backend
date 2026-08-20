<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Users\Exceptions\AuthException;
use App\Domain\Users\Exceptions\OtpException;
use App\Domain\Users\Services\AuthService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $result = $this->authService->requestOtp($request->validated('phone'));

        return ApiResponse::success($result, 'OTP sent.');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->verifyOtp(
                $request->validated('phone'),
                $request->validated('code'),
            );
        } catch (OtpException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'needs_profile_setup' => $result['needs_profile_setup'],
        ], 'Verified.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithPassword(
                $request->validated('phone'),
                $request->validated('password'),
            );
        } catch (AuthException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($result, 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out');
    }
}
