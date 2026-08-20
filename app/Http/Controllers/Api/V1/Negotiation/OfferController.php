<?php

namespace App\Http\Controllers\Api\V1\Negotiation;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Negotiation\Exceptions\OfferException;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Negotiation\Services\OfferService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Negotiation\StoreOfferRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OfferController extends Controller
{
    public function __construct(private readonly OfferService $offers) {}

    public function store(StoreOfferRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        try {
            $offer = $this->offers->create($conversation, $request->user(), (float) $request->validated('amount'));
        } catch (OfferException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($offer, 'Offer sent.', 201);
    }

    public function counter(StoreOfferRequest $request, Offer $offer): JsonResponse
    {
        $this->authorizeParticipant($request, $offer->conversation);

        try {
            $offer = $this->offers->counter($offer, $request->user(), (float) $request->validated('amount'));
        } catch (OfferException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($offer, 'Counter-offer sent.', 201);
    }

    public function accept(Request $request, Offer $offer): JsonResponse
    {
        $this->authorizeParticipant($request, $offer->conversation);

        try {
            $offer = $this->offers->accept($offer, $request->user());
        } catch (OfferException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($offer, 'Offer accepted.');
    }

    public function decline(Request $request, Offer $offer): JsonResponse
    {
        $this->authorizeParticipant($request, $offer->conversation);

        try {
            $offer = $this->offers->decline($offer, $request->user());
        } catch (OfferException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($offer, 'Offer declined.');
    }

    public function retract(Request $request, Offer $offer): JsonResponse
    {
        $this->authorizeParticipant($request, $offer->conversation);

        try {
            $offer = $this->offers->retract($offer, $request->user());
        } catch (OfferException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($offer, 'Offer retracted.');
    }

    private function respond(Offer $offer, string $message, int $status = 200): JsonResponse
    {
        return ApiResponse::success($offer->load(['buyer', 'seller', 'listing']), $message, $status);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        $userId = $request->user()->id;

        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }
    }
}
