<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompareDeploymentsRequest;
use App\Http\Resources\Api\V1\DeploymentResource;
use App\Services\PerformanceHub\CompareDeploymentsAction;
use Illuminate\Http\JsonResponse;

class CompareDeploymentsController extends Controller
{
    public function __invoke(string $siteId, CompareDeploymentsRequest $request, CompareDeploymentsAction $compareDeployments): JsonResponse
    {
        $result = $compareDeployments($siteId, $request->validated());

        return response()->json([
            'currentDeployment' => DeploymentResource::make($result['currentDeployment'])->resolve(),
            'baselineDeployment' => DeploymentResource::make($result['baselineDeployment'])->resolve(),
            'metrics' => $result['metrics'],
        ]);
    }
}
