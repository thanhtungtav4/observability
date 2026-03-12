<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Performance Hub')</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|ibm-plex-mono:400,500" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <style>
            :root {
                --bg: #f3efe7;
                --bg-accent: #ffd2ad;
                --ink: #1a1b1f;
                --muted: #646468;
                --panel: rgba(255, 250, 244, 0.78);
                --line: rgba(36, 28, 21, 0.12);
                --orange: #ff6f2c;
                --teal: #0b8f84;
                --red: #cb4b3c;
                --blue: #1743d1;
                --shadow: 0 20px 40px rgba(31, 24, 18, 0.08);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Space Grotesk", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(255, 167, 77, 0.35), transparent 32%),
                    radial-gradient(circle at top right, rgba(15, 143, 132, 0.14), transparent 28%),
                    linear-gradient(180deg, #faf7f2 0%, var(--bg) 44%, #efe7da 100%);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .dashboard-shell {
                display: grid;
                grid-template-columns: 290px minmax(0, 1fr);
                min-height: 100vh;
            }

            .dashboard-sidebar {
                position: sticky;
                top: 0;
                align-self: start;
                min-height: 100vh;
                padding: 28px 22px;
                background: rgba(255, 247, 240, 0.72);
                backdrop-filter: blur(22px);
                border-right: 1px solid rgba(26, 27, 31, 0.08);
            }

            .brand-mark {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                border-radius: 999px;
                background: rgba(255, 111, 44, 0.12);
                color: #7f3200;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .sidebar-note,
            .sidebar-section-label,
            .mono {
                font-family: "IBM Plex Mono", monospace;
            }

            .sidebar-note {
                margin: 16px 0 28px;
                color: var(--muted);
                font-size: 12px;
                line-height: 1.6;
            }

            .sidebar-section-label {
                color: rgba(26, 27, 31, 0.48);
                font-size: 11px;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                margin-bottom: 10px;
            }

            .nav-stack {
                display: grid;
                gap: 10px;
            }

            .nav-link {
                display: block;
                padding: 14px 16px;
                border-radius: 18px;
                border: 1px solid transparent;
                background: rgba(255, 255, 255, 0.42);
                transition: transform 140ms ease, border-color 140ms ease, background 140ms ease;
            }

            .nav-link:hover {
                transform: translateX(2px);
                border-color: rgba(255, 111, 44, 0.24);
            }

            .nav-link.is-active {
                background: linear-gradient(135deg, rgba(255, 111, 44, 0.16), rgba(23, 67, 209, 0.08));
                border-color: rgba(255, 111, 44, 0.28);
            }

            .nav-link strong {
                display: block;
                font-size: 15px;
            }

            .nav-link span {
                display: block;
                margin-top: 6px;
                font-size: 12px;
                color: var(--muted);
            }

            .dashboard-main {
                padding: 32px;
            }

            .masthead {
                display: grid;
                gap: 24px;
                grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.9fr);
                margin-bottom: 30px;
            }

            .hero-card,
            .panel-card {
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 28px;
                box-shadow: var(--shadow);
            }

            .hero-card {
                padding: 28px;
                position: relative;
                overflow: hidden;
            }

            .hero-card::after {
                content: "";
                position: absolute;
                inset: auto -60px -90px auto;
                width: 220px;
                height: 220px;
                background: radial-gradient(circle, rgba(255, 111, 44, 0.18), transparent 68%);
                pointer-events: none;
            }

            .eyebrow {
                margin: 0 0 12px;
                color: #7f3200;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .hero-title {
                margin: 0;
                font-size: clamp(34px, 5vw, 64px);
                line-height: 0.95;
                max-width: 11ch;
            }

            .hero-copy {
                margin: 14px 0 0;
                max-width: 60ch;
                color: var(--muted);
                line-height: 1.7;
            }

            .filter-card {
                padding: 24px;
            }

            .filter-grid {
                display: grid;
                gap: 14px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .field {
                display: grid;
                gap: 8px;
            }

            .field label {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: rgba(26, 27, 31, 0.54);
            }

            .field input,
            .field select {
                width: 100%;
                padding: 12px 14px;
                border-radius: 14px;
                border: 1px solid rgba(26, 27, 31, 0.12);
                background: rgba(255, 255, 255, 0.78);
                font: inherit;
                color: inherit;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 48px;
                padding: 0 18px;
                border-radius: 14px;
                border: 0;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
                background: linear-gradient(135deg, var(--orange), #ff9c52);
                color: white;
            }

            .button.secondary {
                background: rgba(255, 255, 255, 0.72);
                color: var(--ink);
                border: 1px solid rgba(26, 27, 31, 0.1);
            }

            .section {
                margin-top: 28px;
            }

            .section-heading {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 18px;
            }

            .section-heading h2 {
                margin: 0;
                font-size: 26px;
            }

            .section-heading p {
                margin: 0;
                color: var(--muted);
                max-width: 56ch;
                line-height: 1.6;
            }

            .stats-grid,
            .cards-grid,
            .metrics-grid {
                display: grid;
                gap: 18px;
            }

            .stats-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .cards-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .metrics-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stat-card,
            .site-card,
            .metric-card,
            .table-card,
            .callout {
                padding: 22px;
                border-radius: 24px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.72);
                box-shadow: var(--shadow);
            }

            .stat-card strong,
            .site-card strong,
            .metric-card strong {
                display: block;
                font-size: 32px;
                line-height: 1;
                margin-top: 10px;
            }

            .muted {
                color: var(--muted);
            }

            .badge-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 14px;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 10px;
                border-radius: 999px;
                background: rgba(11, 143, 132, 0.11);
                color: #055951;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .badge.alert {
                background: rgba(203, 75, 60, 0.12);
                color: #8a2418;
            }

            .badge.cool {
                background: rgba(23, 67, 209, 0.1);
                color: #1637a0;
            }

            .metric-rail {
                display: flex;
                align-items: flex-end;
                gap: 7px;
                min-height: 78px;
                margin-top: 18px;
            }

            .metric-rail span {
                flex: 1;
                min-width: 10px;
                border-radius: 999px;
                background: linear-gradient(180deg, rgba(23, 67, 209, 0.95), rgba(11, 143, 132, 0.55));
            }

            .table-card table {
                width: 100%;
                border-collapse: collapse;
            }

            .table-card th,
            .table-card td {
                padding: 14px 12px;
                border-bottom: 1px solid rgba(26, 27, 31, 0.08);
                text-align: left;
                vertical-align: top;
            }

            .table-card th {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: rgba(26, 27, 31, 0.46);
            }

            .table-card tr:last-child td {
                border-bottom: 0;
            }

            .empty-state {
                padding: 44px;
                border-radius: 28px;
                text-align: center;
                border: 1px dashed rgba(26, 27, 31, 0.16);
                background: rgba(255, 255, 255, 0.54);
            }

            .delta-up {
                color: var(--red);
            }

            .delta-down {
                color: var(--teal);
            }

            .delta-flat {
                color: var(--muted);
            }

            .two-column {
                display: grid;
                gap: 18px;
                grid-template-columns: 1.2fr 0.8fr;
            }

            @media (max-width: 1180px) {
                .dashboard-shell {
                    grid-template-columns: 1fr;
                }

                .dashboard-sidebar {
                    position: static;
                    min-height: auto;
                }

                .masthead,
                .stats-grid,
                .cards-grid,
                .metrics-grid,
                .two-column {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="dashboard-shell">
            <aside class="dashboard-sidebar">
                <a class="brand-mark" href="{{ route('dashboard.overview') }}">Pulseboard</a>
                <p class="sidebar-note">
                    Multi-site observability for release-aware Web Vitals, nightly lab signals, and regression triage.
                </p>

                <div class="sidebar-section-label">Views</div>
                <div class="nav-stack" style="margin-bottom: 22px;">
                    <a class="nav-link {{ request()->routeIs('dashboard.overview') ? 'is-active' : '' }}" href="{{ route('dashboard.overview') }}">
                        <strong>Portfolio Overview</strong>
                        <span>Cross-site health, alert pressure, and trend matrix.</span>
                    </a>
                </div>

                <div class="sidebar-section-label">Sites</div>
                <div class="nav-stack">
                    @foreach ($navSites as $navSite)
                        <a class="nav-link {{ $activeSiteId === $navSite->id ? 'is-active' : '' }}" href="{{ route('dashboard.sites.show', ['siteId' => $navSite->id]) }}">
                            <strong>{{ $navSite->name }}</strong>
                            <span>{{ $navSite->slug }}</span>
                        </a>
                    @endforeach
                </div>
            </aside>

            <main class="dashboard-main">
                @yield('content')
            </main>
        </div>
    </body>
</html>
