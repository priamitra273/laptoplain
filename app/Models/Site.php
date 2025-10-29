<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Site extends Model
{
    use SoftDeletes, HasUuid, LogUsers, LogsActivity;

    protected $fillable = [
        'site_id',
        'site_name',
        'latitude',
        'longitude',
        'site_status_id',
        'project_id',
        'replacement_to',
        'regency_id'
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SiteStatus::class, 'site_status_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'replacement_to');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function replaced_by(): HasOne
    {
        return $this->hasOne(Site::class, 'replacement_to');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SiteHistory::class);
    }

    public function cctv(): HasMany
    {
        return $this->hasMany(Cctv::class)->orderBy('id');
    }
}
