<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Registered separately from withRouting()'s `channels:` param, which
    // forwards no middleware override at all (defaults to the `web`
    // group - session/cookie auth). This app is a Sanctum bearer-token
    // API with no session cookies, so the default /broadcasting/auth
    // route would never authenticate the mobile client.
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.active' => \App\Http\Middleware\EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This is an API-only backend - every /api/* response must be JSON
        // regardless of the client's Accept header (a bare curl/Postman
        // request without one would otherwise get redirected to an HTML
        // "back" page, which doesn't exist for a mobile client).
        $exceptions->shouldRenderJsonWhen(fn ($request, $throwable) => $request->is('api/*'));

        // A `code` field, not just `message` - the mobile client needs to
        // safely react to *this* 403 (clear session, redirect to login)
        // without misfiring on unrelated 403s, like StoreListingRequest's
        // seller-profile gate.
        $exceptions->render(fn (\App\Http\Exceptions\AccountRestrictedException $e) => response()->json([
            'message' => $e->getMessage(),
            'code' => 'account_restricted',
        ], 403));
    })->create();
