<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\BankAccount;
use App\Domain\Payments\Models\Transaction;
use App\Domain\Payments\Models\Wallet;
use App\Domain\Payments\Models\WalletTransaction;
use App\Domain\Users\Models\User;
use App\Support\Enums\LedgerDirection;
use App\Support\Enums\TransactionStatus;
use App\Support\Enums\TransactionType;
use App\Support\Enums\WalletTransactionSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WithdrawalService
{
    private const MIN_WITHDRAWAL_AMOUNT = 1000.00;

    public function __construct(private readonly PaystackTransferService $transfers) {}

    /**
     * A /transfer call moves real money, unlike checkout's initialize()
     * call - wrapping it in the same rolled-back-on-failure transaction
     * as the debit would risk telling Paystack to pay out with no
     * corresponding local debit if the transaction later failed for an
     * unrelated reason. So the debit commits first, and any failure is
     * reversed via a compensating ledger entry instead.
     */
    public function request(User $user, BankAccount $account, float $amount): Transaction
    {
        if ($account->user_id !== $user->id) {
            throw new PaymentException('This bank account does not belong to you.');
        }

        if ($amount < self::MIN_WITHDRAWAL_AMOUNT) {
            throw new PaymentException('Minimum withdrawal is '.number_format(self::MIN_WITHDRAWAL_AMOUNT).'.');
        }

        $transaction = DB::transaction(function () use ($user, $account, $amount) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ((float) $wallet->balance < $amount) {
                throw new PaymentException('Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $amount);
            $reference = 'wd_'.Str::uuid();

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Withdrawal,
                'amount' => $amount,
                'gateway_reference' => $reference,
                'status' => TransactionStatus::Pending,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'bank_account_id' => $account->id,
                'type' => LedgerDirection::Debit,
                'source' => WalletTransactionSource::Withdrawal,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'reference' => $reference,
            ]);

            return $transaction;
        });

        try {
            $this->ensureRecipient($account);
            $data = $this->transfers->transfer($transaction, $account);

            $transaction->update([
                'status' => ($data['status'] ?? null) === 'success' ? TransactionStatus::Successful : TransactionStatus::Pending,
                'gateway_response' => $data,
            ]);
        } catch (Throwable $e) {
            $this->reverse($transaction, ['error' => $e->getMessage()]);
        }

        return $transaction->refresh();
    }

    public function ensureRecipient(BankAccount $account): string
    {
        if ($account->paystack_recipient_code !== null) {
            return $account->paystack_recipient_code;
        }

        return $this->transfers->createRecipient($account);
    }

    /**
     * Paystack's transfer.success/transfer.failed webhooks are the source
     * of truth for a live-mode OTP-pending transfer; in test mode the
     * synchronous response in request() already resolves it, making this
     * redundant-but-safe (idempotent via reverse()'s status guard).
     */
    public function handleTransferWebhook(string $reference, string $event, array $payload): void
    {
        $transaction = Transaction::where('gateway_reference', $reference)->first();
        if ($transaction === null) {
            return;
        }

        if ($event === 'transfer.success') {
            if ($transaction->status === TransactionStatus::Pending) {
                $transaction->update(['status' => TransactionStatus::Successful, 'gateway_response' => $payload]);
            }

            return;
        }

        if (in_array($event, ['transfer.failed', 'transfer.reversed'], true)) {
            $this->reverse($transaction, $payload);
        }
    }

    /**
     * Never deletes/mutates the original debit row - ledger integrity
     * means a failed withdrawal is undone with a new, visible credit
     * entry, not a rewrite of history.
     */
    private function reverse(Transaction $transaction, array $gatewayResponse): void
    {
        DB::transaction(function () use ($transaction, $gatewayResponse) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [TransactionStatus::Failed, TransactionStatus::Reversed], true)) {
                return;
            }

            $original = WalletTransaction::where('reference', $locked->gateway_reference)->firstOrFail();
            $wallet = Wallet::whereKey($original->wallet_id)->lockForUpdate()->firstOrFail();
            $wallet->increment('balance', $locked->amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'bank_account_id' => $original->bank_account_id,
                'type' => LedgerDirection::Credit,
                'source' => WalletTransactionSource::Withdrawal,
                'amount' => $locked->amount,
                'balance_after' => $wallet->balance,
                'reference' => 'wt_reverse_'.Str::uuid(),
            ]);

            $locked->update(['status' => TransactionStatus::Reversed, 'gateway_response' => $gatewayResponse]);
        });
    }
}
