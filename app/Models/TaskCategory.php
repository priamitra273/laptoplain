<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskCategory extends Model
{
    use LogUsers, SoftDeletes;

    protected $fillable = [
        'name',
        'icon',
        'severity',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_category_id');
    }
}
