<?php

namespace App\Http\Controllers\Api\V1\Locations;

use App\Domain\Locations\Models\Lga;
use App\Http\Controllers\Api\V1\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class LgaController extends Controller
{
    public function index(): JsonResponse
    {
        $lgas = Lga::orderBy('state')->orderBy('name')->get(['id', 'state', 'name']);

        return ApiResponse::success($lgas);
    }
}
