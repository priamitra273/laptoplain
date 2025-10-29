<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Department extends Model
{
    use SoftDeletes, HasUuid, LogUsers;

    protected $fillable = ['name'];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function cctv(): HasMany
    {
        return $this->hasMany(Cctv::class);
    }
}
