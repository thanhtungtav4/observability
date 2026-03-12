<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DeploymentMetricRollup extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'deployment_id',
        'site_id',
        'environment',
        'page_group_key',
        'build_id',
        'release_version',
        'metric_name',
        'metric_unit',
        'device_class',
        'first_seen_at',
        'last_seen_at',
        'sample_count',
        'p50_value',
        'p75_value',
        'good_count',
        'needs_improvement_count',
        'poor_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'sample_count' => 'integer',
            'p50_value' => 'decimal:3',
            'p75_value' => 'decimal:3',
            'good_count' => 'integer',
            'needs_improvement_count' => 'integer',
            'poor_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
