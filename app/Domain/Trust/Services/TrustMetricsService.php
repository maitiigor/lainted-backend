<?php

namespace App\Domain\Trust\Services;

use App\Domain\Users\Models\User;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\SellerRank;
use App\Support\Enums\SellerType;

/**
 * Doc gives no formula for trust_score/seller_rank/response_rate - the
 * weights and tier thresholds below are a v1 heuristic, tunable later,
 * same spirit as SendboxService::PACKAGE_ESTIMATES.
 */
class TrustMetricsService
{
    public function recompute(User $user): void
    {
        $rating = $user->reviewsReceived()->avg('rating');
        $totalSales = $user->sales()->where('status', OrderStatus::Completed)->count();

        $data = [
            'rating' => $rating,
            'total_sales' => $totalSales,
        ];

        if ($user->seller_type !== SellerType::None) {
            $responseRate = $this->responseRate($user);
            $strikeCount = $user->strikes()->count();
            $trustScore = $this->trustScore($rating, $totalSales, $responseRate, $user->is_verified, $strikeCount);

            $data['response_rate'] = $responseRate;
            $data['trust_score'] = $trustScore;
            $data['seller_rank'] = $this->rankFor($totalSales, $trustScore);
        }

        // update()/fill() silently drop these - they're deliberately not in
        // $fillable since they're system-computed only. forceFill() is safe
        // here because $data is built entirely from this method, never from
        // request input.
        $user->forceFill($data)->save();
    }

    /**
     * % of the seller's conversations that have a buyer-initiated message
     * answered by the seller within 24h. Null (not 0) when the seller has
     * no such conversations yet, so a new seller isn't penalized.
     */
    private function responseRate(User $seller): ?float
    {
        $conversations = $seller->sellerConversations()->with('messages')->get();

        $eligible = 0;
        $responded = 0;

        foreach ($conversations as $conversation) {
            $firstBuyerMessage = $conversation->messages
                ->where('sender_id', $conversation->buyer_id)
                ->sortBy('created_at')
                ->first();

            if ($firstBuyerMessage === null) {
                continue;
            }

            $eligible++;

            $deadline = $firstBuyerMessage->created_at->addHours(24);
            $hasReply = $conversation->messages->contains(
                fn ($message) => $message->sender_id === $seller->id
                    && $message->created_at->greaterThanOrEqualTo($firstBuyerMessage->created_at)
                    && $message->created_at->lessThanOrEqualTo($deadline)
            );

            if ($hasReply) {
                $responded++;
            }
        }

        return $eligible === 0 ? null : round($responded / $eligible * 100, 2);
    }

    private function trustScore(mixed $rating, int $totalSales, ?float $responseRate, bool $isVerified, int $strikeCount): float
    {
        $score = 50 * ((float) ($rating ?? 0) / 5)
            + 25 * (min($totalSales, 50) / 50)
            + 15 * (($responseRate ?? 0) / 100)
            + ($isVerified ? 10 : 0)
            - min(8 * $strikeCount, 30);

        return round(max(0, min(100, $score)), 2);
    }

    private function rankFor(int $totalSales, float $trustScore): SellerRank
    {
        return match (true) {
            $totalSales >= 100 && $trustScore >= 85 => SellerRank::Elite,
            $totalSales >= 40 && $trustScore >= 70 => SellerRank::Top,
            $totalSales >= 10 && $trustScore >= 55 => SellerRank::Trusted,
            $totalSales >= 3 && $trustScore >= 35 => SellerRank::Rising,
            default => SellerRank::New,
        };
    }
}
