<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'teamId' => $this->resource->team_id,
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'defaultEnvironment' => $this->resource->default_environment,
            'status' => $this->resource->status,
        ];
    }
}
