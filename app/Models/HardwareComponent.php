<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class HardwareComponent extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = [
        'code',
        'name'
    ];

    public function hardwares(): HasMany
    {
        return $this->hasMany(Hardware::class);
    }
}
