create extension if not exists pgcrypto;

create table teams (
    id uuid primary key default gen_random_uuid(),
    slug text not null unique,
    name text not null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table sites (
    id uuid primary key default gen_random_uuid(),
    team_id uuid not null references teams(id) on delete restrict,
    slug text not null unique,
    name text not null,
    default_environment text not null default 'production'
        check (default_environment in ('production', 'staging', 'preview', 'development')),
    timezone text not null default 'UTC',
    status text not null default 'active'
        check (status in ('active', 'paused', 'archived')),
    ingest_key_hash text not null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table site_domains (
    id uuid primary key default gen_random_uuid(),
    site_id uuid not null references sites(id) on delete cascade,
    environment text not null
        check (environment in ('production', 'staging', 'preview', 'development')),
    domain text not null,
    is_primary boolean not null default false,
    created_at timestamptz not null default now(),
    unique (environment, domain)
);

create table deployments (
    id uuid primary key default gen_random_uuid(),
    site_id uuid not null references sites(id) on delete cascade,
    environment text not null
        check (environment in ('production', 'staging', 'preview', 'development')),
    build_id text not null,
    release_version text,
    git_ref text,
    git_commit_sha text,
    deployed_at timestamptz not null,
    actor_name text,
    ci_source text,
    metadata jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (site_id, environment, build_id)
);

create table page_groups (
    id uuid primary key default gen_random_uuid(),
    site_id uuid not null references sites(id) on delete cascade,
    group_key text not null,
    label text not null,
    pattern_type text not null
        check (pattern_type in ('literal', 'prefix', 'regex', 'rule_set')),
    match_rules jsonb not null default '[]'::jsonb,
    is_active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (site_id, group_key)
);

create table vitals_events (
    id uuid primary key default gen_random_uuid(),
    site_id uuid not null references sites(id) on delete cascade,
    deployment_id uuid references deployments(id) on delete set null,
    page_group_id uuid references page_groups(id) on delete set null,
    page_group_key text not null,
    environment text not null
        check (environment in ('production', 'staging', 'preview', 'development')),
    occurred_at timestamptz not null,
    build_id text not null,
    release_version text,
    git_ref text,
    git_commit_sha text,
    metric_name text not null
        check (metric_name in ('lcp', 'inp', 'cls', 'fcp', 'ttfb')),
    metric_unit text not null
        check (metric_unit in ('ms', 'score')),
    metric_value double precision not null check (metric_value >= 0),
    delta_value double precision,
    rating text not null
        check (rating in ('good', 'needs_improvement', 'poor')),
    url text not null,
    path text not null,
    page_title text,
    device_class text not null
        check (device_class in ('mobile', 'desktop', 'tablet', 'unknown')),
    navigation_type text,
    browser_name text,
    browser_version text,
    os_name text,
    country_code char(2),
    effective_connection_type text,
    round_trip_time_ms integer,
    downlink_mbps numeric(8, 2),
    session_id uuid,
    page_view_id uuid,
    visitor_hash text,
    attribution jsonb not null default '{}'::jsonb,
    tags jsonb not null default '{}'::jsonb,
    created_at timestamptz not null default now()
);

create table synthetic_runs (
    id uuid primary key default gen_random_uuid(),
    site_id uuid not null references sites(id) on delete cascade,
    deployment_id uuid references deployments(id) on delete set null,
    page_group_id uuid references page_groups(id) on delete set null,
    page_group_key text not null,
    environment text not null
        check (environment in ('production', 'staging', 'preview', 'development')),
    occurred_at timestamptz not null,
    build_id text not null,
    release_version text,
    git_ref text,
    git_commit_sha text,
    runner text not null default 'lighthouse',
    device_preset text not null
        check (device_preset in ('mobile', 'desktop')),
    page_url text not null,
    page_path text not null,
    performance_score numeric(5, 2) not null,
    accessibility_score numeric(5, 2),
    best_practices_score numeric(5, 2),
    seo_score numeric(5, 2),
    fcp_ms integer,
    lcp_ms integer,
    tbt_ms integer,
    cls_score numeric(8, 3),
    speed_index_ms integer,
    inp_ms integer,
    opportunities jsonb not null default '[]'::jsonb,
    diagnostics jsonb not null default '{}'::jsonb,
    screenshot_url text,
    trace_url text,
    report_url text,
    created_at timestamptz not null default now()
);

create index deployments_site_env_deployed_at_idx
    on deployments (site_id, environment, deployed_at desc);

create index page_groups_site_group_key_idx
    on page_groups (site_id, group_key);

create index vitals_events_rollup_idx
    on vitals_events (site_id, environment, metric_name, device_class, occurred_at);

create index vitals_events_page_group_idx
    on vitals_events (page_group_key, metric_name, device_class, occurred_at);

create index vitals_events_deployment_idx
    on vitals_events (deployment_id, metric_name, device_class, occurred_at);

create index vitals_events_build_idx
    on vitals_events (site_id, environment, build_id, metric_name, device_class, occurred_at);

create index vitals_events_occurred_brin_idx
    on vitals_events using brin (occurred_at);

create index vitals_events_attribution_gin_idx
    on vitals_events using gin (attribution jsonb_path_ops);

create index vitals_events_tags_gin_idx
    on vitals_events using gin (tags jsonb_path_ops);

create index synthetic_runs_site_env_occurred_at_idx
    on synthetic_runs (site_id, environment, occurred_at desc);

create index synthetic_runs_page_group_idx
    on synthetic_runs (page_group_key, device_preset, occurred_at desc);

create index synthetic_runs_build_idx
    on synthetic_runs (site_id, environment, build_id, occurred_at desc);

create materialized view daily_metric_rollups as
select
    date_trunc('day', occurred_at at time zone 'UTC')::date as metric_day,
    site_id,
    environment,
    page_group_key,
    deployment_id,
    build_id,
    metric_name,
    metric_unit,
    device_class,
    count(*) as sample_count,
    round((percentile_cont(0.50) within group (order by metric_value))::numeric, 3) as p50_value,
    round((percentile_cont(0.75) within group (order by metric_value))::numeric, 3) as p75_value,
    count(*) filter (where rating = 'good') as good_count,
    count(*) filter (where rating = 'needs_improvement') as needs_improvement_count,
    count(*) filter (where rating = 'poor') as poor_count
from vitals_events
group by
    date_trunc('day', occurred_at at time zone 'UTC')::date,
    site_id,
    environment,
    page_group_key,
    deployment_id,
    build_id,
    metric_name,
    metric_unit,
    device_class;

create unique index daily_metric_rollups_unique_idx
    on daily_metric_rollups (
        metric_day,
        site_id,
        environment,
        page_group_key,
        coalesce(deployment_id, '00000000-0000-0000-0000-000000000000'::uuid),
        build_id,
        metric_name,
        metric_unit,
        device_class
    );

create materialized view deployment_metric_rollups as
select
    deployment_id,
    site_id,
    environment,
    page_group_key,
    build_id,
    release_version,
    metric_name,
    metric_unit,
    device_class,
    min(occurred_at) as first_seen_at,
    max(occurred_at) as last_seen_at,
    count(*) as sample_count,
    round((percentile_cont(0.50) within group (order by metric_value))::numeric, 3) as p50_value,
    round((percentile_cont(0.75) within group (order by metric_value))::numeric, 3) as p75_value,
    count(*) filter (where rating = 'good') as good_count,
    count(*) filter (where rating = 'needs_improvement') as needs_improvement_count,
    count(*) filter (where rating = 'poor') as poor_count
from vitals_events
where deployment_id is not null
group by
    deployment_id,
    site_id,
    environment,
    page_group_key,
    build_id,
    release_version,
    metric_name,
    metric_unit,
    device_class;

create unique index deployment_metric_rollups_unique_idx
    on deployment_metric_rollups (
        deployment_id,
        page_group_key,
        build_id,
        metric_name,
        metric_unit,
        device_class
    );

-- Refresh strategy:
-- refresh materialized view concurrently daily_metric_rollups;
-- refresh materialized view concurrently deployment_metric_rollups;
