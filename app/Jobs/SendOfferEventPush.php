<?php

namespace App\Jobs;

use App\Domain\Negotiation\Models\Offer;
use App\Domain\Notifications\Services\PushNotificationService;
use App\Support\Enums\OfferParty;
use App\Support\Enums\OfferPushEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOfferEventPush implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Offer $offer,
        private readonly OfferPushEvent $event,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        // "Created" (a fresh offer or a new counter) needs telling the
        // party who did NOT propose it - the opposite of initiated_by.
        // "Accepted"/"Declined" need telling the party who DID propose
        // the round that just got a response - initiated_by itself.
        $recipient = match ($this->event) {
            OfferPushEvent::Created => $this->offer->initiated_by === OfferParty::Buyer
                ? $this->offer->seller
                : $this->offer->buyer,
            OfferPushEvent::Accepted, OfferPushEvent::Declined => $this->offer->initiated_by === OfferParty::Buyer
                ? $this->offer->buyer
                : $this->offer->seller,
        };

        match ($this->event) {
            OfferPushEvent::Created => $push->sendOfferReceived($recipient, $this->offer),
            OfferPushEvent::Accepted => $push->sendOfferAccepted($recipient, $this->offer),
            OfferPushEvent::Declined => $push->sendOfferDeclined($recipient, $this->offer),
        };
    }
}
