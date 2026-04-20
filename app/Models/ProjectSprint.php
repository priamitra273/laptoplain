<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSprint extends Model
{
    use LogUsers, SoftDeletes;

    protected $fillable = [
        'project_id',
        'sprint_status_id',
        'name',
        'goal',
        'duration',
        'start_date',
        'end_date',
        'order',
        'retrospective',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function status()
    {
        return $this->belongsTo(MsSprintStatus::class, 'sprint_status_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'sprint_task', 'sprint_id', 'task_id')
            ->using(SprintTask::class)
            ->withTimestamps();
    }

    public function sprintTasks()
    {
        return $this->tasks()
            ->whereHas('category', fn ($q) => $q->whereIn('name', ['Story', 'Task']));
    }

    public function isActive(): bool
    {
        return $this->status?->name === 'Active';
    }

    public function calculateProgress(): float
    {
        $tasks = $this->tasks()->with('status')->get();
        if ($tasks->isEmpty()) {
            return 0;
        }

        $completed = $tasks->filter(
            fn ($t) => strtoupper($t->status?->name) === 'Completed'
        )->count();

        return round(($completed / $tasks->count()) * 100, 2);
    }
}
