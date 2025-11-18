<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, LogUsers;

    protected $table = 'tasks';

    protected $fillable = [
        'owned_id',
        'parent_id',
        'status_id',
        'priority_id',
        'type_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'emoji',
        'title',
        'description',
        'start_date',
        'due_date',
        'progress',
        'sequence_number',
        'is_archived',
        'project_id',
    ];

    protected $appends = ['sub_task'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }


    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function status()
    {
        return $this->belongsTo(MsTaskStatus::class, 'status_id');
    }

    public function priority()
    {
        return $this->belongsTo(MsTaskPriority::class, 'priority_id');
    }


    public function type()
    {
        return $this->belongsTo(MsTaskType::class, 'type_id');
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

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_users')
            ->withTimestamps()
            ->withPivot(['owned_id', 'created_by', 'updated_by', 'deleted_by'])
            ->using(TaskUser::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags()
    {
        return $this->morphToMany(
            Tag::class,
            'model',
            'taggables',
            'model_id',
            'tag_id'
        )->withTimestamps()
            ->withPivot(['owned_id', 'created_by', 'updated_by', 'deleted_by']);
    }

    public function subTaskRecursive()
    {
        return $this->children()->with('subTaskRecursive');
    }

    public function getSubTaskAttribute()
    {
        return $this->subTaskRecursive;
    }

    public function calculateProgress(): float
    {

        if ($this->children->isEmpty()) {
            return (float) $this->progress;
        }

        $total = 0;
        $count = 0;

        foreach ($this->children as $child) {
            $total += $child->calculateProgress();
            $count++;
        }

        return $count > 0 ? round($total / $count, 2) : (float) $this->progress;
    }
}
