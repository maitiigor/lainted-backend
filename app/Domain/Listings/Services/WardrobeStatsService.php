<?php

namespace App\Domain\Listings\Services;

use App\Domain\Listings\Models\Listing;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\WalletTransaction;
use App\Domain\Users\Models\User;
use App\Support\Enums\ListingSource;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\WalletTransactionSource;

/**
 * "My Wardrobe" stats (doc: items listed, items sold, total earned) -
 * scoped to listing_source Wardrobe, i.e. all of a personal seller's own
 * selling activity, not just the subset flagged is_wardrobe_cleanout.
 */
class WardrobeStatsService
{
    public function forSeller(User $seller): array
    {
        $itemsListed = Listing::where('seller_id', $seller->id)
            ->where('listing_source', ListingSource::Wardrobe)
            ->whereIn('status', [ListingStatus::Active, ListingStatus::Sold])
            ->count();

        $wardrobeOrderIds = Order::where('seller_id', $seller->id)
            ->where('status', OrderStatus::Completed)
            ->whereHas('listing', fn ($q) => $q->where('listing_source', ListingSource::Wardrobe))
            ->pluck('id');

        // related_order_id alone is enough to scope this correctly - each
        // completed order credits exactly one SaleEarning transaction to
        // the seller's own wallet (see EscrowService::creditWallet).
        $totalEarned = WalletTransaction::whereIn('related_order_id', $wardrobeOrderIds)
            ->where('source', WalletTransactionSource::SaleEarning)
            ->sum('amount');

        return [
            'items_listed' => $itemsListed,
            'items_sold' => $wardrobeOrderIds->count(),
            'total_earned' => round((float) $totalEarned, 2),
        ];
    }
}
