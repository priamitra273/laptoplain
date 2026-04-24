<?php

namespace App\Repositories;

use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SprintReportRepository
{
    public function getBurndownChartData(int $sprintId): array
    {
        $query = <<<'SQL'
            with
                ps as (
                    select *
                    from project_sprints
                    where id = :id and deleted_at is null
                ),
                range as (
                    select generate_series(ps.start_date::date, ps.end_date::date, interval '1 days') as date
                    from ps
                ),
                weekdays as (
                    select date::date
                    from range
                    where extract(ISODOW FROM date) not in (6,7)
                ),
                filtered_data as (
                    select tasks.*, coalesce(tasks.due_date, ps.end_date) as plan_end_date
                    from tasks
                    inner join sprint_task on sprint_task.task_id = tasks.id
                    inner join ps on sprint_task.sprint_id = ps.id
                    where tasks.deleted_at is null
                )
            select wd.date,
                'DAY-' || row_number() over (order by wd.date) as label,
                count(fd.id) filter ( where fd.plan_end_date > wd.date ) as total_plan,
                count(fd.id) filter ( where fd.completed_at is null or fd.completed_at > wd.date ) as total_actual
            from weekdays wd,
                filtered_data fd
            group by wd.date
            order by wd.date
        SQL;

        return DB::select($query, ['id' => $sprintId]);
    }

    public function getSprintStatusData(int $sprintId): array
    {
        return [
            'completed_tasks' => $this->getCompletedTasksBySprint($sprintId),
            'incomplete_tasks' => $this->getIncompleteTasksBySprint($sprintId),
        ];
    }

    /**
     * Get completed tasks for a sprint
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, App\Models\Task>
     */
    protected function getCompletedTasksBySprint(int $sprintId): Collection
    {
        $sprint = ProjectSprint::with([
            'tasks' => function ($query) {
                $query->with([
                    'project',
                    'status',
                    'priority',
                    'category',
                    'users',
                    'rootAncestor' => fn ($q) => $q->whereRelation('category', 'name', 'Epic'),
                ])
                    ->whereHas('status', fn ($q) => $q->whereIn('name', ['Completed', 'Finished', 'Done']));
            },
        ])->findOrFail($sprintId);

        return $sprint->tasks;
    }

    /**
     * Get incomplete tasks for a sprint
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, App\Models\Task>
     */
    protected function getIncompleteTasksBySprint(int $sprintId): Collection
    {
        $activityLogs = DB::table('activity_log')
            ->where('subject_type', ProjectSprint::class)
            ->where('subject_id', $sprintId)
            ->where('log_name', 'like', 'move_incomplete_%')
            ->get();

        $taskIds = [];
        foreach ($activityLogs as $log) {
            $properties = json_decode($log->properties, true);
            if (isset($properties['detached'])) {
                $taskIds = array_merge($taskIds, (array) $properties['detached']);
            }
        }

        $incompleteTasks = collect();

        if (! empty($taskIds)) {
            $incompleteTasks = Task::with([
                'project',
                'status',
                'priority',
                'category',
                'users',
                'rootAncestor' => fn ($q) => $q->whereRelation('category', 'name', 'Epic'),
            ])
                ->whereIn('id', array_unique($taskIds))
                ->get();
        }

        return $incompleteTasks;
    }
}
