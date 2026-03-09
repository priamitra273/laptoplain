<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogUsers;

class TaskCategory extends Model
{
    use SoftDeletes, LogUsers;

    protected $table = 'task_categories';

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
