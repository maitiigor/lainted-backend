<?php

namespace App\Http\Controllers\Api\V1\Trust;

use App\Domain\Orders\Models\Order;
use App\Domain\Trust\Exceptions\ReviewException;
use App\Domain\Trust\Models\Review;
use App\Domain\Trust\Services\ReviewService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Trust\StoreReviewRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewController extends Controller
{
    private const EAGER = ['reviewer', 'reviewee'];

    public function __construct(private readonly ReviewService $reviews) {}

    /**
     * Public within the auth:sanctum group - any authenticated user can
     * view another user's reviews (this is what a public seller profile
     * screen reads from).
     */
    public function index(User $user): JsonResponse
    {
        $reviews = Review::where('reviewee_id', $user->id)
            ->with(self::EAGER)
            ->latest()
            ->paginate(20);

        return ApiResponse::success($reviews);
    }

    public function store(StoreReviewRequest $request, Order $order): JsonResponse
    {
        $userId = $request->user()->id;

        // Plain 404 for a stranger to this order - same instinct as
        // DisputeController::store.
        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }

        try {
            $review = $this->reviews->create(
                $order,
                $request->user(),
                (int) $request->validated('rating'),
                $request->validated('comment'),
            );
        } catch (ReviewException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($review->load(self::EAGER), 'Review submitted.', 201);
    }
}
