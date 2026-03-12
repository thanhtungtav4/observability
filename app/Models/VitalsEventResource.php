<?php

namespace App\Models;

use Database\Factories\VitalsEventResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalsEventResource extends Model
{
    /** @use HasFactory<VitalsEventResourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vitals_event_id',
        'resource_url',
        'resource_host',
        'resource_path',
        'resource_type',
        'initiator_type',
        'duration_ms',
        'transfer_size',
        'decoded_body_size',
        'cache_state',
        'priority',
        'render_blocking',
        'is_lcp_candidate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'decimal:3',
            'transfer_size' => 'integer',
            'decoded_body_size' => 'integer',
            'render_blocking' => 'boolean',
            'is_lcp_candidate' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function vitalsEvent(): BelongsTo
    {
        return $this->belongsTo(VitalsEvent::class);
    }
}
