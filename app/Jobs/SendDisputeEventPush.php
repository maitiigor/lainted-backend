<?php

namespace App\Jobs;

use App\Domain\Notifications\Services\PushNotificationService;
use App\Domain\Orders\Models\Order;
use App\Domain\Trust\Models\Dispute;
use App\Support\Enums\DisputePushEvent;
use App\Support\Enums\DisputeStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDisputeEventPush implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Dispute $dispute,
        private readonly DisputePushEvent $event,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $order = $this->dispute->order;

        match ($this->event) {
            DisputePushEvent::Raised => $push->sendDisputeRaised($order->seller, $this->dispute),
            DisputePushEvent::SellerResponded => $push->sendDisputeResponded($order->buyer, $this->dispute),
            DisputePushEvent::Escalated => $push->sendDisputeEscalated($order->buyer, $this->dispute),
            DisputePushEvent::Resolved => $this->sendResolved($push, $order),
        };
    }

    private function sendResolved(PushNotificationService $push, Order $order): void
    {
        [$buyerSummary, $sellerSummary] = match ($this->dispute->status) {
            DisputeStatus::ResolvedRefund => [
                'You have been refunded in full for '.$order->listing->title.'.',
                'The dispute for '.$order->listing->title.' was resolved in the buyer\'s favor - a full refund was issued.',
            ],
            DisputeStatus::ResolvedRelease => [
                'Your dispute for '.$order->listing->title.' was resolved - the seller has been paid.',
                'The dispute for '.$order->listing->title.' was resolved in your favor - payment has been released to your wallet.',
            ],
            default => [
                'Your dispute for '.$order->listing->title.' was resolved with a partial refund.',
                'The dispute for '.$order->listing->title.' was resolved with a partial refund to the buyer.',
            ],
        };

        $push->sendDisputeResolved($order->buyer, $this->dispute, $buyerSummary);
        $push->sendDisputeResolved($order->seller, $this->dispute, $sellerSummary);
    }
}
