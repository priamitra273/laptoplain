<?php

namespace App\Models;

use App\Observers\MsProjectPriorityObserver;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(MsProjectPriorityObserver::class)]
class MsProjectPriority extends Model
{
    use LogUsers, SoftDeletes;

    protected $table = 'ms_project_priority';

    protected $fillable = [
        'name',
        'severity',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'owned_id' => 'integer',
    ];

    public function owned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}
