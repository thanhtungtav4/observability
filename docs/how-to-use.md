# How To Use The App

## 1. Boot The App

Run migrations first:

```bash
php artisan migrate
```

If you want sample data for the dashboard:

```bash
php artisan performance-hub:seed-demo --fresh
```

## 2. Create An Admin

The dashboard is now protected by admin login.

Create or promote an admin user with:

```bash
php artisan performance-hub:create-admin admin@example.com password --name="Ops Admin"
```

Then open `/login` and sign in with that email and password.

## 3. Refresh Aggregates

Raw field events and synthetic runs are not shown in dashboard tables until rollups exist.

Refresh them manually with:

```bash
php artisan performance-hub:refresh-rollups
```

The scheduler also refreshes them on a cadence configured in `routes/console.php`.

## 4. Ingest Data

### Web Vitals Collector

Send batches to:

```text
POST /api/v1/collect/web-vitals
```

Headers:

```text
X-Site-Ingest-Key: <site ingest key>
Content-Type: application/json
```

### Deployments

Send deployment metadata to:

```text
POST /api/v1/deployments
Authorization: Bearer <PERFORMANCE_HUB_INTERNAL_TOKEN>
```

### Synthetic Runs

Send Lighthouse-style lab results to:

```text
POST /api/v1/synthetic-runs
Authorization: Bearer <PERFORMANCE_HUB_INTERNAL_TOKEN>
```

## 5. Read Data

Internal read APIs also require the bearer token:

- `GET /api/v1/sites`
- `GET /api/v1/dashboard/overview`
- `GET /api/v1/sites/{siteId}/metrics`
- `GET /api/v1/sites/{siteId}/deployments/compare`

The web dashboard gives the same read model through:

- `/`
- `/sites/{siteId}`
- `/sites/{siteId}/compare`

## 6. Demo Workflow

For a fresh local walkthrough:

1. `php artisan migrate`
2. `php artisan performance-hub:create-admin admin@example.com password --name="Ops Admin"`
3. `php artisan performance-hub:seed-demo --fresh`
4. Open `/login`
5. Sign in
6. Inspect overview, site detail, and compare views
