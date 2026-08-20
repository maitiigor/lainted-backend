<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Domain\Payments\Models\Transaction;
use App\Domain\Payments\Services\PaystackService;
use App\Domain\Payments\Services\SubscriptionPaymentService;
use App\Domain\Payments\Services\WithdrawalService;
use App\Http\Controllers\Api\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly SubscriptionPaymentService $subscriptionPayments,
        private readonly WithdrawalService $withdrawals,
    ) {}

    /**
     * Source-of-truth safety net alongside the mobile app's own post-
     * payment confirm call - PaystackService::verify() is idempotent, so
     * whichever of the two lands first does the real work and the second
     * is a no-op. Always responds 200 so Paystack doesn't retry forever,
     * even when verification itself fails - caught broadly (not just
     * PaymentException) since an unrecognized reference throws a plain
     * ModelNotFoundException, and that must never surface as a 404/500
     * "delivery failure" to Paystack either.
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');
        $expected = hash_hmac('sha512', $request->getContent(), (string) config('services.paystack.secret_key'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            abort(401);
        }

        $event = (string) $request->input('event');

        if ($event === 'charge.success') {
            $reference = (string) $request->input('data.reference');

            try {
                // Route by which FK the Transaction actually has - a
                // minimal, additive branch rather than a second webhook
                // endpoint. Order-linked references hit the exact same
                // unchanged call as before.
                $transaction = Transaction::where('gateway_reference', $reference)->first();

                if ($transaction?->subscription_id !== null) {
                    $this->subscriptionPayments->verify($reference);
                } else {
                    $this->paystack->verify($reference);
                }
            } catch (Throwable $e) {
                Log::warning('Paystack webhook verify failed', ['error' => $e->getMessage()]);
            }
        }

        if (str_starts_with($event, 'transfer.')) {
            try {
                $this->withdrawals->handleTransferWebhook(
                    (string) $request->input('data.reference'),
                    $event,
                    (array) $request->input('data'),
                );
            } catch (Throwable $e) {
                Log::warning('Paystack transfer webhook failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
