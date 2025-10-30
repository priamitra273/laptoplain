<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsProjectStatus extends Model
{
    use SoftDeletes, LogUsers;

    protected $table = 'ms_project_statuses';

    protected $fillable = [
        'name',
        'severity',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'id' => 'integer',
        'owned_id' => 'integer'
    ];

    public function owned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}
