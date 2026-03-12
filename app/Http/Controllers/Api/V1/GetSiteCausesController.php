<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetSiteCausesRequest;
use App\Http\Resources\Api\V1\SiteResource;
use App\Services\PerformanceHub\GetSiteCauseSignalsAction;
use Illuminate\Http\JsonResponse;

class GetSiteCausesController extends Controller
{
    public function __invoke(string $siteId, GetSiteCausesRequest $request, GetSiteCauseSignalsAction $getSiteCauseSignals): JsonResponse
    {
        $result = $getSiteCauseSignals($siteId, $request->validated());

        return response()->json([
            'site' => SiteResource::make($result['site'])->resolve(),
            'signals' => $result['signals'],
        ]);
    }
}
