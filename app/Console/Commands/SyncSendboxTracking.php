<?php

namespace App\Console\Commands;

use App\Domain\Orders\Exceptions\SendboxException;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderService;
use App\Domain\Orders\Services\SendboxService;
use App\Jobs\SendOrderEventPush;
use App\Support\Enums\LogisticsProvider;
use App\Support\Enums\OrderPushEvent;
use App\Support\Enums\OrderStatus;
use Illuminate\Console\Command;

class SyncSendboxTracking extends Command
{
    protected $signature = 'orders:sync-sendbox-tracking';

    protected $description = "Poll Sendbox for real courier status on booked orders and advance Prepared->InTransit->Delivered accordingly";

    /**
     * No webhook exists for this yet (Sendbox's callback_url payload/
     * signature scheme isn't in the public docs) - this poll is the sole,
     * independently-verifiable mechanism for now. See SendboxService.
     */
    private const IN_TRANSIT_CODES = ['pickup_started', 'pickup_completed', 'in_delivery', 'in_transit'];

    public function handle(SendboxService $sendbox, OrderService $orders): void
    {
        Order::where('logistics_provider', LogisticsProvider::Sendbox)
            ->whereIn('status', [OrderStatus::Prepared, OrderStatus::InTransit])
            ->whereNotNull('tracking_reference')
            ->each(function (Order $order) use ($sendbox, $orders) {
                try {
                    $result = $sendbox->track($order->tracking_reference);
                } catch (SendboxException $e) {
                    return;
                }

                if ($result['status_code'] === 'delivered') {
                    $orders->syncDeliveredFromCourier($order);

                    return;
                }

                if (in_array($result['status_code'], self::IN_TRANSIT_CODES, true) && $order->status === OrderStatus::Prepared) {
                    $order->update(['status' => OrderStatus::InTransit]);
                    SendOrderEventPush::dispatch($order, OrderPushEvent::InTransit);
                }
            });
    }
}
