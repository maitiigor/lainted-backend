<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\SubscriptionPlan;
use App\Domain\Payments\Services\SubscriptionPaymentService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Payments\CheckoutSubscriptionRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pro/Elite subscriptions are one-time 30-day charges (not Paystack's
 * native recurring-billing API - see SubscriptionPaymentService's own
 * doc comment for why), reusing the exact initialize/verify shape
 * OrderService+PaystackService already use for order checkout.
 */
class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        return ApiResponse::success(SubscriptionPlan::where('is_active', true)->orderBy('price')->get());
    }

    public function checkout(CheckoutSubscriptionRequest $request, SubscriptionPaymentService $payments): JsonResponse
    {
        $user = $request->user();

        if (! $user->email) {
            return ApiResponse::error('Add an email address to your profile before subscribing.', null, 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->validated('subscription_plan_id'));

        try {
            $result = $payments->initialize($user, $plan, $user->email);
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($result);
    }

    public function confirmPayment(Request $request, SubscriptionPaymentService $payments): JsonResponse
    {
        try {
            $payments->verify($request->string('reference'));
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        $user = $request->user()->fresh();
        $user->setRelation('activeSubscription', $user->activeSubscription()?->load('subscriptionPlan'));

        return ApiResponse::success($user, 'Subscription updated.');
    }
}
