<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Domain\Chat\Exceptions\ChatException;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Services\MessageService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Chat\StoreMessageRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $query = $conversation->messages()
            ->with(['sender', 'offer.buyer', 'offer.seller', 'offer.listing', 'offer.order'])
            ->orderByDesc('created_at');

        if ($request->filled('before_id')) {
            $before = $conversation->messages()->where('uuid', $request->string('before_id'))->firstOrFail();
            $query->where('created_at', '<', $before->created_at);
        }

        return ApiResponse::success($query->limit(30)->get()->reverse()->values());
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        try {
            $message = $this->messages->send(
                $conversation,
                $request->user(),
                $request->validated(),
                $request->file('image'),
            );
        } catch (ChatException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($message->load('sender'), 'Message sent.', 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }
    }
}
