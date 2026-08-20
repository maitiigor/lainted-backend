<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Domain\Listings\Services\WardrobeStatsService;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Payments\Models\Subscription;
use App\Domain\Payments\Models\SubscriptionPlan;
use App\Domain\Users\Exceptions\AuthException;
use App\Domain\Users\Exceptions\OtpException;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\AccountDeletionService;
use App\Domain\Users\Services\AuthService;
use App\Domain\Users\Services\CloudinaryService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Users\ConfirmPhoneChangeRequest;
use App\Http\Requests\Api\V1\Users\RequestPhoneChangeOtpRequest;
use App\Http\Requests\Api\V1\Users\UpdateProfileRequest;
use App\Support\Enums\SellerType;
use App\Support\Enums\SubscriptionPlanCode;
use App\Support\Enums\SubscriptionStatus;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    /**
     * Doc: "Delete account (30-day recovery window, wallet forfeited
     * after)". Only flags account_status - doesn't Eloquent-soft-delete
     * yet, so login keeps working unchanged and the user can self-
     * recover by logging back in (see AccountDeletionService).
     */
    public function deleteAccount(Request $request, AccountDeletionService $deletion): JsonResponse
    {
        $deletion->requestDeletion($request->user());

        return ApiResponse::success(null, 'Account scheduled for deletion in 30 days.');
    }

    public function restoreAccount(Request $request, AccountDeletionService $deletion): JsonResponse
    {
        $deletion->cancelDeletion($request->user());

        return ApiResponse::success($request->user()->fresh(), 'Account restored.');
    }

    public function requestPhoneChangeOtp(RequestPhoneChangeOtpRequest $request, AuthService $auth): JsonResponse
    {
        $auth->requestOtp($request->validated('phone'));

        return ApiResponse::success(null, 'OTP sent.');
    }

    public function confirmPhoneChange(ConfirmPhoneChangeRequest $request, AuthService $auth): JsonResponse
    {
        try {
            $user = $auth->changePhone(
                $request->user(),
                $request->validated('phone'),
                $request->validated('code'),
            );
        } catch (OtpException|AuthException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($user, 'Phone number updated.');
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->withSubscription($request->user()->load('pickupAddress.lga')));
    }

    /**
     * activeSubscription() is a plain computed method (latest Active
     * row), not a declared Eloquent relation, so it can't join a
     * with()/load() call - attach it as a relation manually so it still
     * serializes as `active_subscription` (Eloquent snake-cases relation
     * keys on output regardless of how they were set).
     */
    private function withSubscription(User $user): User
    {
        $user->setRelation('activeSubscription', $user->activeSubscription()?->load('subscriptionPlan'));

        return $user;
    }

    /**
     * Deliberately doesn't return the raw model like show() does - that
     * would leak phone/email/verification_number_encrypted to whoever's
     * viewing, since Eloquent's default serialization has no concept of
     * "this field is only safe for the owner." Hand-picked public subset
     * only.
     */
    public function showPublic(User $user): JsonResponse
    {
        return ApiResponse::success([
            'uuid' => $user->uuid,
            'name' => $user->name,
            'photo_path' => $user->photo_path,
            'seller_type' => $user->seller_type,
            'is_verified' => $user->is_verified,
            'rating' => $user->rating,
            'total_sales' => $user->total_sales,
            'trust_score' => $user->trust_score,
            'seller_rank' => $user->seller_rank,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * "My Wardrobe" stats - a separate endpoint rather than folding into
     * show(), since /me is fetched on nearly every screen and this needs
     * an Orders->Listings join plus a WalletTransaction sum that only one
     * screen actually needs.
     */
    public function wardrobeStats(Request $request, WardrobeStatsService $stats): JsonResponse
    {
        return ApiResponse::success($stats->forSeller($request->user()));
    }

    /**
     * A seller's saved default pickup location for real courier bookings
     * (see SendboxService) - the address itself is created via the
     * existing generic POST /delivery-addresses endpoint, this just
     * points the user at one they already own.
     */
    public function updatePickupAddress(Request $request): JsonResponse
    {
        $address = DeliveryAddress::where('uuid', $request->string('delivery_address_id'))->firstOrFail();

        if ($address->user_id !== $request->user()->id) {
            abort(404);
        }

        $request->user()->update(['pickup_address_id' => $address->id]);

        return ApiResponse::success(
            $request->user()->fresh()->load('pickupAddress.lga'),
            'Pickup address updated.',
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            // Cloudinary's cloud_name is currently disabled on the configured
            // account - using local disk storage until that's sorted out.
            // Build the URL from the actual request host (not config('app.url'))
            // so it resolves correctly whether the client is the Android
            // emulator (10.0.2.2) or the iOS Simulator (localhost) - same
            // host-alias problem as ApiConstants.baseUrl on the mobile side.
            $path = $request->file('photo')->store('profiles', 'public');
            $data['photo_path'] = $request->getSchemeAndHttpHost().'/storage/'.$path;
        }

        if (! empty($data['verification_type'])) {
            $data['verification_number_encrypted'] = $data['verification_number'];
        }

        $wasNotBusinessBefore = $user->seller_type !== SellerType::Business;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'photo_path' => $data['photo_path'] ?? $user->photo_path,
            'lga_id' => $data['lga_id'] ?? $user->lga_id,
            'landmark' => $data['landmark'] ?? $user->landmark,
            'bio' => $data['bio'] ?? $user->bio,
            'seller_type' => $data['seller_type'],
            'verification_type' => $data['verification_type'] ?? $user->verification_type,
            'verification_number_encrypted' => $data['verification_number_encrypted'] ?? $user->verification_number_encrypted,
            'accepted_seller_agreement_at' => $user->accepted_seller_agreement_at ?? now(),
            'profile_completed_at' => now(),
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);
        $user->save();

        $sellerType = SellerType::from($data['seller_type']);

        if ($sellerType !== SellerType::None) {
            $user->syncRoles([$sellerType === SellerType::Business ? 'business_seller' : 'personal_seller', 'buyer']);
        }

        if ($sellerType === SellerType::Business && $wasNotBusinessBefore) {
            $freePlan = SubscriptionPlan::where('code', SubscriptionPlanCode::Free)->first();

            if ($freePlan && ! $user->subscriptions()->where('status', SubscriptionStatus::Active)->exists()) {
                Subscription::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $freePlan->id,
                    'status' => SubscriptionStatus::Active,
                    'starts_at' => now(),
                ]);
            }
        }

        $fresh = $this->withSubscription($user->fresh()->load('pickupAddress.lga'));

        return ApiResponse::success($fresh, 'Profile updated.');
    }
}
