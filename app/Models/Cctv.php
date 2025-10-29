<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Cctv extends Model
{
    use SoftDeletes, HasUuid, LogUsers, LogsActivity;

    protected $fillable = [
        'name',
        'site_id',
        'department_id',
        'device_id',
        'streaming_status_id',
        'analytic_server_id',
        'analytic_category_id',
        'analytic_status_id',
        'ip_flussonic',
        'link_rtsp',
        'link_embed',
        'link_embed_nonrelay',
        'link_embed_bb',
        'submit_bast_date',
        'updated_streaming_at',
        'updated_analytic_at',
        'updated_preconfig_at',
        'is_active',
        'polygon',
        'threshold'
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function analytic_category(): BelongsTo
    {
        return $this->belongsTo(AnalyticCategory::class);
    }

    public function analytic_status(): BelongsTo
    {
        return $this->belongsTo(AnalyticStatus::class);
    }

    public function streaming_status(): BelongsTo
    {
        return $this->belongsTo(StreamingStatus::class);
    }

    public function analytic_server(): BelongsTo
    {
        return $this->belongsTo(AnalyticServer::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
