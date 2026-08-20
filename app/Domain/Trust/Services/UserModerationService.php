<?php

namespace App\Domain\Trust\Services;

use App\Domain\Payments\Models\Wallet;
use App\Domain\Payments\Models\WalletTransaction;
use App\Domain\Trust\Models\Strike;
use App\Domain\Users\Models\User;
use App\Support\Enums\AccountStatus;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\LedgerDirection;
use App\Support\Enums\SellerRank;
use App\Support\Enums\StrikeType;
use App\Support\Enums\WalletTransactionSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reconciles the product doc's two contradictory strike-threshold
 * passages (S9: "3 strikes = suspension, 5 = permanent ban" vs S14:
 * "warning -> 7-day suspension -> permanent ban") by automating off S9's
 * strike count using S14's 7-day suspension duration, with the unused
 * AccountStatus::Warned case as the soft first step: 1-2 -> Warned,
 * 3-4 -> Suspended, 5+ -> Banned. Consequences only ever escalate - a
 * banned user stays banned regardless of further strikes.
 */
class UserModerationService
{
    public function issueStrike(
        User $user,
        StrikeType $type,
        string $reason,
        IssuedBy $issuedBy,
        ?User $admin = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): Strike {
        $strike = Strike::create([
            'user_id' => $user->id,
            'type' => $type,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'issued_by' => $issuedBy,
            'admin_id' => $admin?->id,
        ]);

        $this->applyConsequence($user->fresh());

        return $strike;
    }

    public function warn(User $user): void
    {
        if ($user->account_status === AccountStatus::Banned) {
            return;
        }

        $user->update(['account_status' => AccountStatus::Warned]);
    }

    public function suspend(User $user, int $days = 7): void
    {
        if ($user->account_status === AccountStatus::Banned) {
            return;
        }

        $user->update([
            'account_status' => AccountStatus::Suspended,
            'suspended_until' => now()->addDays($days),
        ]);
    }

    /**
     * "Banned sellers forfeit wallet balance and lose all ratings
     * permanently" - forfeiture zeroes the wallet via a ledger entry
     * (never silently, always a WalletTransaction). "Lose ratings" resets
     * only this user's own computed reputation, not the Review rows other
     * people wrote about them - those belong to the other party's history.
     */
    public function ban(User $user): void
    {
        DB::transaction(function () use ($user) {
            // forceFill(), not update() - rating/trust_score/response_rate
            // aren't in $fillable (system-computed only), so a plain
            // update() would silently drop them and leave stale reputation
            // data behind.
            $user->forceFill([
                'account_status' => AccountStatus::Banned,
                'suspended_until' => null,
                'rating' => null,
                'trust_score' => null,
                'response_rate' => null,
                'seller_rank' => SellerRank::New,
            ])->save();

            $this->forfeitWallet($user);
        });
    }

    /**
     * Public - also called by AccountDeletionService::purgeExpired() for
     * the "wallet forfeited after 30 days" step of self-service account
     * deletion, the exact same ledger primitive as the ban flow.
     */
    public function forfeitWallet(User $user): void
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
        if ($wallet === null || (float) $wallet->balance <= 0) {
            return;
        }

        $amount = (float) $wallet->balance;
        $wallet->decrement('balance', $amount);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => LedgerDirection::Debit,
            'source' => WalletTransactionSource::Forfeiture,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'reference' => 'wt_'.Str::uuid(),
        ]);
    }

    private function applyConsequence(User $user): void
    {
        if ($user->account_status === AccountStatus::Banned) {
            return;
        }

        $count = $user->strikes()->count();

        match (true) {
            $count >= 5 => $this->ban($user),
            $count >= 3 => $this->suspend($user),
            $count >= 1 => $this->warn($user),
            default => null,
        };
    }
}
