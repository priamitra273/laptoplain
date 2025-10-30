<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskUser extends Model
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

    /**
     * Relasi ke task
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relasi ke user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke user (owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }

    /**
     * Relasi ke user pembuat
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user pengubah
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke user penghapus
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
