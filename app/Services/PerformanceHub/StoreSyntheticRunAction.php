<?php

namespace App\Services\PerformanceHub;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SyntheticRun;
use Illuminate\Validation\ValidationException;

class StoreSyntheticRunAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): SyntheticRun
    {
        $site = $this->resolveSite($payload['siteKey'] ?? null);

        $deployment = Deployment::query()
            ->where('site_id', $site->id)
            ->where('environment', $payload['environment'])
            ->where('build_id', $payload['buildId'])
            ->first();

        $pageGroup = PageGroup::query()
            ->where('site_id', $site->id)
            ->where('group_key', $payload['pageGroupKey'])
            ->first();

        return SyntheticRun::query()->create([
            'site_id' => $site->id,
            'deployment_id' => $deployment?->id,
            'page_group_id' => $pageGroup?->id,
            'page_group_key' => $payload['pageGroupKey'],
            'environment' => $payload['environment'],
            'occurred_at' => $payload['occurredAt'],
            'build_id' => $payload['buildId'],
            'release_version' => $deployment?->release_version,
            'git_ref' => $deployment?->git_ref,
            'git_commit_sha' => $deployment?->git_commit_sha,
            'runner' => 'lighthouse',
            'device_preset' => $payload['devicePreset'],
            'page_url' => $payload['pageUrl'],
            'page_path' => $payload['pagePath'],
            'performance_score' => $payload['performanceScore'],
            'accessibility_score' => $payload['accessibilityScore'] ?? null,
            'best_practices_score' => $payload['bestPracticesScore'] ?? null,
            'seo_score' => $payload['seoScore'] ?? null,
            'fcp_ms' => $payload['fcpMs'] ?? null,
            'lcp_ms' => $payload['lcpMs'] ?? null,
            'tbt_ms' => $payload['tbtMs'] ?? null,
            'cls_score' => $payload['clsScore'] ?? null,
            'speed_index_ms' => $payload['speedIndexMs'] ?? null,
            'inp_ms' => $payload['inpMs'] ?? null,
            'opportunities' => $payload['opportunities'],
            'diagnostics' => $payload['diagnostics'],
            'screenshot_url' => $payload['screenshotUrl'] ?? null,
            'trace_url' => $payload['traceUrl'] ?? null,
            'report_url' => $payload['reportUrl'] ?? null,
        ]);
    }

    private function resolveSite(mixed $siteKey): Site
    {
        $site = Site::query()
            ->where('slug', $siteKey)
            ->first();

        if (! $site instanceof Site) {
            throw ValidationException::withMessages([
                'siteKey' => 'The selected site key is invalid.',
            ]);
        }

        return $site;
    }
}
