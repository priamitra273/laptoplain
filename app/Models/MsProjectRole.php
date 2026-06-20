<?php

namespace App\Models;

use App\Observers\MsProjectRoleObserver;
use App\Traits\LogsActivityProjectRole;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(MsProjectRoleObserver::class)]
class MsProjectRole extends Model
{
    use LogsActivityProjectRole, LogUsers, SoftDeletes;

    protected $fillable = [
        'name',
        'config',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'owned_id' => 'integer',
        'config' => 'json',
    ];

    public function owned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}
