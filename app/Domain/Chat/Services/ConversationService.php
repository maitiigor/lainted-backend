<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Exceptions\ChatException;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Listings\Models\Listing;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\BlockService;
use App\Support\Enums\ListingStatus;

class ConversationService
{
    /**
     * Chat is initiated from the listing page, buyer-side only (per the
     * product doc) - the seller replies within the thread this creates,
     * never starts a new one for their own listing.
     */
    public function start(User $buyer, Listing $listing): Conversation
    {
        if ($listing->seller_id === $buyer->id) {
            throw new ChatException('You cannot message yourself about your own listing.');
        }

        if ($listing->status !== ListingStatus::Active) {
            throw new ChatException('This listing is no longer available to message about.');
        }

        if (BlockService::isBlockedEitherWay($buyer, $listing->seller)) {
            throw new ChatException('You cannot start a conversation with this user.');
        }

        return Conversation::firstOrCreate(
            ['listing_id' => $listing->id, 'buyer_id' => $buyer->id],
            ['seller_id' => $listing->seller_id],
        );
    }

    public function markRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
