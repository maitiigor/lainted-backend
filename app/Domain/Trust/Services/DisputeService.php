<?php

namespace App\Domain\Trust\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Services\EscrowService;
use App\Domain\Trust\Exceptions\DisputeException;
use App\Domain\Trust\Models\Dispute;
use App\Domain\Trust\Services\TrustMetricsService;
use App\Domain\Trust\Services\UserModerationService;
use App\Domain\Users\Models\User;
use App\Jobs\SendDisputeEventPush;
use App\Support\Enums\DisputePushEvent;
use App\Support\Enums\DisputeStatus;
use App\Support\Enums\DisputeType;
use App\Support\Enums\EscrowStatus;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\StrikeType;
use Illuminate\Support\Facades\DB;

class DisputeService
{
    public function __construct(
        private readonly EscrowService $escrow,
        private readonly TrustMetricsService $trustMetrics,
        private readonly UserModerationService $moderation,
    ) {}

    /**
     * Buyer-only, and only while the order is sitting in its post-delivery
     * inspection window - deliberately mutually exclusive with confirming
     * receipt (see OrderService::confirmReceipt's active-dispute guard).
     */
    public function raise(Order $order, User $buyer, DisputeType $type, string $statement, ?array $photos): Dispute
    {
        if ($buyer->id !== $order->buyer_id) {
            throw new DisputeException('Only the buyer can raise a dispute on this order.');
        }

        if ($order->status !== OrderStatus::Delivered) {
            throw new DisputeException('This order is not eligible for a dispute right now.');
        }

        if ($order->dispute_deadline_at === null || now()->isAfter($order->dispute_deadline_at)) {
            throw new DisputeException('The 48-hour dispute window for this order has passed.');
        }

        $this->assertNoActiveDispute($order);

        $dispute = DB::transaction(function () use ($order, $buyer, $type, $statement, $photos) {
            $dispute = Dispute::create([
                'order_id' => $order->id,
                'raised_by' => $buyer->id,
                'dispute_type' => $type,
                'buyer_statement' => $statement,
                'buyer_photos' => $photos,
                'status' => DisputeStatus::Open,
                'self_resolve_expires_at' => now()->addHours(48),
            ]);

            $order->escrow?->update(['status' => EscrowStatus::Disputed]);

            return $dispute;
        });

        SendDisputeEventPush::dispatch($dispute, DisputePushEvent::Raised);

        return $dispute;
    }

    /**
     * Seller-only. An offer turns this into a self-resolution window for
     * the buyer; no offer skips straight to admin review.
     */
    public function respondAsSeller(Dispute $dispute, User $seller, string $statement, ?array $photos, ?float $selfResolutionOffered): Dispute
    {
        if ($seller->id !== $dispute->order->seller_id) {
            throw new DisputeException('Only the seller can respond to this dispute.');
        }

        if ($dispute->status !== DisputeStatus::Open) {
            throw new DisputeException('This dispute is no longer awaiting a seller response.');
        }

        if ($selfResolutionOffered !== null && $selfResolutionOffered > (float) $dispute->order->item_price) {
            throw new DisputeException('The offered amount cannot exceed the item price.');
        }

        $dispute->update([
            'seller_statement' => $statement,
            'seller_photos' => $photos,
            'self_resolution_offered' => $selfResolutionOffered,
            'status' => $selfResolutionOffered !== null ? DisputeStatus::SelfResolving : DisputeStatus::AdminReview,
        ]);

        if ($selfResolutionOffered !== null) {
            SendDisputeEventPush::dispatch($dispute, DisputePushEvent::SellerResponded);
        } else {
            SendDisputeEventPush::dispatch($dispute, DisputePushEvent::Escalated);
        }

        return $dispute;
    }

    public function acceptSelfResolution(Dispute $dispute, User $buyer): Dispute
    {
        $this->assertBuyer($dispute, $buyer);

        if ($dispute->status !== DisputeStatus::SelfResolving || $dispute->self_resolution_offered === null) {
            throw new DisputeException('There is no resolution offer to accept.');
        }

        return $this->finalize($dispute, (float) $dispute->self_resolution_offered, true, null, null);
    }

    public function rejectSelfResolution(Dispute $dispute, User $buyer): Dispute
    {
        $this->assertBuyer($dispute, $buyer);

        if ($dispute->status !== DisputeStatus::SelfResolving) {
            throw new DisputeException('There is no resolution offer to reject.');
        }

        $dispute->update(['self_resolution_accepted' => false, 'status' => DisputeStatus::AdminReview]);
        SendDisputeEventPush::dispatch($dispute, DisputePushEvent::Escalated);

        return $dispute;
    }

