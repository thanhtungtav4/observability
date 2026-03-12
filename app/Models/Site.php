<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\SiteFactory> */
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'slug',
        'name',
        'default_environment',
        'timezone',
        'status',
        'ingest_key_hash',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'ingest_key_hash',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function siteDomains(): HasMany
    {
        return $this->hasMany(SiteDomain::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function pageGroups(): HasMany
    {
        return $this->hasMany(PageGroup::class);
    }

    public function vitalsEvents(): HasMany
    {
        return $this->hasMany(VitalsEvent::class);
    }

    public function syntheticRuns(): HasMany
    {
        return $this->hasMany(SyntheticRun::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function setIngestKey(string $plainTextKey): static
    {
        $this->forceFill([
            'ingest_key_hash' => Hash::make($plainTextKey),
        ]);

        return $this;
    }

    public function matchesIngestKey(string $plainTextKey): bool
    {
        return Hash::check($plainTextKey, $this->ingest_key_hash);
    }
}
