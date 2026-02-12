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


    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }


    public function status()
    {
        return $this->belongsTo(MsTaskStatus::class, 'task_status_id');
    }


    public function type()
    {
        return $this->belongsTo(MsTaskType::class, 'task_type_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_users')
            ->withPivot('is_read')
            ->withTimestamps();
    }
}
