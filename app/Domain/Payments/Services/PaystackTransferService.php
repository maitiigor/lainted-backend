<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\BankAccount;
use App\Domain\Payments\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hand-rolled Http:: calls for Paystack's Transfer-side API, mirroring
 * PaystackService's exact pattern - kept as a separate class since
 * PaystackService only ever dealt with charges (single responsibility),
 * not because the underlying integration style differs at all.
 */
class PaystackTransferService
{
    public function listBanks(): array
    {
        return Cache::remember('paystack:banks', now()->addDay(), function () {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
                ->get('/bank', ['currency' => 'NGN', 'type' => 'nuban']);

            if (! $response->successful() || ! $response->json('status')) {
                throw new PaymentException('Could not load the list of banks. Please try again.');
            }

            return $response->json('data');
        });
    }

    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
            ->get('/bank/resolve', ['account_number' => $accountNumber, 'bank_code' => $bankCode]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new PaymentException('Could not verify this account number.');
        }

        return [
            'account_number' => $response->json('data.account_number'),
            'account_name' => $response->json('data.account_name'),
        ];
    }

    public function createRecipient(BankAccount $account): string
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
            ->post('/transferrecipient', [
                'type' => 'nuban',
                'name' => $account->account_name,
                'account_number' => $account->account_number_encrypted,
                'bank_code' => $account->bank_code,
                'currency' => 'NGN',
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack createRecipient failed', [
                'bank_account_id' => $account->id,
                'body' => $response->body(),
            ]);

            throw new PaymentException('Could not set up this bank account for withdrawal.');
        }

        $recipientCode = $response->json('data.recipient_code');
        $account->update(['paystack_recipient_code' => $recipientCode]);

        return $recipientCode;
    }

    /**
     * Both `success` (test mode, always) and `otp` (live mode, pending
     * PIN/OTP finalization) are valid non-error outcomes here - only an
     * unsuccessful HTTP response is a hard failure. The OTP-finalization
     * endpoint itself is untestable against this project's sk_test_ key
     * and out of scope; this just must not crash if it's ever hit.
     */
    public function transfer(Transaction $transaction, BankAccount $account): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(rtrim(config('services.paystack.payment_url'), '/'))
            ->post('/transfer', [
                'source' => 'balance',
                'amount' => (int) round(((float) $transaction->amount) * 100),
                'recipient' => $account->paystack_recipient_code,
                'reference' => $transaction->gateway_reference,
                'reason' => 'Lainted wallet withdrawal',
            ]);

        if (! $response->successful()) {
            Log::warning('Paystack transfer failed', [
                'transaction_uuid' => $transaction->uuid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentException('Could not process this withdrawal. Please try again.');
        }

        return $response->json('data');
    }
}
