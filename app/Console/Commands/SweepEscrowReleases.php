<?php

namespace App\Console\Commands;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Services\EscrowService;
use App\Jobs\SendOrderEventPush;
use App\Support\Enums\EscrowStatus;
use App\Support\Enums\OrderPushEvent;
use App\Support\Enums\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SweepEscrowReleases extends Command
{
    protected $signature = 'escrows:sweep-release';

    protected $description = "Auto-release a Delivered order's escrow 48h after delivery if the buyer never confirmed and no dispute is open";

    /**
     * whereHas(escrow, Held) is what excludes a disputed order here -
     * raising a dispute flips Escrow.status to Disputed (see
     * DisputeService::raise), so no separate dispute-existence check
     * is needed on top of this.
     */
    public function handle(EscrowService $escrow): void
    {
        Order::where('status', OrderStatus::Delivered)
            ->where('dispute_deadline_at', '<=', now())
            ->whereHas('escrow', fn ($q) => $q->where('status', EscrowStatus::Held))
            ->each(function (Order $order) use ($escrow) {
                DB::transaction(function () use ($order, $escrow) {
                    $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
                    $escrow->release($order);
                });

                SendOrderEventPush::dispatch($order, OrderPushEvent::Completed);
            });
    }
}
