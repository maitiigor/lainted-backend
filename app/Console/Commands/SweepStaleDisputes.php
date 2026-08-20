<?php

namespace App\Console\Commands;

use App\Domain\Trust\Models\Dispute;
use App\Jobs\SendDisputeEventPush;
use App\Support\Enums\DisputePushEvent;
use App\Support\Enums\DisputeStatus;
use Illuminate\Console\Command;

class SweepStaleDisputes extends Command
{
    protected $signature = 'disputes:sweep-stale';

    protected $description = 'Escalate a dispute to admin review if the seller never responded within their 48h self-resolution window';

    public function handle(): void
    {
        Dispute::where('status', DisputeStatus::Open)
            ->where('self_resolve_expires_at', '<=', now())
            ->each(function (Dispute $dispute) {
                $dispute->update(['status' => DisputeStatus::AdminReview]);
                SendDisputeEventPush::dispatch($dispute, DisputePushEvent::Escalated);
            });
    }
}
