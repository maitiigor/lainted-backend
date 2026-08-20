<?php

namespace App\Jobs;

use App\Domain\Notifications\Services\PushNotificationService;
use App\Domain\Orders\Models\Order;
use App\Support\Enums\DeliveryTier;
use App\Support\Enums\OrderPushEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderEventPush implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly OrderPushEvent $event,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        match ($this->event) {
            // The buyer already sees this the moment their own checkout
            // succeeds - only the seller needs telling.
            OrderPushEvent::PaymentConfirmed => $push->sendOrderPaymentConfirmed($this->order->seller, $this->order),
            OrderPushEvent::Prepared => $push->sendOrderPrepared($this->order->buyer, $this->order),
            OrderPushEvent::InTransit => $push->sendOrderInTransit($this->order->buyer, $this->order),
            // Whoever did NOT perform the delivery confirmation needs
            // telling - the seller enters the code for Pickup, the buyer
            // self-reports for every other tier (see OrderService).
            OrderPushEvent::Delivered => $push->sendOrderDelivered(
                $this->order->delivery_tier === DeliveryTier::Pickup ? $this->order->buyer : $this->order->seller,
                $this->order,
            ),
            OrderPushEvent::Completed => $push->sendOrderCompleted($this->order->seller, $this->order),
        };
    }
}
