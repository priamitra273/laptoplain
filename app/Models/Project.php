<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\ProjectMember;
use App\Models\User;

class Project extends Model
{
    use SoftDeletes, LogUsers;

    protected $table = 'projects';

    protected $fillable = [
        'status_id',
        'priority_id',
        'owner_id',
        'owned_id',
        'emoji',
        'title',
        'description',
        'start_date',
        'due_date',
        'progress',
        'sequence_number',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'progress' => 'double',
    ];


    protected $with = [
        'status',
        'priority',
        'owner',
        'owned',
    ];

    public function status()
    {
        return $this->belongsTo(MsProjectStatus::class, 'status_id');
    }

    public function priority()
    {
        return $this->belongsTo(MsProjectPriority::class, 'priority_id');
    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function owned()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }


    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function getStatusNameAttribute(): ?string
    {
        return $this->status->name ?? null;
    }

    public function getPriorityNameAttribute(): ?string
    {
        return $this->priority->name ?? null;
    }

    public function getOwnerNameAttribute(): ?string
    {
        return $this->owner->name ?? null;
    }

    public function getOwnedNameAttribute(): ?string
    {
        return $this->owned->name ?? null;
    }

    public function setProgressAttribute($value)
    {
        $this->attributes['progress'] = round(min(max($value, 0), 100), 2);
    }

    public function allTasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id')
            ->whereNull('parent_id')
            ->with('children');
    }

    public function calculateProgress(): float
    {
        $tasks = $this->tasks()->with('children')->get();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $total = 0;
        $count = 0;

        foreach ($tasks as $task) {
            $total += $task->calculateProgress();
            $count++;
        }

        return round($total / $count, 2);
    }

    public function scopeVisibleFor($query, User $user)
    {
        $allowedRoles = ["super-admin-admin", "watcher-admin"];
        $roles = $user->getRoleNames();

        if ($roles->intersect($allowedRoles)->isNotEmpty()) {
            return $query;
        }

        return $query->whereHas('projectMembers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }
}
