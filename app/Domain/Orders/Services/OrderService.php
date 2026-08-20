<?php

namespace App\Domain\Orders\Services;

use App\Domain\Listings\Models\Listing;
use App\Domain\Negotiation\Models\Offer;
use App\Domain\Orders\Exceptions\OrderException;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Orders\Models\InstallationBooking;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Exceptions\SendboxException;
use App\Domain\Payments\Models\Escrow;
use App\Domain\Payments\Services\EscrowService;
use App\Domain\Payments\Services\PaystackService;
use App\Domain\Trust\Services\TrustMetricsService;
use App\Domain\Users\Models\User;
use App\Events\OfferUpdated;
use App\Jobs\SendOrderEventPush;
use App\Support\Enums\DeliveryTier;
use App\Support\Enums\DisputeStatus;
use App\Support\Enums\EscrowStatus;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\LogisticsProvider;
use App\Support\Enums\OfferStatus;
use App\Support\Enums\OrderPushEvent;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Flat fallback fee per tier - used whenever a real Sendbox quote
     * isn't available (seller has no pickup_address_id set yet, or the
     * API call itself fails) so a live courier integration never blocks
     * a sale. See resolveDeliveryFee(). Server-computed only either way -
     * checkout never accepts a client-submitted delivery fee.
     */
    private const DELIVERY_FEES = [
        'pickup' => 0.0,
        'standard' => 1500.0,
        'bulky' => 5000.0,
        'furniture' => 15000.0,
    ];

    private const PROTECTION_FEE_RATE = 0.015;

    public function __construct(
        private readonly PaystackService $paystack,
        private readonly EscrowService $escrow,
        private readonly SendboxService $sendbox,
        private readonly TrustMetricsService $trustMetrics,
    ) {}

    public function checkoutFromOffer(Offer $offer, User $buyer, DeliveryAddress $address, DeliveryTier $tier, PaymentMethod $method, ?string $email): array
    {
        if ($buyer->id !== $offer->buyer_id) {
            throw new OrderException('Only the buyer can check out this offer.');
        }

        // Lazily expire a stale-but-not-yet-swept row, same pattern as
        // OfferService::assertPendingAndNotExpired.
        if ($offer->status === OfferStatus::Accepted && $offer->checkout_expires_at?->isPast()) {
            $offer->update(['status' => OfferStatus::CheckoutExpired]);
        }

        if ($offer->status !== OfferStatus::Accepted) {
            throw new OrderException('This offer is not ready for checkout.');
        }

        // Deliberately no "already checked out" guard here beyond the
        // Accepted-status check above - an offer's order can only ever
        // be genuinely Confirmed/Completed while the offer itself has
        // already moved off Accepted (confirmPayment()/buildOrder()'s COD
        // branch both flip the offer's status the moment that happens),
        // so by the time this line runs, an existing order for this offer
        // can only be a still-Pending or already-Cancelled attempt -
        // buildOrder()'s own stale-order release below handles both.
        return $this->buildOrder($offer->listing, $offer, $buyer, $address, $tier, $method, $email);
    }

    public function checkoutFromListing(Listing $listing, User $buyer, DeliveryAddress $address, DeliveryTier $tier, PaymentMethod $method, ?string $email): array
    {
        if ($listing->is_negotiable) {
            throw new OrderException('This listing requires an offer - Buy Now is only for fixed-price listings.');
        }

        return $this->buildOrder($listing, null, $buyer, $address, $tier, $method, $email);
    }

    private function buildOrder(Listing $listing, ?Offer $offer, User $buyer, DeliveryAddress $address, DeliveryTier $tier, PaymentMethod $method, ?string $email): array
    {
        if ($buyer->id === $listing->seller_id) {
            throw new OrderException('You cannot buy your own listing.');
        }

        if ($address->user_id !== $buyer->id) {
            throw new OrderException('This delivery address does not belong to you.');
        }

        if ($method === PaymentMethod::CashOnDelivery) {
            $this->assertCodEligible($listing->seller);
        }

        if ($method === PaymentMethod::Online && empty($email)) {
            throw new OrderException('An email address is required to pay online.');
        }

        $isOnline = $method === PaymentMethod::Online;

        // The Paystack handshake happens INSIDE this transaction, not
        // after it commits - a failed initialize() throws, and Laravel's
        // DB::transaction() rolls back the Order/Listing/InstallationBooking
        // writes automatically. No separate compensating update needed,
        // and no window where a failed payment attempt leaves a Sold
        // listing with no way to buy it.
        $result = DB::transaction(function () use ($listing, $offer, $buyer, $address, $tier, $method, $email, $isOnline) {
            // Re-fetched and locked inside the transaction - closes the
            // double-sell race between two buyers checking out the same
            // fixed-price listing at once.
            $locked = Listing::whereKey($listing->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ListingStatus::Active) {
                $this->releaseAbandonedOrder($locked);
            }

            if ($locked->status !== ListingStatus::Active) {
                throw new OrderException('This listing is no longer available.');
            }

            $itemPrice = $offer !== null ? (float) $offer->amount : (float) $locked->price;
            $protectionFee = $isOnline ? round($itemPrice * self::PROTECTION_FEE_RATE, 2) : 0.0;
            $deliveryFee = $this->resolveDeliveryFee($locked->seller, $address, $tier, $itemPrice);

            $order = Order::create([
                'listing_id' => $locked->id,
                'offer_id' => $offer?->id,
                'delivery_address_id' => $address->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $locked->seller_id,
                'delivery_tier' => $tier,
                'payment_method' => $method,
                'item_price' => $itemPrice,
                'protection_fee' => $protectionFee,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $itemPrice + $protectionFee + $deliveryFee,
                'status' => $isOnline ? OrderStatus::Pending : OrderStatus::Confirmed,
                'pickup_code' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);

            $locked->update(['status' => ListingStatus::Sold, 'sold_at' => now()]);

            if ($locked->requires_installation) {
                InstallationBooking::create(['order_id' => $order->id]);
            }

            // COD is Confirmed immediately (no online payment to await) -
            // the underlying negotiation is done right here, not later via
            // confirmPayment(). Online orders complete their offer there
            // instead, once the payment is actually verified.
            if (! $isOnline && $offer !== null) {
                $offer->update(['status' => OfferStatus::Completed]);
            }

            $payment = $isOnline ? $this->paystack->initialize($order, $email) : null;

            return ['order' => $order, 'payment' => $payment];
        });

        $order = $result['order'];

        if (! $isOnline) {
            // No online payment to await - already Confirmed, no
            // Transaction/Escrow row (nothing for either to protect here).
            SendOrderEventPush::dispatch($order, OrderPushEvent::PaymentConfirmed);

            if ($offer !== null) {
                broadcast(new OfferUpdated($offer))->toOthers();
            }

            return ['order' => $order, 'authorization_url' => null, 'reference' => null];
        }

        return [
            'order' => $order,
            'authorization_url' => $result['payment']['authorization_url'],
            // The mobile client needs this both to hand to
            // flutter_paystack_plus (a required param, even though it's
            // functionally inert in authorizationUrl mode) and to call
            // back its own /payment/confirm afterwards.
            'reference' => $result['payment']['reference'],
        ];
    }

    /**
     * A listing flips to Sold the instant its first order is created,
     * not once payment is confirmed - so a buyer who backs out before
     * paying (or whose attempt the 30-minute orders:sweep-abandoned
     * cron already cancelled) would otherwise find the listing/offer
     * permanently stuck, unable to ever check out again, with no path
     * back to Active short of admin intervention. A still-Pending order
     * here is by definition an incomplete attempt, not a real sale - it
     * gets superseded by whatever new order is about to be created in
     * the same transaction, exactly like the sweep cron's own logic, just
     * triggered on-demand instead of waiting up to 30 minutes for it.
     * Mutates $locked in place so the caller's re-check sees Active.
     */
    private function releaseAbandonedOrder(Listing $locked): void
    {
        $pending = Order::where('listing_id', $locked->id)
            ->where('status', OrderStatus::Pending)
            ->first();

        if ($pending === null) {
            return;
        }

        // offer_id is a unique FK - detaching it (not just cancelling the
        // order) is what actually frees the offer up for a fresh order.
        $pending->update(['status' => OrderStatus::Cancelled, 'offer_id' => null]);
        $locked->update(['status' => ListingStatus::Active]);
    }

    /**
     * A real Sendbox quote if the seller has a pickup address saved,
     * otherwise the flat estimate - shared by buildOrder() (pricing the
     * buyer at checkout) and DeliveryQuoteController (the mobile
     * checkout screen's live-preview call), so the fallback logic only
     * lives in one place. Never throws - a failed quote is silently the
     * flat estimate, same principle as every other Sendbox touchpoint.
     */
    public function resolveDeliveryFee(User $seller, DeliveryAddress $destination, DeliveryTier $tier, float $itemPrice): float
    {
        if ($tier === DeliveryTier::Pickup || $seller->pickup_address_id === null) {
            return self::DELIVERY_FEES[$tier->value];
        }

        try {
            return $this->sendbox->quote($seller->pickupAddress, $destination, $tier, $itemPrice);
        } catch (SendboxException $e) {
            return self::DELIVERY_FEES[$tier->value];
        }
    }

    /**
     * Idempotent - called from PaystackService::verify(), which may itself
     * be invoked twice for the same reference (the mobile app's own
     * confirm call racing the Paystack webhook).
     */
    public function confirmPayment(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            return;
        }

        $order->update(['status' => OrderStatus::Confirmed]);

        Escrow::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => EscrowStatus::Held,
            'held_at' => now(),
        ]);

        // Marks the underlying negotiation as fulfilled - without this,
        // an accepted offer's chat card would keep showing "pay now"
        // indefinitely even after the buyer has actually paid.
        $offer = $order->offer;
        if ($offer !== null) {
            $offer->update(['status' => OfferStatus::Completed]);
            broadcast(new OfferUpdated($offer))->toOthers();
        }

        SendOrderEventPush::dispatch($order, OrderPushEvent::PaymentConfirmed);
    }

    public function markPrepared(Order $order, User $seller): Order
    {
        $this->assertSeller($order, $seller);

        if ($order->status !== OrderStatus::Confirmed) {
            throw new OrderException('This order is not ready to be marked prepared.');
        }

        $order->update(['status' => OrderStatus::Prepared]);

        // The real money-committing booking call - deliberately here, not
        // at checkout, since "prepared" is the seller's own "ready for
        // pickup" signal and a successful checkout-time quote never
        // guaranteed booking would also succeed later.
        if ($order->delivery_tier !== DeliveryTier::Pickup && $order->logistics_provider === null) {
            $this->tryBookSendboxShipment($order);
        }

        SendOrderEventPush::dispatch($order, OrderPushEvent::Prepared);

        return $order;
    }

    /**
     * Never lets a booking failure block the seller's own Prepared
     * transition above - on any failure this just leaves
     * logistics_provider/tracking_reference null, falling back to the
     * existing manual path (the seller types one in via markInTransit()).
     */
    private function tryBookSendboxShipment(Order $order): void
    {
        if ($order->seller->pickup_address_id === null) {
            return;
        }

        try {
            $trackingCode = $this->sendbox->createShipment($order);
            $order->update(['logistics_provider' => LogisticsProvider::Sendbox, 'tracking_reference' => $trackingCode]);
        } catch (SendboxException $e) {
            Log::warning('Sendbox booking failed', ['order_uuid' => $order->uuid, 'error' => $e->getMessage()]);
        }
    }

    /**
     * A tracking_reference is required for every tier except Pickup UNLESS
     * markPrepared() already booked a real Sendbox shipment for this order
     * (logistics_provider is already set) - this is the manual fallback
     * path for everything else: no rider/courier account, a failed
     * booking, or a genuinely manual courier (GIG/Kwik, still free-text).
     * Either way, a real tracking number/reference ends up recorded here
     * as the evidence a later dispute can check a false "never delivered"
     * claim against.
     */
    public function markInTransit(Order $order, User $seller, ?LogisticsProvider $provider, ?string $trackingReference): Order
    {
        $this->assertSeller($order, $seller);

        if ($order->status !== OrderStatus::Prepared) {
            throw new OrderException('This order is not ready to be marked in transit.');
        }

        if ($order->delivery_tier === DeliveryTier::Pickup) {
            throw new OrderException('Pickup orders skip transit - confirm delivery directly.');
        }

        if ($order->logistics_provider !== LogisticsProvider::Sendbox) {
            if (empty($trackingReference)) {
                throw new OrderException('A tracking reference is required for this delivery tier.');
            }

            $order->update(['logistics_provider' => $provider, 'tracking_reference' => $trackingReference]);
        }

        $order->update(['status' => OrderStatus::InTransit]);
        SendOrderEventPush::dispatch($order, OrderPushEvent::InTransit);

        return $order;
    }

    /**
     * Pickup tier only: seller enters the buyer's own 4-digit code at the
     * in-person handoff. Prepared -> Delivered directly, no transit leg.
     */
    public function confirmDelivery(Order $order, User $seller, string $code): Order
    {
        $this->assertSeller($order, $seller);

        if ($order->delivery_tier !== DeliveryTier::Pickup) {
            throw new OrderException('This order is not a pickup order.');
        }

        if ($order->status !== OrderStatus::Prepared) {
            throw new OrderException('This order is not ready for pickup confirmation.');
        }

        if ($order->pickup_code !== $code) {
            throw new OrderException('Incorrect pickup code.');
        }

        $this->markDeliveredNow($order, viaCode: true);

        return $order;
    }

    /**
     * Non-Pickup tiers only: the buyer self-reports receipt, since no
     * rider/courier account exists to confirm on the seller's behalf.
     */
    public function markDelivered(Order $order, User $buyer): Order
    {
        $this->assertBuyer($order, $buyer);

        if ($order->delivery_tier === DeliveryTier::Pickup) {
            throw new OrderException('Pickup orders are confirmed by the seller, not the buyer.');
        }

        if ($order->status !== OrderStatus::InTransit) {
            throw new OrderException('This order is not in transit.');
        }

        $this->markDeliveredNow($order, viaCode: false);

        return $order;
    }

    /**
     * SyncSendboxTracking's own "the courier reported delivered" path -
     * reuses the exact same transition markDelivered()'s buyer self-report
     * already goes through (dispute_deadline_at stamping included), just
     * triggered by the real courier's status instead of a manual tap.
     * Still leaves markDelivered() itself callable as a manual override.
     */
    public function syncDeliveredFromCourier(Order $order): void
    {
        $this->markDeliveredNow($order, viaCode: false);
    }

    private function markDeliveredNow(Order $order, bool $viaCode): void
    {
        $order->update([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
            'dispute_deadline_at' => now()->addHours(48),
            'pickup_code_entered_at' => $viaCode ? now() : null,
            'delivery_confirmed_code' => $viaCode,
        ]);

        SendOrderEventPush::dispatch($order, OrderPushEvent::Delivered);
    }

    /**
     * Buyer's inspection-window sign-off - releases escrow to the seller
     * (minus commission). Mutually exclusive with raising a dispute: once
     * a buyer confirms, the money has moved, so an open dispute blocks
     * this rather than the other way around.
     */
    public function confirmReceipt(Order $order, User $buyer): Order
    {
        $this->assertBuyer($order, $buyer);

        if ($order->status !== OrderStatus::Delivered) {
            throw new OrderException('This order has not been delivered yet.');
        }

        if ($this->hasActiveDispute($order)) {
            throw new OrderException('This order has an open dispute and cannot be confirmed yet.');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
                'buyer_acknowledged_at' => now(),
            ]);

            $this->escrow->release($order);
        });

        // total_sales just changed for this seller - same trigger as
        // DisputeService::finalize()'s resolved-dispute path.
        $this->trustMetrics->recompute($order->seller);

        SendOrderEventPush::dispatch($order, OrderPushEvent::Completed);

        return $order;
    }

    private function hasActiveDispute(Order $order): bool
    {
        return $order->disputes()
            ->whereNotIn('status', [
                DisputeStatus::ResolvedRefund,
                DisputeStatus::ResolvedRelease,
                DisputeStatus::ResolvedPartial,
            ])
            ->exists();
    }

    public function cancel(Order $order, User $buyer): Order
    {
        $this->assertBuyer($order, $buyer);

        if ($order->status !== OrderStatus::Pending) {
            throw new OrderException('This order can no longer be cancelled.');
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::Cancelled]);
            $order->listing?->update(['status' => ListingStatus::Active]);
        });

        return $order;
    }

    private function assertCodEligible(User $seller): void
    {
        $avgRating = $seller->reviewsReceived()->avg('rating');

        if (! $seller->is_verified || $avgRating === null || $avgRating < 4.5) {
            throw new OrderException('Cash on delivery is not available for this seller yet.');
        }
    }

    private function assertSeller(Order $order, User $seller): void
    {
        if ($seller->id !== $order->seller_id) {
            throw new OrderException('Only the seller can do this.');
        }
    }

    private function assertBuyer(Order $order, User $buyer): void
    {
        if ($buyer->id !== $order->buyer_id) {
            throw new OrderException('Only the buyer can do this.');
        }
    }
}
