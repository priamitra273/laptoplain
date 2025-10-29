<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Project extends Model
{
    use SoftDeletes, HasUuid, LogUsers;

    protected $fillable = [
        'name',
        'start_date',
        'finish_date',
        'plan_site',
        'plan_cctv'
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function cctv(): HasManyThrough
    {
        return $this->hasManyThrough(Cctv::class, Site::class);
    }
}
