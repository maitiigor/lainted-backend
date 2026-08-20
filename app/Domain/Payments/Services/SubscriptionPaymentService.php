<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Subscription;
use App\Domain\Payments\Models\SubscriptionPlan;
use App\Domain\Payments\Models\Transaction;
use App\Domain\Users\Models\User;
use App\Support\Enums\SubscriptionStatus;
use App\Support\Enums\TransactionStatus;
use App\Support\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * A separate service from PaystackService, not an extension of it - the
 * same "new payment context gets its own class" precedent
 * PaystackTransferService already set for withdrawal payouts, rather
 * than touching PaystackService's live order-checkout code to add a
 * second concern. One-time "buy 30 days of Pro/Elite" charges, not
 * Paystack's native recurring-billing API - no product-doc mandate for
 * true recurring billing exists, and this codebase has never touched
 * Paystack's /plan or /subscription endpoints (card tokenization,
 * subscription-lifecycle or invoice webhooks) - see the implementation
 * plan for the full reasoning.
 */
class SubscriptionPaymentService
{
    private const CALLBACK_URL = 'https://lainted.app/payment/callback';

    public function initialize(User $user, SubscriptionPlan $plan, string $email): array
    {
        return DB::transaction(function () use ($user, $plan, $email) {
            $existing = $user->activeSubscription();
            if ($existing?->subscription_plan_id === $plan->id) {
                throw new PaymentException("You're already on this plan.");
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::Pending,
                'starts_at' => now(), // placeholder, overwritten once payment verifies
            ]);

            $reference = 'lnt_'.Str::uuid();

            Transaction::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'type' => TransactionType::SubscriptionCharge,
                'amount' => $plan->price,
                'gateway_reference' => $reference,
                'status' => TransactionStatus::Pending,
            ]);

            $response = Http::withToken(config('services.paystack.secret_key'))
                ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
                ->post('/transaction/initialize', [
                    'email' => $email,
                    'amount' => (int) round(((float) $plan->price) * 100),
                    'reference' => $reference,
                    'callback_url' => self::CALLBACK_URL,
                    'metadata' => ['subscription_uuid' => $subscription->uuid],
                ]);

            if (! $response->successful() || ! $response->json('status')) {
                Log::warning('Paystack subscription initialize failed', [
                    'subscription_uuid' => $subscription->uuid,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PaymentException('Could not start payment. Please try again.');
            }

            return [
                'subscription' => $subscription,
                'authorization_url' => $response->json('data.authorization_url'),
                'reference' => $reference,
            ];
        });
    }

    /**
     * Idempotent - the mobile app's own post-payment confirm call and the
     * Paystack webhook both converge here and may race, same as
     * PaystackService::verify().
     */
    public function verify(string $reference): Subscription
    {
        $transaction = Transaction::where('gateway_reference', $reference)->firstOrFail();

        if ($transaction->status === TransactionStatus::Successful) {
            return $transaction->subscription;
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
            ->get('/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            $transaction->update(['status' => TransactionStatus::Failed, 'gateway_response' => $response->json()]);

            throw new PaymentException('Payment could not be verified.');
        }

        // Anti-tampering: never trust the client-reported amount - compare
        // Paystack's own verified amount (kobo) against what we charged.
        // Same reasoning as PaystackService::verify().
        $expectedKobo = (int) round(((float) $transaction->amount) * 100);

        if ((int) $response->json('data.amount') !== $expectedKobo) {
            $transaction->update(['status' => TransactionStatus::Failed, 'gateway_response' => $response->json('data')]);

            throw new PaymentException('Payment amount mismatch.');
        }

        return DB::transaction(function () use ($transaction, $response) {
            $transaction->update(['status' => TransactionStatus::Successful, 'gateway_response' => $response->json('data')]);

            $user = $transaction->user;

            // Supersede whatever was active (Free, or a previous paid
            // tier) so exactly one Subscription row reads Active - never
            // two, which would make User::activeSubscription()'s
            // latest()-pick accidental rather than correct.
            $previous = $user->activeSubscription();
            $previous?->update(['status' => SubscriptionStatus::Cancelled, 'ends_at' => now()]);

            $transaction->subscription->update([
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
            ]);

            return $transaction->subscription->fresh();
        });
    }
}
