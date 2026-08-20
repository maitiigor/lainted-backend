<?php

namespace App\Domain\Negotiation\Services;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Services\MessageService;
use App\Domain\Negotiation\Exceptions\OfferException;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\BlockService;
use App\Events\OfferUpdated;
use App\Jobs\SendOfferEventPush;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\OfferParty;
use App\Support\Enums\OfferPushEvent;
use App\Support\Enums\OfferStatus;
use Illuminate\Support\Facades\DB;

class OfferService
{
    private const MAX_ROUNDS = 5;

    public function __construct(private readonly MessageService $messages) {}

    /**
     * Buyer-only, per the doc's "Make Offer button on every negotiable
     * listing" - the seller replies within the thread, never starts a
     * fresh offer on their own listing.
     */
    public function create(Conversation $conversation, User $buyer, float $amount): Offer
    {
        $listing = $conversation->listing;
        $seller = $conversation->seller;

        if ($buyer->id !== $conversation->buyer_id) {
            throw new OfferException('Only the buyer can make an offer.');
        }

        if (! $listing->is_negotiable) {
            throw new OfferException('This listing is not negotiable.');
        }

        if ($listing->status !== ListingStatus::Active) {
            throw new OfferException('This listing is no longer available.');
        }

        if (BlockService::isBlockedEitherWay($buyer, $seller)) {
            throw new OfferException('You cannot make an offer to this user.');
        }

        $this->assertValidAmount($amount, (float) $listing->price);

        $meetsMinimum = $listing->minimum_price !== null && $amount >= (float) $listing->minimum_price;

        $offer = DB::transaction(function () use ($conversation, $listing, $buyer, $seller, $amount, $meetsMinimum) {
            $offer = Offer::create([
                'listing_id' => $listing->id,
                'conversation_id' => $conversation->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'initiated_by' => OfferParty::Buyer,
                'amount' => $amount,
                'round_number' => 1,
                'status' => $meetsMinimum ? OfferStatus::Accepted : OfferStatus::Pending,
                'expires_at' => now()->addHours(24),
                'responded_at' => $meetsMinimum ? now() : null,
                'agreed_at' => $meetsMinimum ? now() : null,
                'checkout_expires_at' => $meetsMinimum ? now()->addHours(2) : null,
            ]);

            $this->messages->sendOfferMessage($conversation, $buyer, $offer);

            return $offer;
        });

        SendOfferEventPush::dispatch($offer, $meetsMinimum ? OfferPushEvent::Accepted : OfferPushEvent::Created);

        return $offer;
    }

    /**
     * Only the party who did NOT propose the current round may counter
     * it. Creates a new Offer row (parent_offer_id + round_number+1) -
     * the current row is history from here on, never mutated again
     * except by nothing (it stays `countered` forever).
     */
    public function counter(Offer $offer, User $responder, float $amount): Offer
    {
        $this->assertRespondable($offer, $responder);

        if ($offer->round_number >= self::MAX_ROUNDS) {
            throw new OfferException('This negotiation has reached its maximum number of rounds.');
        }

        if (BlockService::isBlockedEitherWay($offer->buyer, $offer->seller)) {
            throw new OfferException('You cannot counter this offer.');
        }

        $this->assertValidAmount($amount, (float) $offer->listing->price);

        if ((float) $amount === (float) $offer->amount) {
            throw new OfferException('Your counter must be a different amount.');
        }

        $child = DB::transaction(function () use ($offer, $responder, $amount) {
            $offer->update(['status' => OfferStatus::Countered, 'responded_at' => now()]);

            $child = Offer::create([
                'listing_id' => $offer->listing_id,
                'conversation_id' => $offer->conversation_id,
                'buyer_id' => $offer->buyer_id,
                'seller_id' => $offer->seller_id,
                'parent_offer_id' => $offer->id,
                'initiated_by' => $offer->initiated_by->opposite(),
                'amount' => $amount,
                'round_number' => $offer->round_number + 1,
                'status' => OfferStatus::Pending,
                'expires_at' => now()->addHours(24),
            ]);

            $this->messages->sendOfferMessage($offer->conversation, $responder, $child);

            return $child;
        });

        broadcast(new OfferUpdated($offer))->toOthers();
        SendOfferEventPush::dispatch($child, OfferPushEvent::Created);

        return $child;
    }

    public function accept(Offer $offer, User $responder): Offer
    {
        $this->assertRespondable($offer, $responder);

        $offer->update([
            'status' => OfferStatus::Accepted,
            'responded_at' => now(),
            'agreed_at' => now(),
            'checkout_expires_at' => now()->addHours(2),
        ]);

        broadcast(new OfferUpdated($offer))->toOthers();
        SendOfferEventPush::dispatch($offer, OfferPushEvent::Accepted);

        return $offer;
    }

    public function decline(Offer $offer, User $responder): Offer
    {
        $this->assertRespondable($offer, $responder);

        $offer->update(['status' => OfferStatus::Declined, 'responded_at' => now()]);

        broadcast(new OfferUpdated($offer))->toOthers();
        SendOfferEventPush::dispatch($offer, OfferPushEvent::Declined);

        return $offer;
    }

    /**
     * Buyer-only, and only for a round the buyer themselves proposed - a
     * seller's counter sitting pending isn't the buyer's to retract,
     * it's theirs to accept/decline/counter.
     */
    public function retract(Offer $offer, User $buyer): Offer
    {
        if ($buyer->id !== $offer->buyer_id) {
            throw new OfferException('Only the buyer can retract an offer.');
        }

        if ($offer->initiated_by !== OfferParty::Buyer) {
            throw new OfferException('You can only retract an offer you made.');
        }

        $this->assertPendingAndNotExpired($offer);

        $offer->update(['status' => OfferStatus::Retracted, 'responded_at' => now()]);

        broadcast(new OfferUpdated($offer))->toOthers();

        return $offer;
    }

    private function assertRespondable(Offer $offer, User $responder): void
    {
        $this->assertPendingAndNotExpired($offer);

        $responderId = $offer->initiated_by === OfferParty::Buyer ? $offer->seller_id : $offer->buyer_id;

        if ($responder->id !== $responderId) {
            throw new OfferException('You cannot respond to this offer.');
        }
    }

    /**
     * Lazily expires a stale row on touch, rather than leaving it
     * `pending` until the next scheduled sweep catches up to it.
     */
    private function assertPendingAndNotExpired(Offer $offer): void
    {
        if ($offer->status !== OfferStatus::Pending) {
            throw new OfferException('This offer is no longer awaiting a response.');
        }

        if ($offer->expires_at->isPast()) {
            $offer->update(['status' => OfferStatus::Expired]);
            throw new OfferException('This offer has expired.');
        }
    }

    private function assertValidAmount(float $amount, float $listingPrice): void
    {
        if ($amount <= 0 || $amount > $listingPrice) {
            throw new OfferException('Enter a valid offer amount.');
        }
    }
}
