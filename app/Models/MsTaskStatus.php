<?php

namespace App\Models;

use App\Traits\LogsActivityTaskStatus;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsTaskStatus extends Model
{
    use HasFactory, SoftDeletes, LogUsers, LogsActivityTaskStatus;

    protected $table = 'ms_task_statuses';

    protected $fillable = [
        'name',
        'severity',
        'score',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
