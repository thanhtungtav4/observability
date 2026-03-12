<?php

namespace App\Services\PerformanceHub;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\VitalsEvent;
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

                $vitalsEvent = VitalsEvent::query()->firstOrNew([
                    'site_id' => $site->id,
                    'source_event_id' => $event['eventId'],
                ]);

                $attributes = [
                    'source_event_id' => $event['eventId'],
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
                    'correlation_id' => $event['correlationId'] ?? null,
                    'trace_id' => $event['traceId'] ?? null,
                    'visitor_hash' => $event['visitorHash'] ?? data_get($event, 'tags.visitorHash'),
                    'attribution' => $event['attribution'],
                    'tags' => $event['tags'],
                ];

                if (array_key_exists('context', $event)) {
                    $attributes['context'] = $event['context'];
                }

                $vitalsEvent->fill($attributes);
                $vitalsEvent->save();

                $this->replaceResourceEvidence($vitalsEvent, $event);
                $this->replaceLongTaskEvidence($vitalsEvent, $event);
                $this->replaceJavascriptErrorEvidence($vitalsEvent, $event);
            });
        });

        return $events->count();
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function replaceResourceEvidence(VitalsEvent $vitalsEvent, array $event): void
    {
        if (! array_key_exists('resources', $event)) {
            return;
        }

        $vitalsEvent->resources()->delete();

        $records = collect($event['resources'] ?? [])
            ->map(function (array $resource): array {
                return [
                    'resource_url' => $resource['url'],
                    'resource_host' => $this->urlComponent($resource['url'], PHP_URL_HOST),
                    'resource_path' => $this->urlComponent($resource['url'], PHP_URL_PATH),
                    'resource_type' => $resource['resourceType'] ?? null,
                    'initiator_type' => $resource['initiatorType'] ?? null,
                    'duration_ms' => $resource['durationMs'] ?? null,
                    'transfer_size' => $resource['transferSize'] ?? null,
                    'decoded_body_size' => $resource['decodedBodySize'] ?? null,
                    'cache_state' => $resource['cacheState'] ?? null,
                    'priority' => $resource['priority'] ?? null,
                    'render_blocking' => $resource['renderBlocking'] ?? false,
                    'is_lcp_candidate' => $resource['isLcpCandidate'] ?? false,
                ];
            })
            ->all();

        if ($records !== []) {
            $vitalsEvent->resources()->createMany($records);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function replaceLongTaskEvidence(VitalsEvent $vitalsEvent, array $event): void
    {
        if (! array_key_exists('longTasks', $event)) {
            return;
        }

        $vitalsEvent->longTasks()->delete();

        $records = collect($event['longTasks'] ?? [])
            ->map(function (array $longTask): array {
                return [
                    'name' => $longTask['name'] ?? null,
                    'script_url' => $longTask['scriptUrl'] ?? null,
                    'script_host' => $this->urlComponent($longTask['scriptUrl'] ?? null, PHP_URL_HOST),
                    'invoker_type' => $longTask['invokerType'] ?? null,
                    'container_selector' => $longTask['containerSelector'] ?? null,
                    'start_time_ms' => $longTask['startTimeMs'] ?? null,
                    'duration_ms' => $longTask['durationMs'],
                    'blocking_duration_ms' => $longTask['blockingDurationMs'] ?? null,
                ];
            })
            ->all();

        if ($records !== []) {
            $vitalsEvent->longTasks()->createMany($records);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function replaceJavascriptErrorEvidence(VitalsEvent $vitalsEvent, array $event): void
    {
        if (! array_key_exists('errors', $event)) {
            return;
        }

        $vitalsEvent->javascriptErrors()->delete();

        $records = collect($event['errors'] ?? [])
            ->map(function (array $error): array {
                $sourceUrl = $error['sourceUrl'] ?? null;

                return [
                    'fingerprint' => sha1(mb_strtolower(implode('|', [
                        (string) ($error['name'] ?? ''),
                        (string) ($error['message'] ?? ''),
                        (string) ($sourceUrl ?? ''),
                    ]))),
                    'name' => $error['name'] ?? null,
                    'message' => $error['message'],
                    'source_url' => $sourceUrl,
                    'source_host' => $this->urlComponent($sourceUrl, PHP_URL_HOST),
                    'line_number' => $error['lineNumber'] ?? null,
                    'column_number' => $error['columnNumber'] ?? null,
                    'handled' => $error['handled'] ?? false,
                    'stack' => $error['stack'] ?? null,
                ];
            })
            ->all();

        if ($records !== []) {
            $vitalsEvent->javascriptErrors()->createMany($records);
        }
    }

    private function urlComponent(?string $url, int $component): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $value = parse_url($url, $component);

        return is_string($value) && $value !== '' ? $value : null;
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
