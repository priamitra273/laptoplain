<?php

namespace App\Models;

use App\Facades\Sqids;
use App\Traits\LogsActivityTask;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Task extends Model implements HasMedia
{
    use HasFactory, LogsActivityTask, LogUsers, SoftDeletes;
    use HasRecursiveRelationships, InteractsWithMedia;

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
        'story_points',
        'sequence_number',
        'is_archived',
        'project_id',
        'completed_at',
        'task_category_id',
    ];

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

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (is_string($value) && ! ctype_digit($value)) {
            try {
                $value = Sqids::decode($value);
            } catch (\Throwable $e) {
                throw (new ModelNotFoundException)->setModel(static::class);
            }
        }

        return $this->where('id', $value)->firstOrFail();
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

    // ← FIX: tambah eager load category dan relasi lainnya
    public function subTaskRecursive()
    {
        return $this->children()->withCount('comments')->with([
            'subTaskRecursive',
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name,email',
            'tags:id,name,severity',
            'creator:id,name',
            'creator.media',
        ]);
    }

    public function getSubTaskAttribute()
    {
        return $this->subTaskRecursive;
    }

    public function scopeWithRecursive($query)
    {
        $query->with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name',
            'tags:id,name,severity',
            'creator:id,name',
            'creator.media',
            'subTaskRecursive' => function ($q) {
                $q->orderBy('sequence_number')->orderBy('id')->withRecursive();
            },
            'media' => fn ($q) => $q->where('collection_name', 'attachments'),
        ]);
    }

    public function calculateProgress(): float
    {
        // Pakai relasi children yang sudah di-eager-load (Project::tasks()->with('children')
        // dan scopeWithRecursive() memuatnya). Hanya query bila benar-benar belum dimuat.
        $children = $this->relationLoaded('children')
            ? $this->children
            : $this->children()->get(['id', 'parent_id', 'progress']);

        $avg = $children->isEmpty() ? null : $children->avg('progress');

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

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    public function sprints()
    {
        return $this->belongsToMany(
            ProjectSprint::class,
            'sprint_task',
            'task_id',
            'sprint_id'
        )->using(SprintTask::class)
            ->withTimestamps();
    }

    public function scopeBacklog($query)
    {
        return $query->whereDoesntHave('sprints');
    }

    public function scopeIssues($query)
    {
        return $query->whereHas('category', fn ($q) => $q->where('name', 'Issue'))
            ->whereDoesntHave('sprints');
    }

    public function scopeEpics($query)
    {
        return $query->whereHas('category', fn ($q) => $q->where('name', 'Epic'))
            ->whereNull('parent_id');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }
}
