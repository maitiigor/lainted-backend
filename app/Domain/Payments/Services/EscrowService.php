<?php

namespace App\Domain\Payments\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Models\Escrow;
use App\Domain\Payments\Models\PlatformSetting;
use App\Domain\Payments\Models\Wallet;
use App\Domain\Payments\Models\WalletTransaction;
use App\Domain\Users\Models\User;
use App\Support\Enums\EscrowStatus;
use App\Support\Enums\LedgerDirection;
use App\Support\Enums\SellerType;
use App\Support\Enums\WalletTransactionSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns every money movement out of an Order's Escrow row - normal release
 * (buyer confirms receipt, or the auto-release sweep), full refund, and
 * partial split (dispute resolution). Commission is computed and locked
 * into the Order here, at release time, never at checkout - Order Phase 6
 * deliberately left commission_rate/commission_amount at their defaults
 * for exactly this reason.
 */
class EscrowService
{
    /**
     * Held: the normal pre-dispute state. Disputed: DisputeService::raise
     * flips it here immediately, so a resolved dispute must still be able
     * to release from this state - Released/Refunded are the only truly
     * terminal ones.
     */
    private const RELEASABLE_STATUSES = [EscrowStatus::Held, EscrowStatus::Disputed];

    /**
     * Full payout to the seller, commission deducted. Called from
     * OrderService::confirmReceipt() and the escrows:sweep-release
     * command - both can race the same order, so this re-locks and
     * re-checks status before acting, exactly like buildOrder()'s
     * Listing::lockForUpdate() double-sell-race fix.
     */
    public function release(Order $order): void
    {
        $escrow = $order->escrow;
        if ($escrow === null || ! in_array($escrow->status, self::RELEASABLE_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($order, $escrow) {
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, self::RELEASABLE_STATUSES, true)) {
                return;
            }

            $rate = $this->commissionFor($order->seller);
            $commissionAmount = round((float) $order->item_price * $rate / 100, 2);
            $sellerPayout = (float) $order->item_price - $commissionAmount;

            $order->update(['commission_rate' => $rate, 'commission_amount' => $commissionAmount]);
            $locked->update(['status' => EscrowStatus::Released, 'released_at' => now()]);

            $this->creditWallet($order->seller, $sellerPayout, WalletTransactionSource::SaleEarning, $order);
        });
    }

    /**
     * Full refund to the buyer - nothing was ever paid out to a courier
     * for protection_fee/delivery_fee, so the whole total_amount goes
     * back rather than just item_price.
     */
    public function refund(Order $order): void
    {
        $escrow = $order->escrow;
        if ($escrow === null || ! in_array($escrow->status, self::RELEASABLE_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($order, $escrow) {
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, self::RELEASABLE_STATUSES, true)) {
                return;
            }

            $locked->update(['status' => EscrowStatus::Refunded, 'refunded_at' => now()]);

            $this->creditWallet($order->buyer, (float) $order->total_amount, WalletTransactionSource::Refund, $order);
        });
    }

    /**
     * Splits the seller's own share (item_price minus commission) between
     * a buyer refund and a seller payout - used by dispute resolution.
     * $buyerRefundAmount can never exceed the seller's share: protection/
     * delivery fees are platform revenue, never part of what a dispute
     * can claw back.
     */
    public function releasePartial(Order $order, float $buyerRefundAmount): void
    {
        $escrow = $order->escrow;
        if ($escrow === null || ! in_array($escrow->status, self::RELEASABLE_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($order, $escrow, $buyerRefundAmount) {
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, self::RELEASABLE_STATUSES, true)) {
                return;
            }

            $rate = $this->commissionFor($order->seller);
            $commissionAmount = round((float) $order->item_price * $rate / 100, 2);
            $sellerFullShare = (float) $order->item_price - $commissionAmount;

            if ($buyerRefundAmount > $sellerFullShare) {
                throw new PaymentException("Refund amount exceeds the seller's share of this sale.");
            }

            $sellerPayout = round($sellerFullShare - $buyerRefundAmount, 2);

            $order->update(['commission_rate' => $rate, 'commission_amount' => $commissionAmount]);
            $locked->update([
                'status' => $sellerPayout > 0 ? EscrowStatus::Released : EscrowStatus::Refunded,
                'released_at' => now(),
                'refunded_at' => now(),
            ]);

            if ($buyerRefundAmount > 0) {
                $this->creditWallet($order->buyer, $buyerRefundAmount, WalletTransactionSource::Refund, $order);
            }

            if ($sellerPayout > 0) {
                $this->creditWallet($order->seller, $sellerPayout, WalletTransactionSource::SaleEarning, $order);
            }
        });
    }

    private function commissionFor(User $seller): float
    {
        if ($seller->seller_type === SellerType::Business) {
            // Subscription-funded per the product doc - business sellers
            // never pay a per-sale commission.
            return 0.0;
        }

        return (float) PlatformSetting::current()->personal_commission_rate;
    }

    private function creditWallet(User $user, float $amount, WalletTransactionSource $source, ?Order $order): void
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
        $wallet->increment('balance', $amount);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => LedgerDirection::Credit,
            'source' => $source,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'reference' => 'wt_'.Str::uuid(),
            'related_order_id' => $order?->id,
        ]);
    }
}
