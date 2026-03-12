<?php

return [
    'environments' => [
        'production',
        'staging',
        'preview',
        'development',
    ],

    'site_statuses' => [
        'active',
        'paused',
        'archived',
    ],

    'page_group_pattern_types' => [
        'literal',
        'prefix',
        'regex',
        'rule_set',
    ],

    'metric_names' => [
        'lcp',
        'inp',
        'cls',
        'fcp',
        'ttfb',
    ],

    'metric_units' => [
        'ms',
        'score',
    ],

    'ratings' => [
        'good',
        'needs_improvement',
        'poor',
    ],

    'device_classes' => [
        'mobile',
        'desktop',
        'tablet',
        'unknown',
    ],

    'synthetic_runners' => [
        'lighthouse',
    ],

    'synthetic_device_presets' => [
        'mobile',
        'desktop',
    ],

    'health_thresholds' => [
        'lcp' => 2500.0,
        'inp' => 200.0,
        'cls' => 0.1,
    ],
];
