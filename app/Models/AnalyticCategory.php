<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class AnalyticCategory extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'type',
        'has_polygon',
        'has_threshold'
    ];

    public function cctv(): HasMany
    {
        return $this->hasMany(Cctv::class);
    }
}
