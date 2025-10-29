<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class SiteStatus extends Model
{
    use HasUuid;

    protected $fillable = [
        'name'
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function site_histories(): HasMany
    {
        return $this->hasMany(SiteHistory::class);
    }
}
