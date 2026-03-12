<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SyntheticRun extends Model
{
    /** @use HasFactory<\Database\Factories\SyntheticRunFactory> */
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
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
        'runner',
        'device_preset',
        'page_url',
        'page_path',
        'performance_score',
        'accessibility_score',
        'best_practices_score',
        'seo_score',
        'fcp_ms',
        'lcp_ms',
        'tbt_ms',
        'cls_score',
        'speed_index_ms',
        'inp_ms',
        'opportunities',
        'diagnostics',
        'screenshot_url',
        'trace_url',
        'report_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'performance_score' => 'decimal:2',
            'accessibility_score' => 'decimal:2',
            'best_practices_score' => 'decimal:2',
            'seo_score' => 'decimal:2',
            'cls_score' => 'decimal:3',
            'opportunities' => 'array',
            'diagnostics' => 'array',
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
}
