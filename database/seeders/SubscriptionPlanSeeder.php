<?php

namespace Database\Seeders;

use App\Domain\Payments\Models\SubscriptionPlan;
use App\Support\Enums\SubscriptionPlanCode;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => SubscriptionPlanCode::Free,
                'name' => 'Free',
                'price' => 0,
                'max_active_listings' => 10,
                'features' => ['basic_analytics', 'standard_rank'],
            ],
            [
                'code' => SubscriptionPlanCode::Pro,
                'name' => 'Pro',
                'price' => 1500,
                'max_active_listings' => null,
                'features' => ['full_analytics', 'priority_rank', 'flash_sales', 'discount_codes'],
            ],
            [
                'code' => SubscriptionPlanCode::Elite,
                'name' => 'Elite',
                'price' => 3500,
                'max_active_listings' => null,
                'features' => ['full_analytics', 'priority_rank', 'flash_sales', 'discount_codes', 'featured_storefront', 'bulk_listing', 'dedicated_support'],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['code' => $plan['code']],
                [
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'max_active_listings' => $plan['max_active_listings'],
                    'features' => $plan['features'],
                    'is_active' => true,
                ]
            );
        }
    }
}
