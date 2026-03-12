<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'siteId' => $this->resource->site_id,
            'environment' => $this->resource->environment,
            'buildId' => $this->resource->build_id,
            'releaseVersion' => $this->resource->release_version,
            'gitRef' => $this->resource->git_ref,
            'gitCommitSha' => $this->resource->git_commit_sha,
            'deployedAt' => $this->resource->deployed_at?->toISOString(),
        ];
    }
}
