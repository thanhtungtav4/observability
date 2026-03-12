@extends('layouts.dashboard')

@section('title', 'Portfolio Overview')

@section('content')
    <section class="masthead">
        <article class="hero-card">
            <p class="eyebrow">Portfolio Pulse</p>
            <h1 class="hero-title">See regressions before the release notes do.</h1>
            <p class="hero-copy">
                Field data, lab evidence, and deployment context stay in one room here. This page is tuned for fast morning triage:
                which site is drifting, which metric is flashing, and whether the pattern looks new or sticky.
            </p>
        </article>

        <form class="hero-card filter-card" method="GET" action="{{ route('dashboard.overview') }}">
            <p class="eyebrow">Window</p>
            <div class="filter-grid">
                <div class="field">
                    <label for="from">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
                </div>

                <div class="field">
                    <label for="to">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="environment">Environment</label>
                    <select id="environment" name="environment">
                        @foreach (config('performance-hub.environments') as $environment)
                            <option value="{{ $environment }}" @selected($filters['environment'] === $environment)>
                                {{ ucfirst($environment) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 18px;">
                <button class="button" type="submit">Refresh view</button>
                <a class="button secondary" href="{{ route('dashboard.overview') }}">Reset</a>
            </div>
        </form>
    </section>

    @if ($needsDemoSeed)
        <div class="callout">
            <strong>Need a demo workspace?</strong>
            <p class="muted" style="margin: 10px 0 0;">
                Run <span class="mono">php artisan performance-hub:seed-demo</span> to populate three sample sites with release history,
                field vitals, synthetic runs, and ready-to-read rollups.
            </p>
        </div>
    @endif

    @if ($needsRefresh)
        <div class="callout">
            <strong>Raw events exist, but rollups are stale.</strong>
            <p class="muted" style="margin: 10px 0 0;">
                Run <span class="mono">php artisan performance-hub:refresh-rollups</span> or wait for the scheduler to hydrate the
                dashboard read models.
            </p>
        </div>
    @endif

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Control tower</h2>
                <p>Quick counters for the currently selected window.</p>
            </div>
        </div>

        <div class="stats-grid">
            <article class="stat-card">
                <span class="muted">Managed sites</span>
                <strong>{{ $stats['siteCount'] }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Sites with failing metrics</span>
                <strong>{{ $stats['alertCount'] }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Field events in window</span>
                <strong>{{ number_format($stats['eventCount']) }}</strong>
            </article>

            <article class="stat-card">
                <span class="muted">Synthetic runs in window</span>
                <strong>{{ number_format($stats['syntheticCount']) }}</strong>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Site health</h2>
                <p>Each card shows the current failing metrics and poor-event pressure for the latest day inside your selected window.</p>
            </div>
        </div>

        @if (count($healthCards) === 0)
            <div class="empty-state">
                <h3 style="margin: 0 0 8px;">No aggregated health yet</h3>
                <p class="muted" style="margin: 0;">Ingest field data, refresh rollups, and this area will light up with site-level health cards.</p>
            </div>
        @else
            <div class="cards-grid">
                @foreach ($healthCards as $health)
                    <article class="site-card">
                        <div style="display: flex; justify-content: space-between; gap: 14px; align-items: flex-start;">
                            <div>
                                <p class="eyebrow" style="margin-bottom: 8px;">{{ $health['siteSlug'] }}</p>
                                <h3 style="margin: 0; font-size: 24px;">{{ $health['siteName'] }}</h3>
                            </div>

                            <a class="button secondary" href="{{ route('dashboard.sites.show', ['siteId' => $health['siteId']]) }}">Inspect</a>
                        </div>

                        <strong>{{ number_format($health['poorEventShare'] * 100, 1) }}%</strong>
                        <p class="muted" style="margin: 8px 0 0;">Poor-event share across the latest reporting day.</p>

                        <div class="badge-row">
                            @forelse ($health['failingMetrics'] as $metric)
                                <span class="badge alert">{{ strtoupper($metric) }}</span>
                            @empty
                                <span class="badge">Stable window</span>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <h2>Trend matrix</h2>
                <p>Rollup p75 slices grouped by metric and device. Use this when one dashboard row is not enough and you need pattern shape.</p>
            </div>
        </div>

        @if (count($metricCards) === 0)
            <div class="empty-state">
                <h3 style="margin: 0 0 8px;">No trend slices yet</h3>
                <p class="muted" style="margin: 0;">Once daily rollups are populated, this section will draw the short-run trend rails for each metric.</p>
            </div>
        @else
            <div class="metrics-grid">
                @foreach ($metricCards as $metric)
                    @php
                        $maxPoint = max($metric['points']) ?: 1;
                        $deltaClass = $metric['delta'] === null ? 'delta-flat' : ($metric['delta'] > 0 ? 'delta-up' : ($metric['delta'] < 0 ? 'delta-down' : 'delta-flat'));
                    @endphp
                    <article class="metric-card">
                        <div style="display: flex; justify-content: space-between; gap: 16px;">
                            <div>
                                <p class="eyebrow" style="margin-bottom: 8px;">{{ $metric['metricName'] }} · {{ ucfirst($metric['deviceClass']) }}</p>
                                <strong>{{ number_format($metric['latestValue'], $metric['metricKey'] === 'cls' ? 3 : 0) }}</strong>
                            </div>

                            <div style="text-align: right;">
                                <div class="mono muted">{{ $metric['latestDate'] }}</div>
                                <div class="{{ $deltaClass }}" style="margin-top: 12px; font-weight: 700;">
                                    @if ($metric['delta'] === null)
                                        Flat start
                                    @else
                                        {{ $metric['delta'] > 0 ? '+' : '' }}{{ number_format($metric['delta'], $metric['metricKey'] === 'cls' ? 3 : 0) }}
                                    @endif
                                </div>
                                <div class="muted" style="margin-top: 6px;">{{ number_format($metric['sampleCount']) }} samples</div>
                            </div>
                        </div>

                        <div class="metric-rail">
                            @foreach ($metric['points'] as $point)
                                <span style="height: {{ max(($point / $maxPoint) * 100, 14) }}%;"></span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
