<?php

namespace App\Console\Commands;

use App\Domain\Orders\Models\Order;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentMethod;
use Illuminate\Console\Command;

class SweepAbandonedOrders extends Command
{
    protected $signature = 'orders:sweep-abandoned';

    protected $description = 'Cancel online orders left unpaid past a grace window and free their listings back up';

    /**
     * A Listing flips to Sold at order-creation time (not payment
     * confirmation) to close the double-sell race between two buyers
     * checking out at once - so an abandoned/never-paid order must be
     * swept back, or the listing stays permanently unbuyable. 30 minutes
     * is a judgment call (a generous Paystack checkout session), not
     * doc-specified.
     */
    private const GRACE_MINUTES = 30;

    public function handle(): void
    {
        Order::where('status', OrderStatus::Pending)
            ->where('payment_method', PaymentMethod::Online)
            ->where('created_at', '<=', now()->subMinutes(self::GRACE_MINUTES))
            ->each(function (Order $order) {
                $order->update(['status' => OrderStatus::Cancelled]);
                $order->listing?->update(['status' => ListingStatus::Active]);
            });
    }
}
