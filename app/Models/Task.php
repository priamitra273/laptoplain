<?php

namespace App\Models;

use App\Traits\LogsActivityTask;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, LogUsers, LogsActivityTask;

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
        'completed_at',
    ];

    // protected $appends = ['sub_task'];
    protected $hidden = ['children'];

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Task $task) {

            if (! $task->isForceDeleting()) {
                foreach ($task->children as $child) {
                    $child->delete();
                }
            }

            if ($task->isForceDeleting()) {
                foreach ($task->children()->withTrashed()->get() as $child) {
                    $child->forceDelete();
                }
            }
        });

        static::restoring(function (Task $task) {
            foreach ($task->children()->onlyTrashed()->get() as $child) {
                $child->restore();
            }
        });
    }

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
            ->using(TaskUser::class)
            ->wherePivotNull('deleted_at');
    }

    public function usersWithTrashed()
    {
        return $this->belongsToMany(User::class, 'task_users')
            ->withTimestamps()
            ->withPivot(['owned_id', 'created_by', 'updated_by', 'deleted_by'])
            ->using(TaskUser::class)
            ->withPivot('deleted_at')
            ->withTrashed();
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

    public function scopeWithRecursive($query)
    {
        $query->orderBy('id')
            ->with([
                'status:id,name,severity',
                'priority:id,name,severity',
                'type:id,name,severity',
                'users:id,name',
                'tags:id,name,severity',
                'creator:id,name', // Added creator relationship
                'creator.media',   // Added creator media relationship
                'subTaskRecursive' => function ($q) {
                    $q->orderBy('id')->withRecursive();
                },
            ]);
    }

    public function calculateProgress(): float
    {
        $avg = $this->children()->avg('progress');

        return round($avg ?? (float) $this->progress, 2);
    }

    public function assignUser($userId)
    {
        $pivot = TaskUser::withTrashed()
            ->where('task_id', $this->id)
            ->where('user_id', $userId)
            ->first();

        if ($pivot) {
            if ($pivot->trashed()) {
                $pivot->restore();
            }
            return $pivot;
        }

        return $this->users()->attach($userId);
    }
}
