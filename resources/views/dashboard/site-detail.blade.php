@extends('layouts.dashboard')

@section('title', $site->name.' Overview')

@section('content')
    <section class="masthead">
        <article class="hero-card">
            <p class="eyebrow">Site Detail</p>
            <h1 class="hero-title">{{ $site->name }}</h1>
            <p class="hero-copy">
                Track the selected segment over time, spot weak route groups, and jump from a noisy day straight into release
                comparison with the recent deployment rail.
            </p>

            <div class="badge-row">
                <span class="badge cool">{{ $site->slug }}</span>
                <span class="badge">{{ ucfirst($filters['environment']) }}</span>
                <span class="badge">{{ ucfirst($filters['deviceClass']) }}</span>
                @if ($filters['metric'])
                    <span class="badge alert">{{ strtoupper($filters['metric']) }}</span>
                @endif
            </div>
        </article>

        <form class="hero-card filter-card" method="GET" action="{{ route('dashboard.sites.show', ['siteId' => $site->id]) }}">
            <p class="eyebrow">Focus</p>
            <div class="filter-grid">
                <div class="field">
                    <label for="from">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
                </div>

                <div class="field">
                    <label for="to">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
                </div>

                <div class="field">
                    <label for="environment">Environment</label>
                    <select id="environment" name="environment">
                        @foreach (config('performance-hub.environments') as $environment)
                            <option value="{{ $environment }}" @selected($filters['environment'] === $environment)>{{ ucfirst($environment) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="metric">Metric</label>
                    <select id="metric" name="metric">
                        <option value="">All metrics</option>
                        @foreach (config('performance-hub.metric_names') as $metric)
                            <option value="{{ $metric }}" @selected($filters['metric'] === $metric)>{{ strtoupper($metric) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="deviceClass">Device</label>
                    <select id="deviceClass" name="deviceClass">
                        @foreach (config('performance-hub.device_classes') as $deviceClass)
                            <option value="{{ $deviceClass }}" @selected($filters['deviceClass'] === $deviceClass)>{{ ucfirst($deviceClass) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="pageGroupKey">Page group</label>
                    <input id="pageGroupKey" name="pageGroupKey" type="text" value="{{ $filters['pageGroupKey'] }}" placeholder="home, pricing, checkout">
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 18px;">
                <button class="button" type="submit">Apply focus</button>
                <a class="button secondary" href="{{ route('dashboard.sites.show', ['siteId' => $site->id]) }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="section">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="muted">Metric slices</span>
                <strong>{{ count($siteMetrics) }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Recent deployments</span>
                <strong>{{ $deployments->count() }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Synthetic evidence</span>
                <strong>{{ $syntheticRuns->count() }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Latest lab score</span>
                <strong>{{ $latestSyntheticRun ? number_format((float) $latestSyntheticRun->performance_score, 1) : '—' }}</strong>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Likely causes</h2>
                <p>Raw evidence stitched to the selected filter window so the dashboard can point to what is probably driving the regression.</p>
            </div>
        </div>

        @if (($causeSignals['summary']['eventCount'] ?? 0) === 0)
            <div class="empty-state">
                <h3 style="margin: 0 0 8px;">No raw evidence for this focus</h3>
                <p class="muted" style="margin: 0;">Send richer collector payloads with resources, long tasks, and errors to start seeing likely-cause breakdowns.</p>
            </div>
        @else
            <div class="stats-grid">
                <article class="stat-card">
                    <span class="muted">Field events in focus</span>
                    <strong>{{ number_format($causeSignals['summary']['eventCount']) }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Linked resources</span>
                    <strong>{{ number_format($causeSignals['summary']['resourceCount']) }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Long tasks</span>
                    <strong>{{ number_format($causeSignals['summary']['longTaskCount']) }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">JS errors</span>
                    <strong>{{ number_format($causeSignals['summary']['errorCount']) }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Lab hints</span>
                    <strong>{{ number_format($causeSignals['summary']['syntheticOpportunityCount']) }}</strong>
                </article>
            </div>

            @if (count($causeSignals['phaseBreakdown']) > 0 || count($causeSignals['layoutShiftHotspots']) > 0 || count($causeSignals['labOpportunities']) > 0)
                <div class="cards-grid" style="margin-top: 24px;">
                    @foreach ($causeSignals['phaseBreakdown'] as $panel)
                        <article class="site-card">
                            <p class="eyebrow">{{ strtoupper($panel['metricName']) }}</p>
                            <h3 style="margin: 0 0 12px;">{{ $panel['title'] }}</h3>
                            <div style="display: grid; gap: 12px;">
                                @foreach ($panel['rows'] as $row)
                                    <div>
                                        <div style="display: flex; justify-content: space-between; gap: 12px;">
                                            <strong>{{ $row['phase'] }}</strong>
                                            <span class="muted">{{ number_format($row['share'], 1) }}%</span>
                                        </div>
                                        <div class="muted" style="margin-top: 4px;">
                                            {{ number_format($row['count']) }} samples · avg contribution {{ number_format($row['avgContribution'], 1) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach

                    @if (count($causeSignals['layoutShiftHotspots']) > 0)
                        <article class="site-card">
                            <p class="eyebrow">CLS</p>
                            <h3 style="margin: 0 0 12px;">Top shift targets</h3>
                            <div style="display: grid; gap: 12px;">
                                @foreach ($causeSignals['layoutShiftHotspots'] as $row)
                                    <div>
                                        <div style="display: flex; justify-content: space-between; gap: 12px;">
                                            <strong>{{ $row['target'] }}</strong>
                                            <span class="muted">{{ number_format($row['share'], 1) }}%</span>
                                        </div>
                                        <div class="muted" style="margin-top: 4px;">
                                            {{ number_format($row['count']) }} samples · avg CLS {{ number_format($row['avgScore'], 3) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endif

                    @if (count($causeSignals['labOpportunities']) > 0)
                        <article class="site-card">
                            <p class="eyebrow">Synthetic</p>
                            <h3 style="margin: 0 0 12px;">Recurring lab hints</h3>
                            <div style="display: grid; gap: 12px;">
                                @foreach ($causeSignals['labOpportunities'] as $opportunity)
                                    <div style="display: flex; justify-content: space-between; gap: 12px;">
                                        <strong>{{ $opportunity['title'] }}</strong>
                                        <span class="badge">{{ number_format($opportunity['count']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endif
                </div>
            @endif

            <div class="two-column" style="margin-top: 24px;">
                <article class="table-card">
                    <div class="section-heading" style="margin-bottom: 10px;">
                        <div>
                            <h2>Hot resources</h2>
                            <p>Resources that show up repeatedly in the selected window.</p>
                        </div>
                    </div>

                    @if (count($causeSignals['resourceHotspots']) === 0)
                        <div class="empty-state">
                            <p class="muted" style="margin: 0;">No resource timing evidence has been attached to these events yet.</p>
                        </div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>Resource</th>
                                    <th>Type</th>
                                    <th>Avg ms</th>
                                    <th>Blocking</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($causeSignals['resourceHotspots'] as $row)
                                    <tr>
                                        <td>
                                            <strong style="font-size: 15px; line-height: 1.2;">{{ $row['label'] }}</strong>
                                            <div class="muted">{{ number_format($row['count']) }} hits · {{ number_format($row['avgTransferSize']) }} bytes</div>
                                        </td>
                                        <td>{{ strtoupper($row['resourceType']) }}</td>
                                        <td>{{ number_format($row['avgDurationMs'], 1) }}</td>
                                        <td>{{ number_format($row['renderBlockingCount']) }} / {{ number_format($row['lcpCandidateCount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </article>

                <article class="table-card">
                    <div class="section-heading" style="margin-bottom: 10px;">
                        <div>
                            <h2>Interaction hot spots</h2>
                            <p>Main-thread work correlated with slow interaction windows.</p>
                        </div>
                    </div>

                    @if (count($causeSignals['interactionHotspots']) === 0)
                        <div class="empty-state">
                            <p class="muted" style="margin: 0;">No long task evidence has been attached to these events yet.</p>
                        </div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>Script</th>
                                    <th>Target</th>
                                    <th>Avg task</th>
                                    <th>Avg blocking</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($causeSignals['interactionHotspots'] as $row)
                                    <tr>
                                        <td>
                                            <strong style="font-size: 15px; line-height: 1.2;">{{ $row['scriptHost'] }}</strong>
                                            <div class="muted">{{ ucfirst($row['invokerType']) }} · {{ number_format($row['count']) }} samples</div>
                                        </td>
                                        <td>{{ $row['containerSelector'] }}</td>
                                        <td>{{ number_format($row['avgDurationMs'], 1) }}</td>
                                        <td>{{ number_format($row['avgBlockingDurationMs'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </article>
            </div>

            <article class="table-card" style="margin-top: 24px;">
                <div class="section-heading" style="margin-bottom: 10px;">
                    <div>
                        <h2>Recent JavaScript faults</h2>
                        <p>Errors linked to the same filtered event set so you can correlate slow sessions with breakage.</p>
                    </div>
                </div>

                @if (count($causeSignals['errorHotspots']) === 0)
                    <div class="empty-state">
                        <p class="muted" style="margin: 0;">No JavaScript error evidence has been attached to these events yet.</p>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Error</th>
                                <th>Source</th>
                                <th>Count</th>
                                <th>Handled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($causeSignals['errorHotspots'] as $row)
                                <tr>
                                    <td>
                                        <strong style="font-size: 15px; line-height: 1.2;">{{ $row['name'] }}</strong>
                                        <div class="muted">{{ $row['message'] }}</div>
                                    </td>
                                    <td>{{ $row['sourceHost'] }}</td>
                                    <td>{{ number_format($row['count']) }}</td>
                                    <td>{{ number_format($row['handledRate'], 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>
        @endif
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Metric rails</h2>
                <p>Each rail keeps one metric in focus so you can compare shape, not just the last number.</p>
            </div>
        </div>

        @if (count($metricPanels) === 0)
            <div class="empty-state">
                <h3 style="margin: 0 0 8px;">No rollup slices for this filter</h3>
                <p class="muted" style="margin: 0;">Try widening the date range, removing the page-group filter, or refreshing the rollups.</p>
            </div>
        @else
            <div class="metrics-grid">
                @foreach ($metricPanels as $panel)
                    @php
                        $maxPoint = max($panel['points']) ?: 1;
                        $deltaClass = $panel['delta'] === null ? 'delta-flat' : ($panel['delta'] > 0 ? 'delta-up' : ($panel['delta'] < 0 ? 'delta-down' : 'delta-flat'));
                    @endphp
                    <article class="metric-card">
                        <div style="display: flex; justify-content: space-between; gap: 16px;">
                            <div>
                                <p class="eyebrow" style="margin-bottom: 8px;">{{ $panel['metricName'] }}</p>
                                <strong>{{ number_format($panel['latestValue'], $panel['metricKey'] === 'cls' ? 3 : 0) }}</strong>
                            </div>

                            <div style="text-align: right;">
                                <div class="mono muted">{{ $panel['latestDate'] }}</div>
                                <div class="{{ $deltaClass }}" style="margin-top: 12px; font-weight: 700;">
                                    @if ($panel['delta'] === null)
                                        New slice
                                    @else
                                        {{ $panel['delta'] > 0 ? '+' : '' }}{{ number_format($panel['delta'], $panel['metricKey'] === 'cls' ? 3 : 0) }}
                                    @endif
                                </div>
                                <div class="muted" style="margin-top: 6px;">{{ number_format($panel['sampleCount']) }} samples</div>
                            </div>
                        </div>

                        <div class="metric-rail">
                            @foreach ($panel['points'] as $point)
                                <span style="height: {{ max(($point / $maxPoint) * 100, 14) }}%;"></span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="section two-column">
        <article class="table-card">
            <div class="section-heading" style="margin-bottom: 10px;">
                <div>
                    <h2>Page-group pressure</h2>
                    <p>The latest slice for each page group in the current filter window.</p>
                </div>
            </div>

            @if (count($pageGroupPanels) === 0)
                <div class="empty-state">
                    <p class="muted" style="margin: 0;">No page-group breakdown is available for this filter.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Page group</th>
                            <th>Metric</th>
                            <th>p75</th>
                            <th>Poor</th>
                            <th>Samples</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pageGroupPanels as $pageGroup)
                            <tr>
                                <td>{{ $pageGroup['pageGroupKey'] }}</td>
                                <td>{{ $pageGroup['metricName'] }}</td>
                                <td>{{ number_format($pageGroup['p75Value'], strtolower($pageGroup['metricName']) === 'cls' ? 3 : 0) }}</td>
                                <td>{{ number_format($pageGroup['poorCount']) }}</td>
                                <td>{{ number_format($pageGroup['sampleCount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>

        <article class="table-card">
            <div class="section-heading" style="margin-bottom: 10px;">
                <div>
                    <h2>Recent deployments</h2>
                    <p>Jump straight into compare mode from the latest release rail.</p>
                </div>
            </div>

            @if ($deployments->isEmpty())
                <div class="empty-state">
                    <p class="muted" style="margin: 0;">No deployments have been registered for this site yet.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Build</th>
                            <th>Released</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deployments as $deployment)
                            <tr>
                                <td>
                                    <strong style="font-size: 16px; line-height: 1.2;">{{ $deployment->build_id }}</strong>
                                    <div class="muted">{{ $deployment->release_version ?? 'Unversioned' }}</div>
                                </td>
                                <td>{{ $deployment->deployed_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a class="button secondary" href="{{ route('dashboard.sites.compare', ['siteId' => $site->id, 'currentDeploymentId' => $deployment->id]) }}">
                                        Compare
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </article>
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Latest synthetic evidence</h2>
                <p>Nightly lab runs stay attached so the team can compare field regression signals with repeatable Lighthouse checks.</p>
            </div>
        </div>

        @if ($syntheticRuns->isEmpty())
            <div class="empty-state">
                <h3 style="margin: 0 0 8px;">No synthetic runs yet</h3>
                <p class="muted" style="margin: 0;">Send Lighthouse runs into the ingest API and they will appear here alongside field trends.</p>
            </div>
        @else
            <div class="cards-grid">
                @foreach ($syntheticRuns as $run)
                    <article class="site-card">
                        <p class="eyebrow">{{ $run->page_group_key }} · {{ ucfirst($run->device_preset) }}</p>
                        <h3 style="margin: 0; font-size: 24px;">{{ number_format((float) $run->performance_score, 1) }}</h3>
                        <p class="muted" style="margin: 10px 0 0;">{{ $run->page_path }} · {{ $run->occurred_at?->format('Y-m-d H:i') }}</p>
                        <div class="badge-row">
                            <span class="badge">LCP {{ $run->lcp_ms ?? '—' }}</span>
                            <span class="badge cool">INP {{ $run->inp_ms ?? '—' }}</span>
                            <span class="badge alert">CLS {{ $run->cls_score ?? '—' }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
