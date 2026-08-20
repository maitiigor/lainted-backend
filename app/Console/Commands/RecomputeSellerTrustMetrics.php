<?php

namespace App\Console\Commands;

use App\Domain\Trust\Services\TrustMetricsService;
use App\Domain\Users\Models\User;
use Illuminate\Console\Command;

class RecomputeSellerTrustMetrics extends Command
{
    protected $signature = 'sellers:recompute-trust-metrics';

    protected $description = 'Daily backstop recompute of rating/total_sales/trust_score/response_rate for every user - catches response_rate drift from chat activity that has no eager recompute hook';

    public function handle(TrustMetricsService $trustMetrics): void
    {
        User::query()->each(fn (User $user) => $trustMetrics->recompute($user));
    }
}
