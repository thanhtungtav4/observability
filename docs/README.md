# Performance Observability Hub

This repository defines a practical MVP for a multi-site web performance observability hub.

The product goal is simple:

- collect real user Web Vitals from many sites
- join field data with deployment metadata
- run nightly synthetic checks for key URLs
- show which site, page group, device segment, or release is regressing

This is intentionally not a general CRM.
It is a focused performance hub that helps teams answer:

- which site is failing LCP, INP, or CLS right now
- which page group is pulling p75 down
- which deploy introduced the regression
- whether lab runs confirm the same bottleneck

## MVP Scope

The MVP is built around 3 layers:

1. Collector
2. Storage and aggregation
3. Dashboard and alerting

Core artifacts in this repository:

- [docs/mvp-spec.md](/Volumes/Manager Data/Tool/performance monitor/docs/mvp-spec.md)
- [docs/collector-payload.md](/Volumes/Manager Data/Tool/performance monitor/docs/collector-payload.md)
- [docs/openapi.yaml](/Volumes/Manager Data/Tool/performance monitor/docs/openapi.yaml)
- [database/schema.sql](/Volumes/Manager Data/Tool/performance monitor/database/schema.sql)

## Suggested Stack

- Admin app: Laravel 12 + Filament
- Primary database: PostgreSQL
- Collector library: `web-vitals` attribution build
- Nightly lab checks: Lighthouse CI or custom runner
- Alerts: email or Slack webhook after aggregate refresh

## Build Order

1. Ship deployment registry and raw event ingestion.
2. Store attribution-rich Web Vitals events in PostgreSQL.
3. Materialize daily and per-deployment p75 rollups.
4. Add dashboard views for site, page group, device, and release comparison.
5. Add nightly Lighthouse runs for critical URLs and basic threshold alerts.
