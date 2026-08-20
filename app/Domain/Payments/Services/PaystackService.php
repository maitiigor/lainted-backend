<?php

namespace App\Domain\Payments\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderService;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Transaction;
use App\Support\Enums\TransactionStatus;
use App\Support\Enums\TransactionType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Hand-rolled Http:: calls, mirroring AuthService::sendSms()'s Termii
 * integration - no Paystack SDK package, matching this project's own
 * prior "no laravel-paystack package" decision.
 *
 * Never constructor-injects OrderService: OrderService constructor-
 * injects this class (to initialize payment during checkout), so the
 * reverse dependency would be circular. verify() resolves OrderService
 * lazily instead, the same lazy-resolution pattern PushNotificationService
 * already uses for the Firebase SDK.
 */
class PaystackService
{
    /**
     * The mobile app's flutter_paystack_plus widget watches its WebView's
     * navigation for a URL containing this exact string to know the
     * checkout flow has ended (it also independently detects Paystack's
     * own hosted "close" page, but that isn't guaranteed to fire in every
     * case) - must match `paystackCallbackUrl` in the mobile app's
     * ApiConstants exactly. Doesn't need to resolve to a real page: the
     * WebView only ever checks whether the URL contains this string.
     */
    private const CALLBACK_URL = 'https://lainted.app/payment/callback';

    public function initialize(Order $order, string $email): array
    {
        $reference = 'lnt_'.Str::uuid();

        Transaction::create([
            'user_id' => $order->buyer_id,
            'order_id' => $order->id,
            'type' => TransactionType::Payment,
            'amount' => $order->total_amount,
            'gateway_reference' => $reference,
            'status' => TransactionStatus::Pending,
        ]);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
            ->post('/transaction/initialize', [
                'email' => $email,
                'amount' => (int) round(((float) $order->total_amount) * 100),
                'reference' => $reference,
                'callback_url' => self::CALLBACK_URL,
                'metadata' => ['order_uuid' => $order->uuid],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack initialize failed', [
                'order_uuid' => $order->uuid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentException('Could not start payment. Please try again.');
        }

        return [
            'authorization_url' => $response->json('data.authorization_url'),
            'access_code' => $response->json('data.access_code'),
            'reference' => $reference,
        ];
    }

    /**
     * Idempotent - the mobile app's own post-payment confirm call and the
     * Paystack webhook both converge here and may race; whichever lands
     * second just returns the already-verified row instead of
     * re-processing (and never double-fires OrderService::confirmPayment).
     */
    public function verify(string $reference): Transaction
    {
        $transaction = Transaction::where('gateway_reference', $reference)->firstOrFail();

        if ($transaction->status === TransactionStatus::Successful) {
            return $transaction;
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
        // The mobile widget's `onSuccess` fires purely because its WebView
        // navigated to the callback URL, with zero server-side proof - see
        // the authorizationUrl-mode research behind this design.
        $expectedKobo = (int) round(((float) $transaction->amount) * 100);

        if ((int) $response->json('data.amount') !== $expectedKobo) {
            $transaction->update(['status' => TransactionStatus::Failed, 'gateway_response' => $response->json('data')]);

            throw new PaymentException('Payment amount mismatch.');
        }

        $transaction->update(['status' => TransactionStatus::Successful, 'gateway_response' => $response->json('data')]);

        app(OrderService::class)->confirmPayment($transaction->order);

        return $transaction;
    }
}