    public function adminResolveRelease(Dispute $dispute, User $admin, ?string $notes): Dispute
    {
        return $this->finalize($dispute, 0.0, null, $admin, $notes);
    }

    public function adminResolveRefund(Dispute $dispute, User $admin, ?string $notes): Dispute
    {
        return $this->finalize($dispute, (float) $dispute->order->total_amount, null, $admin, $notes);
    }

    public function adminResolvePartial(Dispute $dispute, User $admin, float $buyerRefundAmount, ?string $notes): Dispute
    {
        return $this->finalize($dispute, $buyerRefundAmount, null, $admin, $notes);
    }

    /**
     * The single shared money-moving + status-deriving core - resolution
     * status is derived from the money split itself (0 => released to
     * seller, full total => refunded, anything else => partial), not from
     * which actor decided it. Self-resolution-accepted and every admin
     * path converge here.
     */
    private function finalize(Dispute $dispute, float $buyerRefundAmount, ?bool $selfResolutionAccepted, ?User $admin, ?string $notes): Dispute
    {
        $order = $dispute->order;

        $resolved = DB::transaction(function () use ($dispute, $order, $buyerRefundAmount, $selfResolutionAccepted, $admin, $notes) {
            if ($buyerRefundAmount <= 0) {
                $this->escrow->release($order);
                $status = DisputeStatus::ResolvedRelease;
            } elseif ($buyerRefundAmount >= (float) $order->total_amount) {
                $this->escrow->refund($order);
                $status = DisputeStatus::ResolvedRefund;
            } else {
                $this->escrow->releasePartial($order, $buyerRefundAmount);
                $status = DisputeStatus::ResolvedPartial;
            }

            $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);

            $dispute->update([
                'status' => $status,
                'resolved_at' => now(),
                'self_resolution_accepted' => $selfResolutionAccepted,
                'admin_id' => $admin?->id,
                'admin_notes' => $notes,
            ]);

            if (in_array($status, [DisputeStatus::ResolvedRefund, DisputeStatus::ResolvedPartial], true)) {
                $this->maybeStrikeForGradeMismatch($dispute);
            }

            return $dispute;
        });

        // A resolved dispute always lands the order on Completed, which is
        // exactly when total_sales changes - same trigger as
        // OrderService::confirmReceipt()'s normal happy path.
        $this->trustMetrics->recompute($order->seller);

        SendDisputeEventPush::dispatch($resolved, DisputePushEvent::Resolved);

        return $resolved;
    }

    /**
     * Doc: grade-mismatch is "automatic refund eligibility" on first
     * offense, refund + a seller strike on repeats. "Repeat" is counted
     * by prior resolved-in-the-buyer's-favor GradeMismatch disputes
     * against this seller, not by existing Strike rows (those are the
     * consequence, not the count).
     */
    private function maybeStrikeForGradeMismatch(Dispute $dispute): void
    {
        if ($dispute->dispute_type !== DisputeType::GradeMismatch) {
            return;
        }

        $priorCount = Dispute::query()
            ->where('id', '!=', $dispute->id)
            ->where('dispute_type', DisputeType::GradeMismatch)
            ->whereIn('status', [DisputeStatus::ResolvedRefund, DisputeStatus::ResolvedPartial])
            ->whereHas('order', fn ($q) => $q->where('seller_id', $dispute->order->seller_id))
            ->count();

        if ($priorCount === 0) {
            return;
        }

        $this->moderation->issueStrike(
            $dispute->order->seller,
            StrikeType::GradeMismatch,
            'Repeat grade-mismatch dispute resolved in the buyer\'s favor.',
            IssuedBy::System,
            referenceType: Dispute::class,
            referenceId: $dispute->id,
        );
    }

    private function assertNoActiveDispute(Order $order): void
    {
        $hasActive = $order->disputes()
            ->whereNotIn('status', [DisputeStatus::ResolvedRefund, DisputeStatus::ResolvedRelease, DisputeStatus::ResolvedPartial])
            ->exists();

        if ($hasActive) {
            throw new DisputeException('This order already has an active dispute.');
        }
    }

    private function assertBuyer(Dispute $dispute, User $buyer): void
    {
        if ($buyer->id !== $dispute->order->buyer_id) {
            throw new DisputeException('Only the buyer can do this.');
        }
    }
}
