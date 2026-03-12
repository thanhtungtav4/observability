# Collector Payload

## Principles

- keep the browser payload small
- include enough release and attribution context to explain regressions
- support both single-event and batched ingestion
- prefer `fetch(..., { keepalive: true })` when the collector must send `X-Site-Ingest-Key`
- use `sendBeacon` only behind a same-origin proxy or another auth mechanism that does not rely on custom browser headers

## Required Deployment Context

Inject this object into each site during build or deploy:

```html
<script>
window.__PERF_RELEASE__ = {
  siteKey: "smile-clinic",
  environment: "production",
  buildId: "2026.03.12-1",
  releaseVersion: "2026.03.12",
  gitRef: "main",
  gitCommitSha: "abc123def456",
  deployedAt: "2026-03-12T02:10:00Z"
};
</script>
```

## Event Shape

Each Web Vitals event should send:

| Field | Type | Notes |
| --- | --- | --- |
| `eventId` | string | UUID generated in the browser |
| `occurredAt` | string | ISO 8601 timestamp |
| `metricName` | string | `lcp`, `inp`, `cls`, `fcp`, `ttfb` |
| `metricUnit` | string | `ms` or `score` |
| `metricValue` | number | final metric value |
| `deltaValue` | number or null | delta from prior report if present |
| `rating` | string | `good`, `needs_improvement`, `poor` |
| `url` | string | canonical URL without tracking params |
| `path` | string | path portion only |
| `pageGroupKey` | string | normalized route group |
| `deviceClass` | string | `mobile`, `desktop`, `tablet`, `unknown` |
| `navigationType` | string | `navigate`, `reload`, `back-forward`, and so on |
| `effectiveConnectionType` | string | browser network hint if available |
| `browserName` | string | parsed or supplied by client helper |
| `osName` | string | optional |
| `sessionId` | string | UUID per browser session |
| `pageViewId` | string | UUID per page view |
| `correlationId` | string | optional cross-tier request or session correlation key |
| `traceId` | string | optional trace identifier if the page already has one |
| `release` | object | build and deploy metadata |
| `attribution` | object | metric-specific evidence |
| `tags` | object | optional extra dimensions |
| `context` | object | optional collector state such as hydration phase or request keys |
| `resources` | array | optional resource timing evidence linked to the event |
| `longTasks` | array | optional main-thread task evidence linked to the event |
| `errors` | array | optional JavaScript errors observed in the same session window |

## Single Event Example

```json
{
  "siteKey": "smile-clinic",
  "environment": "production",
  "events": [
    {
      "eventId": "4ecf75c3-90c3-4de9-a51c-63a0f4b03e38",
      "occurredAt": "2026-03-12T02:14:22.332Z",
      "metricName": "lcp",
      "metricUnit": "ms",
      "metricValue": 4212,
      "deltaValue": 4212,
      "rating": "poor",
      "url": "https://smile.example.com/",
      "path": "/",
      "pageGroupKey": "home",
      "deviceClass": "mobile",
      "navigationType": "navigate",
      "effectiveConnectionType": "4g",
      "browserName": "Chrome",
      "osName": "Android",
      "sessionId": "f46cc9df-92e8-441e-9052-583d07a8de9d",
      "pageViewId": "e10e7c53-e6d8-4567-b9eb-73506b2414fc",
      "correlationId": "corr-home-001",
      "traceId": "trace-home-001",
      "release": {
        "buildId": "2026.03.12-1",
        "releaseVersion": "2026.03.12",
        "gitRef": "main",
        "gitCommitSha": "abc123def456",
        "deployedAt": "2026-03-12T02:10:00Z"
      },
      "attribution": {
        "lcpElement": "img.hero-image",
        "lcpUrl": "https://cdn.example.com/hero.webp",
        "timeToFirstByte": 540,
        "resourceLoadDelay": 210,
        "resourceLoadDuration": 1320,
        "elementRenderDelay": 2142
      },
      "tags": {
        "countryCode": "VN",
        "experiment": "hero-v2"
      },
      "context": {
        "collectorVersion": "1.2.0",
        "hydrationPhase": "after-hydration",
        "routeTransitionType": "document",
        "apiRequestKeys": ["availability-api"]
      },
      "resources": [
        {
          "url": "https://cdn.example.com/hero.webp",
          "resourceType": "image",
          "initiatorType": "img",
          "durationMs": 1320,
          "transferSize": 420000,
          "decodedBodySize": 950000,
          "cacheState": "miss",
          "priority": "high",
          "renderBlocking": false,
          "isLcpCandidate": true
        }
      ],
      "longTasks": [
        {
          "name": "script-evaluation",
          "scriptUrl": "https://app.example.com/build/app.js",
          "invokerType": "event-listener",
          "containerSelector": "button.book-now",
          "startTimeMs": 1440,
          "durationMs": 280,
          "blockingDurationMs": 180
        }
      ],
      "errors": [
        {
          "name": "TypeError",
          "message": "Cannot read properties of undefined",
          "sourceUrl": "https://app.example.com/build/app.js",
          "lineNumber": 182,
          "columnNumber": 24,
          "handled": false
        }
      ]
    }
  ]
}
```

