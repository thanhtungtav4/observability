<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpsertDeploymentRequest;
use App\Http\Resources\Api\V1\DeploymentResource;
use App\Services\PerformanceHub\UpsertDeploymentAction;
use Illuminate\Http\JsonResponse;

class UpsertDeploymentController extends Controller
{
    public function __invoke(UpsertDeploymentRequest $request, UpsertDeploymentAction $upsertDeployment): JsonResponse
    {
        $deployment = $upsertDeployment($request->validated());

        return (new DeploymentResource($deployment))
            ->response()
            ->setStatusCode(201);
    }
}
