<?php

namespace App\Events;

use App\Domain\Chat\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, Channel|PresenceChannel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->message->conversation->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return $this->message->load(['sender', 'offer.buyer', 'offer.seller', 'offer.listing', 'offer.order'])->toArray();
    }
}
