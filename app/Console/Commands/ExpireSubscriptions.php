<?php

namespace App\Console\Commands;

use App\Domain\Payments\Models\Subscription;
use App\Domain\Payments\Models\SubscriptionPlan;
use App\Support\Enums\SubscriptionPlanCode;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Downgrades a lapsed paid subscription to Free once its 30-day period ends - without this, a one-time Pro/Elite payment would grant that tier forever';

    public function handle(): void
    {
        $freePlan = SubscriptionPlan::where('code', SubscriptionPlanCode::Free)->first();

        Subscription::where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<=', now())
            ->each(function (Subscription $subscription) use ($freePlan) {
                $subscription->update(['status' => SubscriptionStatus::Expired]);

                if ($freePlan) {
                    Subscription::create([
                        'user_id' => $subscription->user_id,
                        'subscription_plan_id' => $freePlan->id,
                        'status' => SubscriptionStatus::Active,
                        'starts_at' => now(),
                    ]);
                }
            });
    }
}
