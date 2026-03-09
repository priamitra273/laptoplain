<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\LogUsers;

class SprintTask extends Pivot
{
    use LogUsers;

    protected $table = 'sprint_task';

    protected $fillable = [
        'sprint_id',
        'task_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public $timestamps = true;
}
