<?php

namespace App\Http\Controllers\Api\V1\Trust;

use App\Domain\Orders\Models\Order;
use App\Domain\Trust\Exceptions\DisputeException;
use App\Domain\Trust\Models\Dispute;
use App\Domain\Trust\Services\DisputeService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Trust\RespondToDisputeRequest;
use App\Http\Requests\Api\V1\Trust\StoreDisputeRequest;
use App\Http\Requests\Api\V1\Trust\UploadDisputePhotoRequest;
use App\Support\Enums\DisputeType;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DisputeController extends Controller
{
    private const EAGER = ['order.listing', 'order.buyer', 'order.seller', 'raisedBy', 'admin'];

    public function __construct(private readonly DisputeService $disputes) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $disputes = Dispute::whereHas('order', fn ($q) => $q->where('buyer_id', $userId)->orWhere('seller_id', $userId))
            ->with(self::EAGER)
            ->latest()
            ->get();

        return ApiResponse::success($disputes);
    }

    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        $this->authorizeParticipant($request, $dispute);

        return ApiResponse::success($dispute->load(self::EAGER));
    }

    public function store(StoreDisputeRequest $request, Order $order): JsonResponse
    {
        $userId = $request->user()->id;

        // Plain 404 for a total stranger to this order - mirrors
        // OrderController::checkoutOffer. A legitimate participant in the
        // wrong role (the seller trying to raise a dispute) still reaches
        // DisputeService, which throws its own 422 for that.
        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }

        try {
            $dispute = $this->disputes->raise(
                $order,
                $request->user(),
                DisputeType::from($request->validated('dispute_type')),
                $request->validated('buyer_statement'),
                $request->validated('buyer_photos'),
            );
        } catch (DisputeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($dispute->load(self::EAGER), 'Dispute raised.', 201);
    }

    public function respond(RespondToDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        $this->authorizeParticipant($request, $dispute);

        try {
            $dispute = $this->disputes->respondAsSeller(
                $dispute,
                $request->user(),
                $request->validated('seller_statement'),
                $request->validated('seller_photos'),
                $request->validated('self_resolution_offered') !== null
                    ? (float) $request->validated('self_resolution_offered')
                    : null,
            );
        } catch (DisputeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($dispute->load(self::EAGER), 'Response submitted.');
    }

    public function acceptResolution(Request $request, Dispute $dispute): JsonResponse
    {
        $this->authorizeParticipant($request, $dispute);

        try {
            $dispute = $this->disputes->acceptSelfResolution($dispute, $request->user());
        } catch (DisputeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($dispute->load(self::EAGER), 'Resolution accepted.');
    }

    /**
     * Flat, not tied to a specific dispute at upload time - a buyer/seller
     * uploads evidence photos before the dispute (or response) they'll be
     * attached to even exists yet, then submits the returned URLs as part
     * of that request's own buyer_photos/seller_photos array.
     */
    public function uploadPhoto(UploadDisputePhotoRequest $request): JsonResponse
    {
        $path = $request->file('photo')->store('disputes/evidence', 'public');

        // Not Storage::disk('public')->url() - that builds from the static
        // APP_URL config (localhost), which on the Android emulator means
        // the emulator itself, not the host machine, so the image is
        // never reachable from the device. Same fix as ProfileController's
        // photo upload: build from the actual request host instead.
        return ApiResponse::success(
            ['url' => $request->getSchemeAndHttpHost().'/storage/'.$path],
            'Photo uploaded.',
            201,
        );
    }

    public function rejectResolution(Request $request, Dispute $dispute): JsonResponse
    {
        $this->authorizeParticipant($request, $dispute);

        try {
            $dispute = $this->disputes->rejectSelfResolution($dispute, $request->user());
        } catch (DisputeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($dispute->load(self::EAGER), 'Escalated to our support team.');
    }

    /**
     * Plain 404, not 403 - same instinct as OrderController's own
     * authorizeParticipant.
     */
    private function authorizeParticipant(Request $request, Dispute $dispute): void
    {
        $userId = $request->user()->id;
        $order = $dispute->order;

        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }
    }
}
