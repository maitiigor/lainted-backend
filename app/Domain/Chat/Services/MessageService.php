<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Exceptions\ChatException;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Chat\Models\Message;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Trust\Services\UserModerationService;
use App\Domain\Users\Models\User;
use App\Domain\Users\Services\BlockService;
use App\Events\MessageSent;
use App\Jobs\SendNewMessagePush;
use App\Support\ContactLeakDetector;
use App\Support\Enums\ContactLeakSourceType;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\MessageType;
use App\Support\Enums\StrikeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(private readonly UserModerationService $moderation) {}

    /**
     * Unlike a listing's contact-leak check (which rejects the request
     * and force-deletes the draft), a chat message is always saved - the
     * doc requires chat history preserved permanently for dispute
     * resolution, and describes the detector as "redacting", not
     * blocking. The send succeeds; it just comes back flagged.
     */
    public function send(Conversation $conversation, User $sender, array $data, ?UploadedFile $image): Message
    {
        $other = $conversation->buyer_id === $sender->id ? $conversation->seller : $conversation->buyer;

        if (BlockService::isBlockedEitherWay($sender, $other)) {
            throw new ChatException('You cannot message this user.');
        }

        $rawBody = $data['body'] ?? null;
        $match = $rawBody !== null ? ContactLeakDetector::detect($rawBody) : null;
        [$body, $isFlagged, $flaggedReason] = $match !== null
            ? $this->applyRedaction($rawBody, $match)
            : [$rawBody, false, null];

        $message = DB::transaction(function () use ($conversation, $sender, $data, $image, $body, $isFlagged, $flaggedReason, $match) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'type' => $data['type'],
                'body' => $body,
                'image_path' => $image ? $image->store('messages', 'public') : null,
                'is_flagged' => $isFlagged,
                'flagged_reason' => $flaggedReason,
            ]);

            if ($match !== null) {
                $this->recordLeakStrike($sender, $message, $match);
            }

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        broadcast(new MessageSent($message))->toOthers();
        SendNewMessagePush::dispatch($message);

        return $message;
    }

    /**
     * Offers render as a special message card in chat - this is the
     * linkage, not `send()` (which is shaped for text/image and always
     * runs blocking/leak-detection re-checks that OfferService has
     * already done before calling this). No push dispatch here - offer
     * push copy is transition-specific (received/accepted/declined),
     * owned by OfferService/SendOfferEventPush instead of the generic
     * "new message" push.
     */
    public function sendOfferMessage(Conversation $conversation, User $sender, Offer $offer): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $offer) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'offer_id' => $offer->id,
                'type' => MessageType::Offer,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    /**
     * @param  array{pattern: \App\Support\Enums\ContactLeakPattern, matched_text: string}  $match
     * @return array{0: string, 1: bool, 2: string}
     */
    private function applyRedaction(string $body, array $match): array
    {
        $matchedText = $match['matched_text'];
        $reason = "Detected {$match['pattern']->value} - redacted before sending.";

        // Best-effort surgical redaction when the matched text literally
        // appears in the original body - not always true, since some
        // matches come from a normalized copy (spelled-out digits,
        // "dot"/"at" instead of "./@") that doesn't exist verbatim in
        // what the sender actually typed. Falls back to redacting the
        // whole message rather than risk leaving part of the leak
        // visible because a substring search came up empty.
        $redacted = stripos($body, $matchedText) !== false
            ? str_ireplace($matchedText, '[redacted]', $body)
            : '[Message removed - contained contact information]';

        return [$redacted, true, $reason];
    }

    /**
     * @param  array{pattern: \App\Support\Enums\ContactLeakPattern, matched_text: string}  $match
     */
    private function recordLeakStrike(User $sender, Message $message, array $match): void
    {
        $strike = $this->moderation->issueStrike(
            $sender,
            StrikeType::ContactLeak,
            'Contact info detected in a chat message.',
            IssuedBy::System,
            referenceType: 'message',
            referenceId: $message->id,
        );

        ContactLeakStrike::create([
            'user_id' => $sender->id,
            'strike_id' => $strike->id,
            'source_type' => ContactLeakSourceType::Message,
            'source_id' => $message->id,
            'detected_pattern' => $match['pattern'],
            'matched_text' => $match['matched_text'],
        ]);
    }
}
