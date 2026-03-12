# Performance Observability Hub

Laravel 12 application for collecting Web Vitals, attaching them to deployment metadata, storing synthetic Lighthouse runs, and triaging regressions from a web dashboard.

## What You Get

- field ingestion endpoint for Web Vitals batches
- internal deployment and synthetic-run ingestion endpoints
- rollup command for daily and per-deployment aggregates
- admin-only dashboard for overview, site detail, and release compare
- demo data command so the UI is usable immediately on local setup

## Local Setup

1. Install dependencies.
```bash
composer install
npm install
```

2. Create the environment file and app key.
```bash
cp .env.example .env
php artisan key:generate
```

3. Make sure the internal API token is set in `.env`.
```dotenv
PERFORMANCE_HUB_INTERNAL_TOKEN=change-me
```

4. Run the database migrations.
```bash
php artisan migrate
```

5. Create the first admin account for the dashboard.
```bash
php artisan performance-hub:create-admin admin@example.com password --name="Ops Admin"
```

6. Optional: load demo data and precompute rollups.
```bash
php artisan performance-hub:seed-demo --fresh
```

7. Open the dashboard login page on your Herd domain.
```text
/login
```

For this workspace the local URL is typically `https://monitor.test/login`.

## Daily Commands

- Refresh rollups after new raw events arrive:
```bash
php artisan performance-hub:refresh-rollups
```

- Rebuild the local demo workspace:
```bash
php artisan performance-hub:seed-demo --fresh
```

- Run the test suite:
```bash
php artisan test --compact
```

- Format changed PHP files:
```bash
vendor/bin/pint --dirty --format agent
```

## Web Access

- `GET /login`
  Admin login screen.
- `POST /login`
  Starts an admin session.
- `POST /logout`
  Ends the current admin session.
- `GET /`
  Portfolio overview, requires an authenticated admin.
- `GET /sites/{siteId}`
  Site detail view, requires an authenticated admin.
- `GET /sites/{siteId}/compare`
  Release compare view, requires an authenticated admin.

## API Access

Two auth modes are used:

- Collector auth:
  `POST /api/v1/collect/web-vitals` requires `X-Site-Ingest-Key`.
- Internal auth:
  deployment, synthetic, and read APIs require `Authorization: Bearer <PERFORMANCE_HUB_INTERNAL_TOKEN>`.

Main endpoints:

- `POST /api/v1/collect/web-vitals`
- `POST /api/v1/deployments`
- `POST /api/v1/synthetic-runs`
- `GET /api/v1/sites`
- `GET /api/v1/dashboard/overview`
- `GET /api/v1/sites/{siteId}/metrics`
- `GET /api/v1/sites/{siteId}/causes`
- `GET /api/v1/sites/{siteId}/deployments/compare`

For the collector, prefer `fetch(..., { keepalive: true })` for real browser traffic. The ingest API uses `X-Site-Ingest-Key`, which means plain `navigator.sendBeacon()` is only suitable if you proxy or otherwise inject auth outside the browser call.

## Documentation

- Product and API spec: [docs/README.md](/Users/macbook/Herd/monitor/docs/README.md)
- Usage guide: [docs/how-to-use.md](/Users/macbook/Herd/monitor/docs/how-to-use.md)
