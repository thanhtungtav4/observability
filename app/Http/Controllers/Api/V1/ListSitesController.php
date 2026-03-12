<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SiteResource;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class ListSitesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $sites = Site::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => SiteResource::collection($sites)->resolve(),
        ]);
    }
}
