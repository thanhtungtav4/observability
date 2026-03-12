<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSyntheticRunRequest;
use App\Http\Resources\Api\V1\SyntheticRunResource;
use App\Services\PerformanceHub\StoreSyntheticRunAction;
use Illuminate\Http\JsonResponse;

class StoreSyntheticRunController extends Controller
{
    public function __invoke(StoreSyntheticRunRequest $request, StoreSyntheticRunAction $storeSyntheticRun): JsonResponse
    {
        $syntheticRun = $storeSyntheticRun($request->validated());

        return (new SyntheticRunResource($syntheticRun->loadMissing('site')))
            ->response()
            ->setStatusCode(201);
    }
}
