@extends('layouts.dashboard')

@section('title', $site->name.' Release Compare')

@section('content')
    <section class="masthead">
        <article class="hero-card">
            <p class="eyebrow">Release Compare</p>
            <h1 class="hero-title">{{ $site->name }}</h1>
            <p class="hero-copy">
                Compare the current release against its baseline and isolate which metric-device-page-group slices moved the most.
            </p>

            <div class="badge-row">
                <span class="badge cool">{{ ucfirst($filters['deviceClass']) }}</span>
                @if ($comparison)
                    <span class="badge">{{ $comparison['currentDeployment']->build_id }}</span>
                    <span class="badge alert">{{ $comparison['baselineDeployment']->build_id }}</span>
                @endif
            </div>
        </article>

        <form class="hero-card filter-card" method="GET" action="{{ route('dashboard.sites.compare', ['siteId' => $site->id]) }}">
            <p class="eyebrow">Compare window</p>
            <div class="filter-grid">
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="currentDeploymentId">Current deployment</label>
                    <select id="currentDeploymentId" name="currentDeploymentId">
                        @foreach ($deployments as $deployment)
                            <option value="{{ $deployment->id }}" @selected($filters['currentDeploymentId'] === $deployment->id)>
                                {{ $deployment->build_id }} · {{ $deployment->deployed_at?->format('Y-m-d H:i') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="baselineDeploymentId">Baseline deployment</label>
                    <select id="baselineDeploymentId" name="baselineDeploymentId">
                        <option value="">Auto previous release</option>
                        @foreach ($deployments as $deployment)
                            <option value="{{ $deployment->id }}" @selected($filters['baselineDeploymentId'] === $deployment->id)>
                                {{ $deployment->build_id }} · {{ $deployment->deployed_at?->format('Y-m-d H:i') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="deviceClass">Device class</label>
                    <select id="deviceClass" name="deviceClass">
                        @foreach (['mobile', 'desktop', 'tablet', 'unknown'] as $deviceClass)
                            <option value="{{ $deviceClass }}" @selected($filters['deviceClass'] === $deviceClass)>{{ ucfirst($deviceClass) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 18px;">
                <button class="button" type="submit">Run compare</button>
                <a class="button secondary" href="{{ route('dashboard.sites.compare', ['siteId' => $site->id]) }}">Reset</a>
            </div>
        </form>
    </section>

    @if ($comparisonError)
        <div class="callout">
            <strong>Comparison could not be built.</strong>
            <p class="muted" style="margin: 10px 0 0;">{{ $comparisonError }}</p>
        </div>
    @endif

    @if ($comparison === null)
        <div class="empty-state">
            <h3 style="margin: 0 0 8px;">Not enough deployments to compare</h3>
            <p class="muted" style="margin: 0;">Register at least two deployments for this site, refresh rollups, and this page will calculate release deltas automatically.</p>
        </div>
    @else
        <section class="section">
            <div class="stats-grid">
                <article class="stat-card">
                    <span class="muted">Compared slices</span>
                    <strong>{{ count($comparison['metrics']) }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Regressions</span>
                    <strong>{{ $summary['regressions'] }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Improvements</span>
                    <strong>{{ $summary['improvements'] }}</strong>
                </article>

                <article class="stat-card">
                    <span class="muted">Current build</span>
                    <strong style="font-size: 20px;">{{ $comparison['currentDeployment']->build_id }}</strong>
                </article>
            </div>
        </section>

        <section class="section two-column">
            <article class="site-card">
                <p class="eyebrow">Largest regression</p>
                @if ($summary['largestRegression'])
                    <h3 style="margin: 0; font-size: 28px;">{{ strtoupper($summary['largestRegression']['metricName']) }}</h3>
                    <p class="muted" style="margin: 10px 0 0;">
                        {{ $summary['largestRegression']['pageGroupKey'] }} · {{ ucfirst($summary['largestRegression']['deviceClass']) }}
                    </p>
                    <strong class="delta-up" style="font-size: 34px;">+{{ number_format($summary['largestRegression']['delta'], strtolower($summary['largestRegression']['metricName']) === 'cls' ? 3 : 0) }}</strong>
                @else
                    <h3 style="margin: 0; font-size: 28px;">No regressions yet</h3>
                    <p class="muted" style="margin: 10px 0 0;">The current release is not worse than baseline for any compared slice.</p>
                @endif
            </article>

            <article class="site-card">
                <p class="eyebrow">Best improvement</p>
                @if ($summary['largestImprovement'])
                    <h3 style="margin: 0; font-size: 28px;">{{ strtoupper($summary['largestImprovement']['metricName']) }}</h3>
                    <p class="muted" style="margin: 10px 0 0;">
                        {{ $summary['largestImprovement']['pageGroupKey'] }} · {{ ucfirst($summary['largestImprovement']['deviceClass']) }}
                    </p>
                    <strong class="{{ $summary['largestImprovement']['delta'] < 0 ? 'delta-down' : 'delta-flat' }}" style="font-size: 34px;">
                        {{ $summary['largestImprovement']['delta'] > 0 ? '+' : '' }}{{ number_format($summary['largestImprovement']['delta'], strtolower($summary['largestImprovement']['metricName']) === 'cls' ? 3 : 0) }}
                    </strong>
                @else
                    <h3 style="margin: 0; font-size: 28px;">No improvements yet</h3>
                    <p class="muted" style="margin: 10px 0 0;">Every compared slice is flat or regressed against the selected baseline.</p>
                @endif
            </article>
        </section>

        <section class="section">
            <div class="section-heading">
                <div>
                    <h2>Slice-by-slice delta table</h2>
                    <p>Positive delta means the current release is worse than baseline for that exact slice.</p>
                </div>
            </div>

            <article class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Page group</th>
                            <th>Device</th>
                            <th>Baseline p75</th>
                            <th>Current p75</th>
                            <th>Delta</th>
                            <th>Samples</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (collect($comparison['metrics'])->sortByDesc('delta') as $metric)
                            <tr>
                                <td>{{ strtoupper($metric['metricName']) }}</td>
                                <td>{{ $metric['pageGroupKey'] }}</td>
                                <td>{{ ucfirst($metric['deviceClass']) }}</td>
                                <td>{{ number_format($metric['baselineP75'], strtolower($metric['metricName']) === 'cls' ? 3 : 0) }}</td>
                                <td>{{ number_format($metric['currentP75'], strtolower($metric['metricName']) === 'cls' ? 3 : 0) }}</td>
                                <td class="{{ $metric['delta'] > 0 ? 'delta-up' : ($metric['delta'] < 0 ? 'delta-down' : 'delta-flat') }}">
                                    {{ $metric['delta'] > 0 ? '+' : '' }}{{ number_format($metric['delta'], strtolower($metric['metricName']) === 'cls' ? 3 : 0) }}
                                </td>
                                <td>{{ number_format($metric['baselineSampleCount']) }} → {{ number_format($metric['currentSampleCount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>
        </section>
    @endif
@endsection
