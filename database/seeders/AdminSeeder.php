<?php

namespace Database\Seeders;

use App\Domain\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Filament's default login form authenticates against `email`, so the
     * admin user needs one even though `email` is otherwise an optional
     * field for normal (mobile) users.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@lainted.ng');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Lainted Admin'),
                'phone' => env('ADMIN_PHONE', '+2340000000000'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
                'profile_completed_at' => now(),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
