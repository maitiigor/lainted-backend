<?php

namespace App\Jobs;

use App\Domain\Chat\Models\Message;
use App\Domain\Notifications\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNewMessagePush implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Message $message) {}

    public function handle(PushNotificationService $push): void
    {
        $conversation = $this->message->conversation;
        $recipient = $this->message->sender_id === $conversation->buyer_id
            ? $conversation->seller
            : $conversation->buyer;

        $push->sendNewMessage($recipient, $this->message);
    }
}
