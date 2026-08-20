<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Orders\StoreDeliveryAddressRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user()->deliveryAddresses()->with('lga')->get());
    }

    public function store(StoreDeliveryAddressRequest $request): JsonResponse
    {
        $address = $request->user()->deliveryAddresses()->create([
            'lga_id' => $request->validated('lga_id'),
            'landmark' => $request->validated('landmark'),
            'full_address_encrypted' => $request->validated('full_address'),
            'recipient_phone_encrypted' => $request->validated('recipient_phone'),
        ]);

        return ApiResponse::success($address->load('lga'), 'Delivery address saved.', 201);
    }
}
