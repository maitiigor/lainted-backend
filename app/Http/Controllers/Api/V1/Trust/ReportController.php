<?php

namespace App\Http\Controllers\Api\V1\Trust;

use App\Domain\Listings\Models\Listing;
use App\Domain\Trust\Models\Report;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\V1\Trust\StoreReportRequest;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $reportableType = $request->validated('reportable_type');
        $model = $reportableType === 'listing' ? Listing::class : User::class;

        $reportable = $model::where('uuid', $request->validated('reportable_id'))->firstOrFail();

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $model,
            'reportable_id' => $reportable->id,
            'reason' => $request->validated('reason'),
            'description' => $request->validated('description'),
        ]);

        return ApiResponse::success($report, 'Report submitted. Our team will review it.', 201);
    }
}
