<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Domain\Chat\Exceptions\ChatException;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Services\ConversationService;
use App\Domain\Listings\Models\Listing;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Chat\StoreConversationRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['listing.photos', 'buyer', 'seller'])
            ->orderByDesc('last_message_at')
            ->get();

        return ApiResponse::success($conversations);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $listing = Listing::where('uuid', $request->validated('listing_id'))->firstOrFail();

        try {
            $conversation = $this->conversations->start($request->user(), $listing);
        } catch (ChatException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            $conversation->load(['listing.photos', 'buyer', 'seller']),
            'Conversation started.',
            201,
        );
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        return ApiResponse::success($conversation->load(['listing.photos', 'buyer', 'seller']));
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $this->conversations->markRead($conversation, $request->user());

        return ApiResponse::success(null, 'Marked as read.');
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;

        // Plain 404, not 403 - don't distinguish "doesn't exist" from
        // "exists but you're not in it", same instinct as
        // ListingController::show (relevant here for a blocked user
        // probing for a conversation they're not part of).
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }
    }
}
