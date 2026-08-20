<?php

namespace App\Console\Commands;

use App\Domain\Negotiation\Models\Offer;
use App\Jobs\SendOfferReminderPush;
use App\Support\Enums\OfferStatus;
use Illuminate\Console\Command;

class SweepOffers extends Command
{
    protected $signature = 'offers:sweep';

    protected $description = 'Send expiry reminders and flip stale offers/checkout windows to their terminal states';

    public function handle(): void
    {
        $this->sendReminders(6, 'reminder_sent_6h_at');
        $this->sendReminders(2, 'reminder_sent_2h_at');
        $this->expireStaleOffers();
        $this->expireStaleCheckoutWindows();
    }

    private function sendReminders(int $hours, string $column): void
    {
        Offer::where('status', OfferStatus::Pending)
            ->whereNull($column)
            ->whereBetween('expires_at', [now(), now()->addHours($hours)])
            ->each(function (Offer $offer) use ($hours, $column) {
                SendOfferReminderPush::dispatch($offer, $hours);
                $offer->update([$column => now()]);
            });
    }

    /**
     * Only ever touches `pending` - a `countered` row is history, not a
     * live offer waiting on anyone, and must never flip to `expired`.
     */
    private function expireStaleOffers(): void
    {
        Offer::where('status', OfferStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update(['status' => OfferStatus::Expired]);
    }

    private function expireStaleCheckoutWindows(): void
    {
        Offer::where('status', OfferStatus::Accepted)
            ->where('checkout_expires_at', '<=', now())
            ->update(['status' => OfferStatus::CheckoutExpired]);
    }
}
