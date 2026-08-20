<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Exceptions\SendboxException;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Orders\Models\Order;
use App\Support\Enums\DeliveryTier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hand-rolled Http:: calls to Sendbox's real shipping API, mirroring
 * PaystackService's exact style - no SDK package, config-driven base
 * URL (test/staging vs live), a domain exception on failure. Sendbox is
 * the only one of the product doc's three named logistics partners
 * (GIG, Kwik, Sendbox) with a genuinely self-serve API + sandbox; GIG/Kwik
 * both require a business conversation to enable API access, so they
 * stay manual/free-text LogisticsProvider options with no code here.
 */
class SendboxService
{
    private const SERVICE_CODE = 'standard';

    /**
     * No real per-listing weight/dimension capture exists anywhere in
     * this codebase (Listing.weight_tier is just an enum name) - these
     * are judgment-call estimates feeding a real API, the same spirit as
     * the old flat delivery-fee table just repositioned as an input
     * instead of being the final price.
     */
    private const PACKAGE_ESTIMATES = [
        'standard' => ['weight' => 2.0, 'dimension' => ['length' => 30, 'width' => 30, 'height' => 20]],
        'bulky' => ['weight' => 15.0, 'dimension' => ['length' => 60, 'width' => 50, 'height' => 50]],
        'furniture' => ['weight' => 40.0, 'dimension' => ['length' => 120, 'width' => 80, 'height' => 80]],
    ];

    public function quote(DeliveryAddress $origin, DeliveryAddress $destination, DeliveryTier $tier, float $itemValue): float
    {
        $response = $this->post(
            '/shipping/shipment_delivery_quote',
            $this->shipmentPayload($origin, $destination, $tier, $itemValue),
        );

        if (! $response->successful() || $response->json('status') !== 'successful') {
            Log::warning('Sendbox quote failed', ['status' => $response->status(), 'body' => $response->body()]);

            throw new SendboxException('Could not get a delivery quote.');
        }

        $fee = $response->json('rate.fee') ?? $response->json('rates.0.fee');

        if ($fee === null) {
            throw new SendboxException('Sendbox returned no usable rate.');
        }

        return (float) $fee;
    }

    /**
     * The real money-committing call - only ever invoked once, at
     * OrderService::markPrepared() time, never at checkout.
     */
    public function createShipment(Order $order): string
    {
        $origin = $order->seller->pickupAddress;
        $destination = $order->deliveryAddress;

        $payload = $this->shipmentPayload($origin, $destination, $order->delivery_tier, (float) $order->item_price);
        $payload['pickup_date'] = now()->addDay()->toDateString();

        $response = $this->post('/shipping/shipments', $payload);

        if (! $response->successful()) {
            Log::warning('Sendbox createShipment failed', [
                'order_uuid' => $order->uuid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new SendboxException('Could not book this delivery.');
        }

        $trackingCode = $response->json('tracking_code');

        if ($trackingCode === null) {
            throw new SendboxException('Sendbox returned no tracking code.');
        }

        return $trackingCode;
    }

    public function track(string $trackingCode): array
    {
        $response = $this->post('/shipping/tracking', ['code' => $trackingCode]);

        if (! $response->successful()) {
            throw new SendboxException('Could not check delivery status.');
        }

        return [
            'status_code' => $response->json('status.code'),
            'status_name' => $response->json('status.name'),
        ];
    }

    /**
     * Http::post() throws ConnectionException (DNS/SSL/timeout/refused)
     * BEFORE a response even exists - a different failure mode than an
     * unsuccessful HTTP response, and every caller here only ever wants
     * to catch one exception type. Converting it here means "Sendbox is
     * unreachable" degrades exactly like "Sendbox rejected the request"
     * everywhere this service is used (see resolveDeliveryFee(),
     * tryBookSendboxShipment() - neither must ever block a sale on this).
     */
    private function post(string $uri, array $payload): Response
    {
        try {
            return $this->client()->post($uri, $payload);
        } catch (ConnectionException $e) {
            throw new SendboxException('Could not reach Sendbox: '.$e->getMessage());
        }
    }

    private function client(): PendingRequest
    {
        return Http::withToken(config('services.sendbox.api_key'))
            ->baseUrl(rtrim((string) config('services.sendbox.base_url'), '/'))
            ->acceptJson();
    }

    private function shipmentPayload(DeliveryAddress $origin, DeliveryAddress $destination, DeliveryTier $tier, float $itemValue): array
    {
        $estimate = self::PACKAGE_ESTIMATES[$tier->value];

        return [
            'origin' => $this->addressPayload($origin),
            'destination' => $this->addressPayload($destination),
            'currency' => 'NGN',
            'weight' => $estimate['weight'],
            'dimension' => $estimate['dimension'],
            'incoming_option' => 'pickup',
            // Best-effort placeholder - Sendbox's exact `region` taxonomy
            // isn't in the public docs. Verify/correct once a real
            // sandbox key exists and an actual request can be inspected.
            'region' => $origin->lga->state,
            'service_type' => 'local',
            'package_type' => 'parcel',
            'total_value' => $itemValue,
            'channel_code' => 'api',
            'service_code' => self::SERVICE_CODE,
        ];
    }

    private function addressPayload(DeliveryAddress $address): array
    {
        return [
            'address' => $address->full_address_encrypted,
            'phone' => $address->recipient_phone_encrypted,
            'city' => $address->lga->name,
            'state' => $address->lga->state,
            'country' => 'NG',
        ];
    }
}
