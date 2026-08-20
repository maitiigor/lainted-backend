<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Chat\Models\Message;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Notifications\Models\DeviceToken;
use App\Domain\Orders\Models\Order;
use App\Domain\Trust\Models\Dispute;
use App\Domain\Trust\Models\Review;
use App\Domain\Users\Models\User;
use App\Support\Enums\OfferParty;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Throwable;

class PushNotificationService
{
    public function sendNewMessage(User $recipient, Message $message): void
    {
        $this->push(
            $recipient,
            $message->sender->name,
            $message->is_flagged ? '[Message removed - contained contact information]' : ($message->body ?? 'Sent a photo'),
        );
    }

    public function sendOfferReceived(User $recipient, Offer $offer): void
    {
        $this->push(
            $recipient,
            'New offer on '.$offer->listing->title,
            'You have an offer — respond within 24 hours',
        );
    }

    public function sendOfferAccepted(User $recipient, Offer $offer): void
    {
        $this->push(
            $recipient,
            'Offer accepted',
            "You agreed on \u{20a6}".number_format((float) $offer->amount).' — pay now',
        );
    }

    public function sendOfferDeclined(User $recipient, Offer $offer): void
    {
        $this->push(
            $recipient,
            'Offer declined',
            'Your offer on '.$offer->listing->title.' was declined.',
        );
    }

    public function sendOfferExpiryReminder(User $recipient, Offer $offer, int $hoursRemaining): void
    {
        $party = $recipient->id === $offer->buyer_id ? OfferParty::Buyer : OfferParty::Seller;
        $waiting = $offer->initiated_by === $party ? "you're waiting on a response to" : 'you need to respond to';

        $this->push(
            $recipient,
            'Offer expiring soon',
            "Reminder: {$waiting} an offer on ".$offer->listing->title." - {$hoursRemaining}h left.",
        );
    }

    public function sendOrderPaymentConfirmed(User $recipient, Order $order): void
    {
        $this->push(
            $recipient,
            'New sale',
            'Payment received for '.$order->listing->title.' — prepare it for delivery.',
        );
    }

    public function sendOrderPrepared(User $recipient, Order $order): void
    {
        $this->push(
            $recipient,
            'Order prepared',
            'Your order for '.$order->listing->title.' has been prepared.',
        );
    }

    public function sendOrderInTransit(User $recipient, Order $order): void
    {
        $this->push(
            $recipient,
            'Order on its way',
            'Your order for '.$order->listing->title.' is in transit.',
        );
    }

    public function sendOrderDelivered(User $recipient, Order $order): void
    {
        $this->push(
            $recipient,
            'Order delivered',
            'The order for '.$order->listing->title.' has been marked as delivered.',
        );
    }

    public function sendOrderCompleted(User $recipient, Order $order): void
    {
        $this->push(
            $recipient,
            'Order completed',
            'The order for '.$order->listing->title.' is complete — your payment has been released to your wallet.',
        );
    }

    public function sendDisputeRaised(User $recipient, Dispute $dispute): void
    {
        $this->push(
            $recipient,
            'Dispute raised',
            'A dispute has been raised for '.$dispute->order->listing->title.' — respond within 48 hours.',
        );
    }

    public function sendDisputeResponded(User $recipient, Dispute $dispute): void
    {
        $this->push(
            $recipient,
            'Seller responded to your dispute',
            'The seller has responded to your dispute for '.$dispute->order->listing->title.'.',
        );
    }

    public function sendDisputeEscalated(User $recipient, Dispute $dispute): void
    {
        $this->push(
            $recipient,
            'Dispute under review',
            'Your dispute for '.$dispute->order->listing->title.' has been escalated to our support team.',
        );
    }

    public function sendDisputeResolved(User $recipient, Dispute $dispute, string $summary): void
    {
        $this->push($recipient, 'Dispute resolved', $summary);
    }

    public function sendReviewReceived(User $recipient, Review $review): void
    {
        $this->push(
            $recipient,
            'You\'ve been reviewed',
            str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating).' - tap to see what they said.',
        );
    }

    private function push(User $recipient, string $title, string $body): void
    {
        $tokens = DeviceToken::where('user_id', $recipient->id)->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        try {
            // Resolved lazily, not constructor-injected - a missing/invalid
            // service-account credential throws the first time the SDK is
            // actually touched, and that needs to be inside this try too,
            // not just the send call, to degrade gracefully like the
            // existing Termii SMS integration does.
            $messaging = app(Messaging::class);

            $notification = FirebaseNotification::create($title, $body);

            $report = $messaging->sendMulticast(
                CloudMessage::new()->withNotification($notification),
                $tokens->all(),
            );

            $this->pruneInvalidTokens($report);
        } catch (Throwable $e) {
            Log::warning('FCM push send failed', [
                'user_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function pruneInvalidTokens(MulticastSendReport $report): void
    {
        foreach ($report->invalidTokens() as $token) {
            DeviceToken::where('token', $token)->delete();
        }
    }
}
