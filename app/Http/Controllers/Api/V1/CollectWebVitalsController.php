<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CollectWebVitalsRequest;
use App\Services\PerformanceHub\CollectWebVitalsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CollectWebVitalsController extends Controller
{
    public function __invoke(CollectWebVitalsRequest $request, CollectWebVitalsAction $collectWebVitals): JsonResponse
    {
        $accepted = $collectWebVitals(
            $request->validated(),
            $request->header('X-Site-Ingest-Key'),
        );

        return response()->json([
            'accepted' => $accepted,
            'rejected' => 0,
            'ingestionBatchId' => Str::uuid()->toString(),
        ], 202);
    }
}
