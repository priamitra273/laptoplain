<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'task_id',
        'task_status_id',
        'task_type_id',
        'message',
    ];

    /**
     * Relasi ke Task
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relasi ke Master Task Status
     */
    public function status()
    {
        return $this->belongsTo(MsTaskStatus::class, 'task_status_id');
    }

    /**
     * Relasi ke Master Task Type
     */
    public function type()
    {
        return $this->belongsTo(MsTaskType::class, 'task_type_id');
    }
}
