<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Province extends Model
{
    use HasUuid;

    protected $fillable = [
        'name'
    ];

    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class);
    }
}
