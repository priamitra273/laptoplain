<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Regency extends Model
{
    use HasUuid;

    protected $fillable = [
        'province_id',
        'name',
        'geojson'
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function cctv(): HasManyThrough
    {
        return $this->hasManyThrough(Cctv::class, Site::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'geojson' => 'array',
        ];
    }
}