## Attribution Shapes

### LCP

```json
{
  "lcpElement": "img.hero-image",
  "lcpUrl": "https://cdn.example.com/hero.webp",
  "timeToFirstByte": 540,
  "resourceLoadDelay": 210,
  "resourceLoadDuration": 1320,
  "elementRenderDelay": 2142
}
```

### CLS

```json
{
  "largestShiftTarget": ".promo-banner",
  "largestShiftTime": 1790,
  "largestShiftValue": 0.18,
  "culprits": [
    {
      "selector": ".promo-banner",
      "reason": "image_without_dimensions"
    }
  ]
}
```

### INP

```json
{
  "interactionTarget": "button.book-now",
  "interactionType": "click",
  "inputDelay": 122,
  "processingDuration": 168,
  "presentationDelay": 211
}
```

## Minimal Client Snippet

```js
import { onCLS, onINP, onLCP, onFCP, onTTFB } from "web-vitals/attribution";

const release = window.__PERF_RELEASE__ || {};
const sessionId = crypto.randomUUID();
const pageViewId = crypto.randomUUID();
const endpoint = "https://perf.example.com/api/v1/collect/web-vitals";

function detectDeviceClass() {
  if (window.matchMedia("(max-width: 767px)").matches) return "mobile";
  if (window.matchMedia("(max-width: 1024px)").matches) return "tablet";
  return "desktop";
}

function pageGroupFor(pathname) {
  if (pathname === "/") return "home";
  if (pathname.startsWith("/blog")) return "blog";
  if (pathname.startsWith("/pricing")) return "pricing";
  return "other";
}

function sendMetric(metric) {
  const payload = {
    siteKey: release.siteKey,
    environment: release.environment,
    events: [
      {
        eventId: crypto.randomUUID(),
        occurredAt: new Date().toISOString(),
        metricName: metric.name.toLowerCase(),
        metricUnit: metric.name === "CLS" ? "score" : "ms",
        metricValue: metric.value,
        deltaValue: metric.delta ?? null,
        rating: metric.rating,
        url: location.origin + location.pathname,
        path: location.pathname,
        pageGroupKey: pageGroupFor(location.pathname),
        deviceClass: detectDeviceClass(),
        navigationType: performance.getEntriesByType("navigation")[0]?.type ?? "navigate",
        effectiveConnectionType: navigator.connection?.effectiveType ?? null,
        browserName: navigator.userAgent,
        osName: null,
        sessionId,
        pageViewId,
        release: {
          buildId: release.buildId,
          releaseVersion: release.releaseVersion,
          gitRef: release.gitRef,
          gitCommitSha: release.gitCommitSha,
          deployedAt: release.deployedAt
        },
        attribution: metric.attribution ?? {},
        tags: {}
      }
    ]
  };

  const body = JSON.stringify(payload);

  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Site-Ingest-Key": window.__PERF_INGEST_KEY__
    },
    body,
    keepalive: true
  });
}

onLCP(sendMetric);
onINP(sendMetric);
onCLS(sendMetric);
onFCP(sendMetric);
onTTFB(sendMetric);
```

## Validation Rules

- reject unknown metric names
- reject events missing `buildId` in production
- cap `attribution` and `tags` payload size
- normalize URLs to strip tracking parameters
- map unknown routes to `other` instead of storing arbitrary path groups
