<?php

namespace App\Console\Commands;

use App\Domain\Users\Services\AccountDeletionService;
use Illuminate\Console\Command;

class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Finalizes self-service account deletion once the 30-day recovery window has passed - forfeits the wallet and soft-deletes the account';

    public function handle(AccountDeletionService $deletion): void
    {
        $deletion->purgeExpired();
    }
}
