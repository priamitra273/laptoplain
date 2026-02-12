<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskUser extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'task_users';

    protected $fillable = [
        'task_id',
        'user_id',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
