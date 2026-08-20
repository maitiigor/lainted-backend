<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\AccountRestrictedException;
use App\Support\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single enforcement point for account_status/suspended_until - both
 * columns existed and were settable (via Filament) long before anything
 * ever checked them. Sitting on the one shared auth:sanctum group means
 * every authenticated route is covered at once, including for a user who
 * was Active when their token was issued and got suspended mid-session.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->account_status === AccountStatus::Banned) {
            throw new AccountRestrictedException('Your account has been permanently banned for violating our terms.');
        }

        if ($user?->account_status === AccountStatus::Suspended && $user->suspended_until?->isFuture()) {
            throw new AccountRestrictedException(
                'Your account is suspended until '.$user->suspended_until->toFormattedDateString().' for violating our terms.'
            );
        }

        return $next($request);
    }
}
