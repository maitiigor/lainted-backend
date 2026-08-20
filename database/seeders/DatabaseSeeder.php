<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LgaSeeder::class,
            CategorySeeder::class,
            SubscriptionPlanSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
