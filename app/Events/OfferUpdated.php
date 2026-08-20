<?php

namespace App\Events;

use App\Domain\Negotiation\Models\Offer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Accept/decline/retract mutate an existing Offer row in place with no
 * new Message row, so `MessageSent`/`message.sent` never fires for those
 * transitions - this is the only way a chat screen finds out about them
 * in real time.
 */
class OfferUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Offer $offer) {}

    /**
     * @return array<int, Channel|PresenceChannel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->offer->conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'offer.updated';
    }

    public function broadcastWith(): array
    {
        // `order` lets the mobile app show a "View Order" link once a
        // paid-for offer flips to Completed - see OrderService.
        return $this->offer->load(['buyer', 'seller', 'listing', 'order'])->toArray();
    }
}
