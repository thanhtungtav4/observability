<?php

namespace App\Models;

use Database\Factories\VitalsEventJavascriptErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalsEventJavascriptError extends Model
{
    /** @use HasFactory<VitalsEventJavascriptErrorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vitals_event_id',
        'fingerprint',
        'name',
        'message',
        'source_url',
        'source_host',
        'line_number',
        'column_number',
        'handled',
        'stack',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'column_number' => 'integer',
            'handled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function vitalsEvent(): BelongsTo
    {
        return $this->belongsTo(VitalsEvent::class);
    }
}
