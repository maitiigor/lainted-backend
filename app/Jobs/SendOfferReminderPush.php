<?php

namespace App\Jobs;

use App\Domain\Negotiation\Models\Offer;
use App\Domain\Notifications\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOfferReminderPush implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Offer $offer,
        private readonly int $hoursRemaining,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        // Both parties get reminded - whoever's turn it is to respond
        // needs the nudge, and the other party benefits from knowing the
        // clock is ticking too.
        $push->sendOfferExpiryReminder($this->offer->buyer, $this->offer, $this->hoursRemaining);
        $push->sendOfferExpiryReminder($this->offer->seller, $this->offer, $this->hoursRemaining);
    }
}
