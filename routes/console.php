<?php

use App\Console\Commands\ExpireSubscriptions;
use App\Console\Commands\LiftExpiredSuspensions;
use App\Console\Commands\PurgeDeletedAccounts;
use App\Console\Commands\RecomputeSellerTrustMetrics;
use App\Console\Commands\SweepAbandonedOrders;
use App\Console\Commands\SweepEscrowReleases;
use App\Console\Commands\SweepOffers;
use App\Console\Commands\SweepStaleDisputes;
use App\Console\Commands\SyncSendboxTracking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminders (6h/2h before expiry) and expiry/checkout-window sweeps -
// this app's first scheduled task. Lives here, not bootstrap/app.php:
// this file is already the documented Laravel 12 home for Schedule::
// calls, and bootstrap/app.php in this app is consistently reserved for
// HTTP-bootstrap wiring (routing, middleware, exceptions, broadcasting).
Schedule::command(SweepOffers::class)->everyFiveMinutes();

// Frees a listing back up when its buyer never completes payment - see
// SweepAbandonedOrders for why this can't wait for Phase 7's release job.
Schedule::command(SweepAbandonedOrders::class)->everyFiveMinutes();

// Phase 7's release job, anticipated by the comment above: auto-releases
// escrow 48h after delivery if the buyer never confirmed and no dispute
// is open.
Schedule::command(SweepEscrowReleases::class)->everyFiveMinutes();

// Escalates an un-responded dispute to admin review after its 48h
// self-resolution window closes.
Schedule::command(SweepStaleDisputes::class)->everyFiveMinutes();

// Polls Sendbox for real courier status on booked orders - no webhook
// exists yet (see SyncSendboxTracking), so this poll is the sole
// mechanism advancing Prepared->InTransit->Delivered for those orders.
Schedule::command(SyncSendboxTracking::class)->everyFiveMinutes();

// Reverts a 7-day Strike-driven suspension once it expires - keeps
// EnsureAccountIsActive itself simple/read-only.
Schedule::command(LiftExpiredSuspensions::class)->everyFiveMinutes();

// Backstop only - rating/total_sales recompute eagerly on the events that
// change them (see ReviewService, OrderService::confirmReceipt,
// DisputeService::finalize). This is the first non-5-minute cadence in
// this file: trust drift isn't time-sensitive the way escrow/dispute
// sweeps are.
Schedule::command(RecomputeSellerTrustMetrics::class)->daily();

// Finalizes self-service account deletion once the 30-day recovery
// window has passed (forfeit wallet + real soft-delete) - see
// AccountDeletionService for why the account isn't soft-deleted until
// this actually runs.
Schedule::command(PurgeDeletedAccounts::class)->everyFiveMinutes();

// Downgrades a lapsed Pro/Elite subscription back to Free once its
// 30-day period ends - without this, User::activeSubscription() would
// keep reading the old Active row forever regardless of ends_at.
Schedule::command(ExpireSubscriptions::class)->everyFiveMinutes();
