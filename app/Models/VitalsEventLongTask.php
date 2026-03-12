<?php

namespace App\Models;

use Database\Factories\VitalsEventLongTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalsEventLongTask extends Model
{
    /** @use HasFactory<VitalsEventLongTaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vitals_event_id',
        'name',
        'script_url',
        'script_host',
        'invoker_type',
        'container_selector',
        'start_time_ms',
        'duration_ms',
        'blocking_duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time_ms' => 'decimal:3',
            'duration_ms' => 'decimal:3',
            'blocking_duration_ms' => 'decimal:3',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function vitalsEvent(): BelongsTo
    {
        return $this->belongsTo(VitalsEvent::class);
    }
}
