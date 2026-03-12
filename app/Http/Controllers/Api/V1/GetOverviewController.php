<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetOverviewRequest;
use App\Services\PerformanceHub\GetOverviewAction;
use Illuminate\Http\JsonResponse;

class GetOverviewController extends Controller
{
    public function __invoke(GetOverviewRequest $request, GetOverviewAction $getOverview): JsonResponse
    {
        return response()->json($getOverview($request->validated()));
    }
}
