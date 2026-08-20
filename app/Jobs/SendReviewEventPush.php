<?php

namespace App\Jobs;

use App\Domain\Notifications\Services\PushNotificationService;
use App\Domain\Trust\Models\Review;
use App\Support\Enums\ReviewPushEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReviewEventPush implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Review $review,
        private readonly ReviewPushEvent $event,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        match ($this->event) {
            ReviewPushEvent::Received => $push->sendReviewReceived($this->review->reviewee, $this->review),
        };
    }
}
