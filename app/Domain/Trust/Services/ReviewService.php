<?php

namespace App\Domain\Trust\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Trust\Exceptions\ReviewException;
use App\Domain\Trust\Models\Review;
use App\Domain\Users\Models\User;
use App\Jobs\SendReviewEventPush;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\ReviewerRole;
use App\Support\Enums\ReviewPushEvent;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private readonly TrustMetricsService $trustMetrics) {}

    /**
     * The caller only supplies a rating/comment - reviewee and
     * reviewer_role are always derived from which side of the order the
     * reviewer is actually on, closing off "reviewing the wrong person"
     * as a possible bug class entirely.
     */
    public function create(Order $order, User $reviewer, int $rating, ?string $comment): Review
    {
        if ($reviewer->id !== $order->buyer_id && $reviewer->id !== $order->seller_id) {
            throw new ReviewException('You are not a participant on this order.');
        }

        if ($order->status !== OrderStatus::Completed) {
            throw new ReviewException('This order is not eligible for a review yet.');
        }

        if (Review::where('order_id', $order->id)->where('reviewer_id', $reviewer->id)->exists()) {
            throw new ReviewException('You have already reviewed this order.');
        }

        $isBuyer = $reviewer->id === $order->buyer_id;
        $reviewee = $isBuyer ? $order->seller : $order->buyer;

        $review = DB::transaction(function () use ($order, $reviewer, $reviewee, $isBuyer, $rating, $comment) {
            $review = Review::create([
                'order_id' => $order->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $reviewee->id,
                'reviewer_role' => $isBuyer ? ReviewerRole::Buyer : ReviewerRole::Seller,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            $this->trustMetrics->recompute($reviewee);

            return $review;
        });

        SendReviewEventPush::dispatch($review, ReviewPushEvent::Received);

        return $review;
    }
}
