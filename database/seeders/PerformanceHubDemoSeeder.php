<?php

namespace Database\Seeders;

use App\Models\Deployment;
use App\Models\PageGroup;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SyntheticRun;
use App\Models\Team;
use App\Models\VitalsEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PerformanceHubDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SAMPLES_PER_SLICE = 3;

    private const WINDOW_DAYS = 7;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anchorDay = now()->startOfDay();

        $team = Team::factory()->create([
            'slug' => 'observability-lab',
            'name' => 'Observability Lab',
        ]);

        foreach ($this->siteDefinitions() as $definition) {
            $this->seedSite($team, $definition, $anchorDay->copy());
        }
    }

    /**
     * @param  array{
     *     slug: string,
     *     name: string,
     *     domain: string,
     *     ingestKey: string,
     *     timezone: string,
     *     trendVariance: float,
     *     pageGroups: list<array{key: string, label: string, path: string, weight: float}>,
     *     deployments: list<array{
     *         buildId: string,
     *         releaseVersion: string,
     *         gitRef: string,
     *         gitCommitSha: string,
     *         daysAgo: int,
     *         actorName: string,
     *         ciSource: string,
     *         profile: array<string, float>
     *     }>
     * }  $definition
     */
    private function seedSite(Team $team, array $definition, Carbon $anchorDay): void
    {
        $site = Site::factory()
            ->for($team)
            ->withIngestKey($definition['ingestKey'])
            ->create([
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'timezone' => $definition['timezone'],
            ]);

        SiteDomain::factory()
            ->for($site)
            ->primary()
            ->create([
                'domain' => $definition['domain'],
                'environment' => 'production',
            ]);

        $pageGroups = collect($definition['pageGroups'])
            ->mapWithKeys(function (array $pageGroup) use ($site): array {
                $patternType = $pageGroup['path'] === '/' ? 'literal' : 'prefix';

                return [
                    $pageGroup['key'] => [
                        'model' => PageGroup::factory()
                            ->for($site)
                            ->create([
                                'group_key' => $pageGroup['key'],
                                'label' => $pageGroup['label'],
                                'pattern_type' => $patternType,
                                'match_rules' => [
                                    [
                                        'type' => $patternType,
                                        'value' => $pageGroup['path'],
                                    ],
                                ],
                            ]),
                        'path' => $pageGroup['path'],
                        'weight' => $pageGroup['weight'],
                    ],
                ];
            });

        $deployments = collect($definition['deployments'])
            ->map(function (array $deployment) use ($anchorDay, $site): array {
                $deployedAt = $anchorDay
                    ->copy()
                    ->subDays($deployment['daysAgo'])
                    ->setTime(9 + $deployment['daysAgo'], 15);

                return [
                    'model' => Deployment::factory()
                        ->for($site)
                        ->create([
                            'environment' => 'production',
                            'build_id' => $deployment['buildId'],
                            'release_version' => $deployment['releaseVersion'],
                            'git_ref' => $deployment['gitRef'],
                            'git_commit_sha' => $deployment['gitCommitSha'],
                            'deployed_at' => $deployedAt,
                            'actor_name' => $deployment['actorName'],
                            'ci_source' => $deployment['ciSource'],
                            'metadata' => [
                                'channel' => 'demo-seed',
                                'commitUrl' => 'https://github.com/example/repo/commit/'.$deployment['gitCommitSha'],
                            ],
                        ]),
                    'profile' => $deployment['profile'],
                ];
            })
            ->sortBy(fn (array $deployment): int => $deployment['model']->deployed_at->getTimestamp())
            ->values();

        $this->seedFieldEvents(
            $site,
            $definition['domain'],
            $pageGroups,
            $deployments,
            $definition['trendVariance'],
            $anchorDay,
        );

        $this->seedSyntheticRuns($site, $definition['domain'], $pageGroups, $deployments);
    }

    /**
     * @param  Collection<string, array{model: PageGroup, path: string, weight: float}>  $pageGroups
     * @param  Collection<int, array{model: Deployment, profile: array<string, float>}>  $deployments
     */
    private function seedFieldEvents(
        Site $site,
        string $domain,
        Collection $pageGroups,
        Collection $deployments,
        float $trendVariance,
        Carbon $anchorDay,
    ): void {
        $deviceWeights = [
            'mobile' => 1.0,
            'desktop' => 0.82,
        ];

        foreach (range(self::WINDOW_DAYS - 1, 0) as $daysAgo) {
            $occurredAt = $anchorDay->copy()->subDays($daysAgo)->setTime(13, 0);
            $activeDeployment = $this->activeDeploymentFor($deployments, $occurredAt);
            $deploymentAge = max($activeDeployment['model']->deployed_at->diffInDays($occurredAt), 0);
            $trendStep = (self::WINDOW_DAYS - 1) - $daysAgo;

            foreach ($pageGroups as $pageGroupKey => $pageGroup) {
                foreach ($deviceWeights as $deviceClass => $deviceWeight) {
                    foreach ($this->metricCatalog() as $metricName => $metricConfig) {
                        foreach (range(0, self::SAMPLES_PER_SLICE - 1) as $sampleIndex) {
                            $metricValue = $this->metricValue(
                                $metricName,
                                $activeDeployment['profile'][$metricName],
                                $pageGroup['weight'],
                                $deviceWeight,
                                $deploymentAge,
                                $trendStep,
                                $sampleIndex,
                                $trendVariance,
                            );

                            $event = VitalsEvent::factory()
                                ->for($site)
                                ->for($activeDeployment['model'], 'deployment')
                                ->for($pageGroup['model'], 'pageGroup')
                                ->create([
                                    'page_group_key' => $pageGroupKey,
                                    'environment' => 'production',
                                    'occurred_at' => $occurredAt->copy()->addMinutes($sampleIndex * 11),
                                    'build_id' => $activeDeployment['model']->build_id,
                                    'release_version' => $activeDeployment['model']->release_version,
                                    'git_ref' => $activeDeployment['model']->git_ref,
                                    'git_commit_sha' => $activeDeployment['model']->git_commit_sha,
                                    'metric_name' => $metricName,
                                    'metric_unit' => $metricConfig['unit'],
                                    'metric_value' => $metricValue,
                                    'delta_value' => $metricValue,
                                    'rating' => $this->ratingFor($metricName, $metricValue),
                                    'url' => 'https://'.$domain.$pageGroup['path'],
                                    'path' => $pageGroup['path'],
                                    'page_title' => $site->name.' · '.$pageGroup['model']->label,
                                    'device_class' => $deviceClass,
                                    'navigation_type' => $sampleIndex === 0 ? 'navigate' : 'reload',
                                    'browser_name' => 'Chrome',
                                    'browser_version' => $deviceClass === 'desktop' ? '134' : '133',
                                    'os_name' => $deviceClass === 'desktop' ? 'macOS' : 'Android',
                                    'country_code' => 'VN',
                                    'effective_connection_type' => $deviceClass === 'desktop' ? 'wifi' : '4g',
                                    'round_trip_time_ms' => $deviceClass === 'desktop' ? 42 : 118,
                                    'downlink_mbps' => $deviceClass === 'desktop' ? 48.5 : 14.2,
                                    'session_id' => (string) Str::uuid(),
                                    'page_view_id' => (string) Str::uuid(),
                                    'correlation_id' => (string) Str::uuid(),
                                    'trace_id' => (string) Str::uuid(),
                                    'visitor_hash' => sha1($site->slug.'|'.$pageGroupKey.'|'.$deviceClass.'|'.$daysAgo.'|'.$sampleIndex),
                                    'attribution' => $this->attributionFor($metricName),
                                    'tags' => [
                                        'site' => $site->slug,
                                        'pageGroupKey' => $pageGroupKey,
                                        'deviceClass' => $deviceClass,
                                        'seed' => 'demo',
                                    ],
                                    'context' => [
                                        'collectorVersion' => 'demo-seed-1',
                                        'hydrationPhase' => $sampleIndex === 0 ? 'before-hydration' : 'after-hydration',
                                        'routeTransitionType' => $sampleIndex === 0 ? 'document' : 'spa',
                                        'apiRequestKeys' => ['search-api', 'availability-api'],
                                    ],
                                ]);

                            $this->attachEvidenceFor($event, $metricName, $domain, $pageGroup['path'], $deviceClass, $sampleIndex);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<string, array{model: PageGroup, path: string, weight: float}>  $pageGroups
     * @param  Collection<int, array{model: Deployment, profile: array<string, float>}>  $deployments
     */
    private function seedSyntheticRuns(
        Site $site,
        string $domain,
        Collection $pageGroups,
        Collection $deployments,
    ): void {
        $deviceWeights = [
            'mobile' => 1.0,
            'desktop' => 0.78,
        ];

        foreach ($deployments as $deployment) {
            foreach ($pageGroups as $pageGroupKey => $pageGroup) {
                foreach ($deviceWeights as $devicePreset => $deviceWeight) {
                    $lcp = (int) round($deployment['profile']['lcp'] * $pageGroup['weight'] * $deviceWeight);
                    $inp = (int) round($deployment['profile']['inp'] * $pageGroup['weight'] * $deviceWeight);
                    $cls = round($deployment['profile']['cls'] * $pageGroup['weight'] * ($devicePreset === 'desktop' ? 0.86 : 1.0), 3);
                    $fcp = (int) round($deployment['profile']['fcp'] * $pageGroup['weight'] * $deviceWeight);
                    $ttfb = (int) round($deployment['profile']['ttfb'] * $pageGroup['weight'] * ($devicePreset === 'desktop' ? 0.92 : 1.0));

                    SyntheticRun::factory()
                        ->for($site)
                        ->for($deployment['model'], 'deployment')
                        ->for($pageGroup['model'], 'pageGroup')
                        ->create([
                            'page_group_key' => $pageGroupKey,
                            'environment' => 'production',
                            'occurred_at' => $deployment['model']->deployed_at->copy()->addHours($devicePreset === 'desktop' ? 10 : 6),
                            'build_id' => $deployment['model']->build_id,
                            'release_version' => $deployment['model']->release_version,
                            'git_ref' => $deployment['model']->git_ref,
                            'git_commit_sha' => $deployment['model']->git_commit_sha,
                            'device_preset' => $devicePreset,
                            'page_url' => 'https://'.$domain.$pageGroup['path'],
                            'page_path' => $pageGroup['path'],
                            'performance_score' => $this->performanceScoreFor($lcp, $inp, $cls, $ttfb),
                            'accessibility_score' => round($devicePreset === 'desktop' ? 96.0 : 94.0, 1),
                            'best_practices_score' => round($pageGroup['weight'] > 1.1 ? 92.0 : 95.0, 1),
                            'seo_score' => 97.0,
                            'fcp_ms' => $fcp,
                            'lcp_ms' => $lcp,
                            'tbt_ms' => (int) round($inp * 0.62),
                            'cls_score' => $cls,
                            'speed_index_ms' => (int) round($fcp * 1.28),
                            'inp_ms' => $inp,
                            'opportunities' => $this->opportunitiesFor($lcp, $inp),
                            'diagnostics' => [
                                'userAgent' => 'Lighthouse demo runner',
                                'networkRequests' => $devicePreset === 'desktop' ? 84 : 97,
                            ],
                            'screenshot_url' => 'https://'.$domain.'/reports/'.$deployment['model']->build_id.'/'.$pageGroupKey.'/'.$devicePreset.'/screenshot.jpg',
                            'trace_url' => 'https://'.$domain.'/reports/'.$deployment['model']->build_id.'/'.$pageGroupKey.'/'.$devicePreset.'/trace.json',
                            'report_url' => 'https://'.$domain.'/reports/'.$deployment['model']->build_id.'/'.$pageGroupKey.'/'.$devicePreset.'/index.html',
                        ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, array{model: Deployment, profile: array<string, float>}>  $deployments
     * @return array{model: Deployment, profile: array<string, float>}
     */
    private function activeDeploymentFor(Collection $deployments, Carbon $occurredAt): array
    {
        return $deployments->last(
            fn (array $deployment): bool => $deployment['model']->deployed_at->lte($occurredAt)
        ) ?? $deployments->firstOrFail();
    }

    private function metricValue(
        string $metricName,
        float $baseValue,
        float $pageWeight,
        float $deviceWeight,
        int $deploymentAge,
        int $trendStep,
        int $sampleIndex,
        float $trendVariance,
    ): float {
        $warmUpWeight = max(0.95, 1 - ($deploymentAge * 0.018));
        $trendWeight = 1 + (($trendStep - 3) * $trendVariance);
        $sampleWeight = 1 + (($sampleIndex - 1) * ($metricName === 'cls' ? 0.06 : 0.035));

        $value = $baseValue * $pageWeight * $deviceWeight * $warmUpWeight * $trendWeight * $sampleWeight;

        if ($metricName === 'cls') {
            return round($value, 3);
        }

        return round($value, 0);
    }

    private function ratingFor(string $metricName, float $metricValue): string
    {
        $thresholds = $this->metricCatalog()[$metricName];

        if ($metricValue <= $thresholds['good']) {
            return 'good';
        }

        if ($metricValue <= $thresholds['poor']) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * @return array<string, array{unit: string, good: float, poor: float}>
     */
    private function metricCatalog(): array
    {
        return [
            'lcp' => ['unit' => 'ms', 'good' => 2500.0, 'poor' => 4000.0],
            'inp' => ['unit' => 'ms', 'good' => 200.0, 'poor' => 500.0],
            'cls' => ['unit' => 'score', 'good' => 0.1, 'poor' => 0.25],
            'fcp' => ['unit' => 'ms', 'good' => 1800.0, 'poor' => 3000.0],
            'ttfb' => ['unit' => 'ms', 'good' => 800.0, 'poor' => 1800.0],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributionFor(string $metricName): array
    {
        return match ($metricName) {
            'lcp' => [
                'element' => 'img.hero-poster',
                'timeToFirstByte' => 520,
                'resourceLoadDelay' => 180,
                'resourceLoadDuration' => 1440,
                'elementRenderDelay' => 260,
            ],
            'inp' => [
                'interactionTarget' => 'button[data-booking-cta]',
                'inputDelay' => 46,
                'processingDuration' => 188,
                'presentationDelay' => 74,
            ],
            'cls' => [
                'shiftSource' => '#promo-banner',
                'largestShiftTarget' => '#promo-banner',
            ],
            'fcp' => ['renderPath' => 'document'],
            'ttfb' => ['requestRoute' => 'edge-cache'],
            default => [],
        };
    }

    private function attachEvidenceFor(
        VitalsEvent $event,
        string $metricName,
        string $domain,
        string $pagePath,
        string $deviceClass,
        int $sampleIndex,
    ): void {
        $pageSlug = trim($pagePath, '/');
        $pageSlug = $pageSlug === '' ? 'home' : str_replace('/', '-', $pageSlug);

        if (in_array($metricName, ['lcp', 'fcp'], true)) {
            $event->resources()->createMany([
                [
                    'resource_url' => 'https://assets.'.$domain.'/images/'.$pageSlug.'-hero.jpg',
                    'resource_host' => 'assets.'.$domain,
                    'resource_path' => '/images/'.$pageSlug.'-hero.jpg',
                    'resource_type' => 'image',
                    'initiator_type' => 'img',
                    'duration_ms' => $deviceClass === 'desktop' ? 420 : 1180,
                    'transfer_size' => $deviceClass === 'desktop' ? 420000 : 690000,
                    'decoded_body_size' => $deviceClass === 'desktop' ? 880000 : 1180000,
                    'cache_state' => $sampleIndex === 0 ? 'miss' : 'revalidated',
                    'priority' => 'high',
                    'render_blocking' => false,
                    'is_lcp_candidate' => $metricName === 'lcp',
                ],
                [
                    'resource_url' => 'https://app.'.$domain.'/build/app.css',
                    'resource_host' => 'app.'.$domain,
                    'resource_path' => '/build/app.css',
                    'resource_type' => 'stylesheet',
                    'initiator_type' => 'link',
                    'duration_ms' => $deviceClass === 'desktop' ? 84 : 230,
                    'transfer_size' => 92000,
                    'decoded_body_size' => 160000,
                    'cache_state' => $sampleIndex === 0 ? 'miss' : 'hit',
                    'priority' => 'high',
                    'render_blocking' => true,
                    'is_lcp_candidate' => false,
                ],
            ]);
        }

        if ($metricName === 'inp') {
            $event->longTasks()->createMany([
                [
                    'name' => 'script-evaluation',
                    'script_url' => 'https://app.'.$domain.'/build/checkout.js',
                    'script_host' => 'app.'.$domain,
                    'invoker_type' => 'event-listener',
                    'container_selector' => 'button[data-booking-cta]',
                    'start_time_ms' => 1240 + ($sampleIndex * 40),
                    'duration_ms' => $deviceClass === 'desktop' ? 180 : 460,
                    'blocking_duration_ms' => $deviceClass === 'desktop' ? 92 : 280,
                ],
                [
                    'name' => 'layout',
                    'script_url' => 'https://app.'.$domain.'/build/forms.js',
                    'script_host' => 'app.'.$domain,
                    'invoker_type' => 'promise',
                    'container_selector' => '#booking-form',
                    'start_time_ms' => 1390 + ($sampleIndex * 50),
                    'duration_ms' => $deviceClass === 'desktop' ? 124 : 310,
                    'blocking_duration_ms' => $deviceClass === 'desktop' ? 48 : 170,
                ],
            ]);

            if ($sampleIndex === 0) {
                $event->javascriptErrors()->create([
                    'fingerprint' => sha1('hydrate-mismatch|'.$pagePath),
                    'name' => 'TypeError',
                    'message' => 'Cannot read properties of undefined during hydration',
                    'source_url' => 'https://app.'.$domain.'/build/checkout.js',
                    'source_host' => 'app.'.$domain,
                    'line_number' => 182,
                    'column_number' => 24,
                    'handled' => false,
                    'stack' => "TypeError: Cannot read properties of undefined\n    at hydrateBookingCta (checkout.js:182:24)",
                ]);
            }
        }
    }

    /**
     * @return list<array{id: string, title: string}>
     */
    private function opportunitiesFor(int $lcp, int $inp): array
    {
        $opportunities = [];

        if ($lcp > 2500) {
            $opportunities[] = [
                'id' => 'render-blocking-resources',
                'title' => 'Eliminate render-blocking resources',
            ];
        }

        if ($inp > 200) {
            $opportunities[] = [
                'id' => 'main-thread-tasks',
                'title' => 'Reduce main-thread work during interaction',
            ];
        }

        if ($opportunities === []) {
            $opportunities[] = [
                'id' => 'keep-going',
                'title' => 'Release looks stable across core rendering paths',
            ];
        }

        return $opportunities;
    }

    private function performanceScoreFor(int $lcp, int $inp, float $cls, int $ttfb): float
    {
        $score = 100
            - max($lcp - 1800, 0) / 60
            - max($inp - 120, 0) / 7
            - max($ttfb - 400, 0) / 24
            - max($cls - 0.04, 0) * 180;

        return round(max(38, min(99, $score)), 1);
    }

    /**
     * @return list<array{
     *     slug: string,
     *     name: string,
     *     domain: string,
     *     ingestKey: string,
     *     timezone: string,
     *     trendVariance: float,
     *     pageGroups: list<array{key: string, label: string, path: string, weight: float}>,
     *     deployments: list<array{
     *         buildId: string,
     *         releaseVersion: string,
     *         gitRef: string,
     *         gitCommitSha: string,
     *         daysAgo: int,
     *         actorName: string,
     *         ciSource: string,
     *         profile: array<string, float>
     *     }>
     * }>
     */
    private function siteDefinitions(): array
    {
        return [
            [
                'slug' => 'smile-clinic',
                'name' => 'Smile Clinic',
                'domain' => 'smile-clinic.test',
                'ingestKey' => 'pilot-ingest-key',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'trendVariance' => 0.012,
                'pageGroups' => [
                    ['key' => 'home', 'label' => 'Home', 'path' => '/', 'weight' => 1.00],
                    ['key' => 'pricing', 'label' => 'Pricing', 'path' => '/pricing', 'weight' => 1.08],
                    ['key' => 'booking', 'label' => 'Booking Flow', 'path' => '/book', 'weight' => 1.16],
                ],
                'deployments' => [
                    [
                        'buildId' => 'smile-2026.03.01',
                        'releaseVersion' => '2026.03.01',
                        'gitRef' => 'main',
                        'gitCommitSha' => '1111111111111111111111111111111111111111',
                        'daysAgo' => 6,
                        'actorName' => 'Linh Nguyen',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 2180.0,
                            'inp' => 162.0,
                            'cls' => 0.052,
                            'fcp' => 1160.0,
                            'ttfb' => 420.0,
                        ],
                    ],
                    [
                        'buildId' => 'smile-2026.03.02',
                        'releaseVersion' => '2026.03.02',
                        'gitRef' => 'main',
                        'gitCommitSha' => '2222222222222222222222222222222222222222',
                        'daysAgo' => 3,
                        'actorName' => 'Linh Nguyen',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 2470.0,
                            'inp' => 188.0,
                            'cls' => 0.089,
                            'fcp' => 1340.0,
                            'ttfb' => 470.0,
                        ],
                    ],
                    [
                        'buildId' => 'smile-2026.03.03',
                        'releaseVersion' => '2026.03.03',
                        'gitRef' => 'main',
                        'gitCommitSha' => '3333333333333333333333333333333333333333',
                        'daysAgo' => 1,
                        'actorName' => 'Linh Nguyen',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 3520.0,
                            'inp' => 236.0,
                            'cls' => 0.220,
                            'fcp' => 1510.0,
                            'ttfb' => 545.0,
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'lumen-shop',
                'name' => 'Lumen Shop',
                'domain' => 'lumen-shop.test',
                'ingestKey' => 'lumen-shop-ingest-key',
                'timezone' => 'America/Los_Angeles',
                'trendVariance' => 0.009,
                'pageGroups' => [
                    ['key' => 'home', 'label' => 'Home', 'path' => '/', 'weight' => 1.00],
                    ['key' => 'catalog', 'label' => 'Catalog', 'path' => '/catalog', 'weight' => 1.05],
                    ['key' => 'checkout', 'label' => 'Checkout', 'path' => '/checkout', 'weight' => 1.20],
                ],
                'deployments' => [
                    [
                        'buildId' => 'lumen-2026.03.01',
                        'releaseVersion' => '2026.03.01',
                        'gitRef' => 'main',
                        'gitCommitSha' => '4444444444444444444444444444444444444444',
                        'daysAgo' => 6,
                        'actorName' => 'Ari Chen',
                        'ciSource' => 'circleci',
                        'profile' => [
                            'lcp' => 3140.0,
                            'inp' => 252.0,
                            'cls' => 0.162,
                            'fcp' => 1640.0,
                            'ttfb' => 610.0,
                        ],
                    ],
                    [
                        'buildId' => 'lumen-2026.03.02',
                        'releaseVersion' => '2026.03.02',
                        'gitRef' => 'main',
                        'gitCommitSha' => '5555555555555555555555555555555555555555',
                        'daysAgo' => 3,
                        'actorName' => 'Ari Chen',
                        'ciSource' => 'circleci',
                        'profile' => [
                            'lcp' => 2710.0,
                            'inp' => 208.0,
                            'cls' => 0.118,
                            'fcp' => 1420.0,
                            'ttfb' => 520.0,
                        ],
                    ],
                    [
                        'buildId' => 'lumen-2026.03.03',
                        'releaseVersion' => '2026.03.03',
                        'gitRef' => 'main',
                        'gitCommitSha' => '6666666666666666666666666666666666666666',
                        'daysAgo' => 1,
                        'actorName' => 'Ari Chen',
                        'ciSource' => 'circleci',
                        'profile' => [
                            'lcp' => 2290.0,
                            'inp' => 176.0,
                            'cls' => 0.081,
                            'fcp' => 1180.0,
                            'ttfb' => 430.0,
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'atlas-guide',
                'name' => 'Atlas Guide',
                'domain' => 'atlas-guide.test',
                'ingestKey' => 'atlas-guide-ingest-key',
                'timezone' => 'Europe/London',
                'trendVariance' => 0.007,
                'pageGroups' => [
                    ['key' => 'home', 'label' => 'Home', 'path' => '/', 'weight' => 1.00],
                    ['key' => 'docs', 'label' => 'Docs', 'path' => '/docs', 'weight' => 1.03],
                    ['key' => 'search', 'label' => 'Search', 'path' => '/search', 'weight' => 1.11],
                ],
                'deployments' => [
                    [
                        'buildId' => 'atlas-2026.03.01',
                        'releaseVersion' => '2026.03.01',
                        'gitRef' => 'main',
                        'gitCommitSha' => '7777777777777777777777777777777777777777',
                        'daysAgo' => 6,
                        'actorName' => 'Morgan Ellis',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 2460.0,
                            'inp' => 168.0,
                            'cls' => 0.082,
                            'fcp' => 1270.0,
                            'ttfb' => 470.0,
                        ],
                    ],
                    [
                        'buildId' => 'atlas-2026.03.02',
                        'releaseVersion' => '2026.03.02',
                        'gitRef' => 'main',
                        'gitCommitSha' => '8888888888888888888888888888888888888888',
                        'daysAgo' => 3,
                        'actorName' => 'Morgan Ellis',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 2390.0,
                            'inp' => 214.0,
                            'cls' => 0.094,
                            'fcp' => 1230.0,
                            'ttfb' => 450.0,
                        ],
                    ],
                    [
                        'buildId' => 'atlas-2026.03.03',
                        'releaseVersion' => '2026.03.03',
                        'gitRef' => 'main',
                        'gitCommitSha' => '9999999999999999999999999999999999999999',
                        'daysAgo' => 1,
                        'actorName' => 'Morgan Ellis',
                        'ciSource' => 'github-actions',
                        'profile' => [
                            'lcp' => 2425.0,
                            'inp' => 181.0,
                            'cls' => 0.108,
                            'fcp' => 1255.0,
                            'ttfb' => 462.0,
                        ],
                    ],
                ],
            ],
        ];
    }
}
