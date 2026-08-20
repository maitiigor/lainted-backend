<?php

namespace App\Domain\Users\Services;

use App\Domain\Trust\Services\UserModerationService;
use App\Domain\Users\Models\User;
use App\Support\Enums\AccountStatus;

/**
 * Doc: "Delete account (30-day recovery window, wallet forfeited after)".
 * Deliberately doesn't Eloquent-soft-delete until the window actually
 * expires - see the class-level note on why in the Phase 10 plan: an
 * immediate soft-delete would hide the user from AuthService's own
 * login queries, making self-service recovery impossible without first
 * rewriting login to use withTrashed() everywhere. Keeping the row
 * fully normal (just flagged) until purgeExpired() runs means login
 * keeps working exactly as it does today.
 */
class AccountDeletionService
{
    private const RECOVERY_DAYS = 30;

    public function __construct(private readonly UserModerationService $moderation) {}

    public function requestDeletion(User $user): void
    {
        // forceFill(), not update() - deletion_requested_at isn't in
        // $fillable (system-computed only), so a plain update() would
        // silently drop it while account_status (which IS fillable)
        // still saved, leaving an inconsistent half-applied state.
        $user->forceFill([
            'account_status' => AccountStatus::PendingDeletion,
            'deletion_requested_at' => now(),
        ])->save();
    }

    public function cancelDeletion(User $user): void
    {
        $user->forceFill([
            'account_status' => AccountStatus::Active,
            'deletion_requested_at' => null,
        ])->save();
    }

    public function purgeExpired(): void
    {
        User::where('account_status', AccountStatus::PendingDeletion)
            ->where('deletion_requested_at', '<=', now()->subDays(self::RECOVERY_DAYS))
            ->each(function (User $user) {
                $this->moderation->forfeitWallet($user);
                $user->delete();
            });
    }
}
