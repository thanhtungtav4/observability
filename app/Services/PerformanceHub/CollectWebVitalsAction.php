<?php

namespace App\Services\PerformanceHub;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CollectWebVitalsAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload, ?string $ingestKey): int
    {
        $site = $this->resolveSite($payload['siteKey'] ?? null);

        if (! is_string($ingestKey) || $ingestKey === '' || ! $site->matchesIngestKey($ingestKey)) {
            throw new UnauthorizedHttpException('SiteIngestKey', 'Authentication failed.');
        }

        $events = collect($payload['events']);
        $environment = $payload['environment'];

        $deployments = Deployment::query()
            ->where('site_id', $site->id)
            ->where('environment', $environment)
            ->whereIn('build_id', $events->map(fn (array $event): string => $event['release']['buildId'])->unique()->all())
            ->get()
            ->keyBy('build_id');

        $pageGroups = PageGroup::query()
            ->where('site_id', $site->id)
            ->whereIn('group_key', $events->map(fn (array $event): string => $event['pageGroupKey'])->unique()->all())
            ->get()
            ->keyBy('group_key');

        DB::transaction(function () use ($deployments, $environment, $events, $pageGroups, $site): void {
            $events->each(function (array $event) use ($deployments, $environment, $pageGroups, $site): void {
                $release = $event['release'];
                $deployment = $deployments->get($release['buildId']);
                $pageGroup = $pageGroups->get($event['pageGroupKey']);

                VitalsEvent::query()->create([
                    'site_id' => $site->id,
                    'deployment_id' => $deployment?->id,
                    'page_group_id' => $pageGroup?->id,
                    'page_group_key' => $event['pageGroupKey'],
                    'environment' => $environment,
                    'occurred_at' => $event['occurredAt'],
                    'build_id' => $release['buildId'],
                    'release_version' => $release['releaseVersion'] ?? null,
                    'git_ref' => $release['gitRef'] ?? null,
                    'git_commit_sha' => $release['gitCommitSha'] ?? null,
                    'metric_name' => $event['metricName'],
                    'metric_unit' => $event['metricUnit'],
                    'metric_value' => $event['metricValue'],
                    'delta_value' => $event['deltaValue'] ?? null,
                    'rating' => $event['rating'],
                    'url' => $event['url'],
                    'path' => $event['path'],
                    'page_title' => $event['pageTitle'] ?? null,
                    'device_class' => $event['deviceClass'],
                    'navigation_type' => $event['navigationType'] ?? null,
                    'browser_name' => $event['browserName'] ?? null,
                    'browser_version' => $event['browserVersion'] ?? null,
                    'os_name' => $event['osName'] ?? null,
                    'country_code' => $event['countryCode'] ?? data_get($event, 'tags.countryCode'),
                    'effective_connection_type' => $event['effectiveConnectionType'] ?? null,
                    'round_trip_time_ms' => $event['roundTripTimeMs'] ?? null,
                    'downlink_mbps' => $event['downlinkMbps'] ?? null,
                    'session_id' => $event['sessionId'] ?? null,
                    'page_view_id' => $event['pageViewId'] ?? null,
                    'visitor_hash' => $event['visitorHash'] ?? data_get($event, 'tags.visitorHash'),
                    'attribution' => $event['attribution'],
                    'tags' => $event['tags'],
                ]);
            });
        });

        return $events->count();
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
