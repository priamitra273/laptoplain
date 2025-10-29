<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteHistory extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes, LogUsers;

    protected $fillable = [
        'site_id',
        'site_status_id',
        'remark'
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SiteStatus::class, 'site_status_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
