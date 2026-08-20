<?php

namespace App\Console\Commands;

use App\Domain\Users\Models\User;
use App\Support\Enums\AccountStatus;
use Illuminate\Console\Command;

class LiftExpiredSuspensions extends Command
{
    protected $signature = 'accounts:lift-expired-suspensions';

    protected $description = 'Flip a Suspended account back to Active once its suspended_until has passed';

    public function handle(): void
    {
        User::where('account_status', AccountStatus::Suspended)
            ->where('suspended_until', '<=', now())
            ->each(fn (User $user) => $user->update([
                'account_status' => AccountStatus::Active,
                'suspended_until' => null,
            ]));
    }
}
