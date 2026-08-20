<?php

namespace App\Providers;

use App\Domain\Chat\Models\Message;
use App\Domain\Listings\Models\Listing;
use App\Domain\Users\Models\User;
use App\Providers\TelescopeServiceProvider;
use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'listing' => Listing::class,
            'user' => User::class,
            'message' => Message::class,
        ]);

        // Two independent caps rather than one combined phone+IP key: this
        // stops both a single IP fanning out OTP requests across many phones,
        // and a phone being targeted from multiple rotating IPs.
        RateLimiter::for('otp-per-phone', function (Request $request) {
            $phone = PhoneNumber::normalize($request->input('phone')) ?? $request->input('phone');

            return Limit::perHour(5)->by('otp-phone:'.$phone);
        });

        RateLimiter::for('otp-per-ip', function (Request $request) {
            return Limit::perHour(10)->by('otp-ip:'.$request->ip());
        });
    }
}
