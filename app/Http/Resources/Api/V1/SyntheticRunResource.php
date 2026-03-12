<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyntheticRunResource extends JsonResource
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
            'siteKey' => $this->resource->site?->slug,
            'environment' => $this->resource->environment,
            'buildId' => $this->resource->build_id,
            'occurredAt' => $this->resource->occurred_at?->toISOString(),
            'pageUrl' => $this->resource->page_url,
            'pagePath' => $this->resource->page_path,
            'pageGroupKey' => $this->resource->page_group_key,
            'devicePreset' => $this->resource->device_preset,
            'performanceScore' => $this->resource->performance_score,
            'accessibilityScore' => $this->resource->accessibility_score,
            'bestPracticesScore' => $this->resource->best_practices_score,
            'seoScore' => $this->resource->seo_score,
            'fcpMs' => $this->resource->fcp_ms,
            'lcpMs' => $this->resource->lcp_ms,
            'tbtMs' => $this->resource->tbt_ms,
            'clsScore' => $this->resource->cls_score,
            'speedIndexMs' => $this->resource->speed_index_ms,
            'inpMs' => $this->resource->inp_ms,
            'screenshotUrl' => $this->resource->screenshot_url,
            'traceUrl' => $this->resource->trace_url,
            'reportUrl' => $this->resource->report_url,
            'opportunities' => $this->resource->opportunities,
            'diagnostics' => $this->resource->diagnostics,
        ];
    }
}
