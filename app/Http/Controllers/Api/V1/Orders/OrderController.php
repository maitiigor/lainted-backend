<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Listings\Models\Listing;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Orders\Exceptions\OrderException;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderService;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Services\PaystackService;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Orders\ConfirmDeliveryRequest;
use App\Http\Requests\Api\V1\Orders\MarkInTransitRequest;
use App\Http\Requests\Api\V1\Orders\StoreCheckoutRequest;
use App\Http\Requests\Api\V1\Payments\ConfirmPaymentRequest;
use App\Support\Enums\DeliveryTier;
use App\Support\Enums\LogisticsProvider;
use App\Support\Enums\PaymentMethod;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends Controller
{
    private const EAGER = ['listing.photos', 'buyer', 'seller', 'deliveryAddress.lga', 'escrow', 'disputes', 'reviews.reviewer', 'reviews.reviewee'];

    public function __construct(
        private readonly OrderService $orders,
        private readonly PaystackService $paystack,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $orders = Order::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(self::EAGER)
            ->latest()
            ->get()
            ->each(fn (Order $order) => $this->revealPickupCode($order, $userId));

        return ApiResponse::success($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        return ApiResponse::success(
            $this->revealPickupCode($order->load(self::EAGER), $request->user()->id),
        );
    }

    public function checkoutOffer(StoreCheckoutRequest $request, Offer $offer): JsonResponse
    {
        $userId = $request->user()->id;

        // Plain 404 for a total stranger to this offer - mirrors
        // OfferController::authorizeParticipant. A legitimate participant
        // in the wrong role (the seller trying to check out) still reaches
        // OrderService, which throws its own 422 for that.
        if ($offer->buyer_id !== $userId && $offer->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }

        return $this->checkout($request, $offer->listing, $offer);
    }

    public function checkoutListing(StoreCheckoutRequest $request, Listing $listing): JsonResponse
    {
        return $this->checkout($request, $listing, null);
    }

    public function confirmPayment(ConfirmPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $transaction = $this->paystack->verify($request->validated('reference'));
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        if ($transaction->order_id !== $order->id) {
            throw new NotFoundHttpException();
        }

        return $this->respond($order->refresh(), 'Payment confirmed.');
    }

    public function markPrepared(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $order = $this->orders->markPrepared($order, $request->user());
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Order marked as prepared.');
    }

    public function markInTransit(MarkInTransitRequest $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        $provider = $request->validated('logistics_provider') !== null
            ? LogisticsProvider::from($request->validated('logistics_provider'))
            : null;

        try {
            $order = $this->orders->markInTransit($order, $request->user(), $provider, $request->validated('tracking_reference'));
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Order marked as in transit.');
    }

    public function confirmDelivery(ConfirmDeliveryRequest $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $order = $this->orders->confirmDelivery($order, $request->user(), $request->validated('code'));
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Delivery confirmed.');
    }

    public function markDelivered(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $order = $this->orders->markDelivered($order, $request->user());
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Marked as delivered.');
    }

    public function confirmReceipt(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $order = $this->orders->confirmReceipt($order, $request->user());
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Receipt confirmed.');
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorizeParticipant($request, $order);

        try {
            $order = $this->orders->cancel($order, $request->user());
        } catch (OrderException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return $this->respond($request, $order,'Order cancelled.');
    }

    private function checkout(StoreCheckoutRequest $request, Listing $listing, ?Offer $offer): JsonResponse
    {
        $address = DeliveryAddress::where('uuid', $request->validated('delivery_address_id'))->firstOrFail();
        $tier = DeliveryTier::from($request->validated('delivery_tier'));
        $method = PaymentMethod::from($request->validated('payment_method'));

        try {
            $result = $offer !== null
                ? $this->orders->checkoutFromOffer($offer, $request->user(), $address, $tier, $method, $request->validated('email'))
                : $this->orders->checkoutFromListing($listing, $request->user(), $address, $tier, $method, $request->validated('email'));
        } catch (OrderException|PaymentException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        $order = $this->revealPickupCode($result['order']->load(self::EAGER), $request->user()->id);

        return ApiResponse::success([
            'order' => $order,
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'],
        ], 'Order created.', 201);
    }

    private function respond(Request $request, Order $order, string $message): JsonResponse
    {
        return ApiResponse::success(
            $this->revealPickupCode($order->load(self::EAGER), $request->user()->id),
            $message,
        );
    }

    private function revealPickupCode(Order $order, int $userId): Order
    {
        if ($order->buyer_id === $userId) {
            $order->makeVisible('pickup_code');
        }

        return $order;
    }

    /**
     * Plain 404, not 403 - don't distinguish "doesn't exist" from "exists
     * but you're not in it", same instinct as ConversationController's.
     */
    private function authorizeParticipant(Request $request, Order $order): void
    {
        $userId = $request->user()->id;

        if ($order->buyer_id !== $userId && $order->seller_id !== $userId) {
            throw new NotFoundHttpException();
        }
    }
}
