<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetSiteMetricsRequest;
use App\Http\Resources\Api\V1\SiteResource;
use App\Services\PerformanceHub\GetSiteMetricsAction;
use Illuminate\Http\JsonResponse;

class GetSiteMetricsController extends Controller
{
    public function __invoke(string $siteId, GetSiteMetricsRequest $request, GetSiteMetricsAction $getSiteMetrics): JsonResponse
    {
        $result = $getSiteMetrics($siteId, $request->validated());

        return response()->json([
            'site' => SiteResource::make($result['site'])->resolve(),
            'metrics' => $result['metrics'],
        ]);
    }
}
