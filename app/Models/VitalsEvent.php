<?php

namespace App\Models;

use Database\Factories\VitalsEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VitalsEvent extends Model
{
    /** @use HasFactory<VitalsEventFactory> */
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_event_id',
        'site_id',
        'deployment_id',
        'page_group_id',
        'page_group_key',
        'environment',
        'occurred_at',
        'build_id',
        'release_version',
        'git_ref',
        'git_commit_sha',
        'metric_name',
        'metric_unit',
        'metric_value',
        'delta_value',
        'rating',
        'url',
        'path',
        'page_title',
        'device_class',
        'navigation_type',
        'browser_name',
        'browser_version',
        'os_name',
        'country_code',
        'effective_connection_type',
        'round_trip_time_ms',
        'downlink_mbps',
        'session_id',
        'page_view_id',
        'correlation_id',
        'trace_id',
        'visitor_hash',
        'attribution',
        'tags',
        'context',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metric_value' => 'decimal:3',
            'delta_value' => 'decimal:3',
            'downlink_mbps' => 'decimal:2',
            'attribution' => 'array',
            'tags' => 'array',
            'context' => 'array',
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

    public function pageGroup(): BelongsTo
    {
        return $this->belongsTo(PageGroup::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(VitalsEventResource::class);
    }

    public function longTasks(): HasMany
    {
        return $this->hasMany(VitalsEventLongTask::class);
    }

    public function javascriptErrors(): HasMany
    {
        return $this->hasMany(VitalsEventJavascriptError::class);
    }
}
