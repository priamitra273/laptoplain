<?php

namespace App\Repositories;

use App\Data\Project\Lazy\TaskCardData;
use App\Data\Project\Lazy\TaskListItemData;
use App\Data\Task\ProjectTaskData;
use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsSprintStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectRepository
{
    /**
     * Cache tag grouping every cached project shell, so the whole group can be
     * flushed at once when a globally-shared dependency (status/priority/role/user) changes.
     */
    private const SHELL_CACHE_TAG = 'project-shell';

    /**
     * Find a project by its integer ID.
     */
    public function findById(int $id): Project
    {
        return Project::findOrFail($id);
    }

    /**
     * Cache key for a single project's shell payload.
     */
    private function shellCacheKey(int $projectId): string
    {
        return self::SHELL_CACHE_TAG.':'.$projectId;
    }

    /**
     * Forget the cached shell for a single project (used when a change is scoped
     * to one project, e.g. its status/priority or one of its members).
     */
    public function forgetShell(int $projectId): void
    {
        Cache::tags(self::SHELL_CACHE_TAG)->forget($this->shellCacheKey($projectId));
    }

    /**
     * Flush every cached project shell (used when a globally-shared dependency
     * changes and we cannot cheaply tell which projects are affected).
     */
    public function flushShells(): void
    {
        Cache::tags(self::SHELL_CACHE_TAG)->flush();
    }

    /**
     * Get all projects visible to the given user, with status and priority.
     */
    public function getAllVisibleForUser(User $user): Collection
    {
        return Project::with([
            'status:id,name,severity',
            'priority:id,name,severity',
        ])
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Find a project by encoded ID and eager-load all relations needed for the show page.
     *
     * @throws NotFoundHttpException
     */
    public function findWithRelationsForShow(string $encoded): Project
    {
        try {
            $projectId = Sqids::decode($encoded);

            return Project::with([
                'status:id,name,severity',
                'priority:id,name,severity',
                'projectMembers' => function ($query) {
                    $query->whereHas('user', fn ($q) => $q->where('is_active', true));
                },
                'projectMembers.user:id,name,email',
                'projectMembers.user.media',
                'projectMembers.role:id,name,config',
                'tasks',
            ])->findOrFail($projectId);
        } catch (\Exception $e) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Flat relation list shared by tree, sprint, and backlog task loading.
     *
     * @return array<int|string, string|\Closure>
     */
    protected function taskTreeRelations(): array
    {
        return [
            'status:id,name,severity,score',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name,email',
            'users.media',
            'tags:id,name,severity',
            'creator:id,name,email',
            'creator.media',
            'media' => fn ($q) => $q->where('collection_name', 'attachments'),
        ];
    }

    /**
     * Load the project's full task tree using a single recursive CTE for the rows,
     * then batch-load relations in the app (query count independent of tree depth).
     *
     * @return DataCollection<int, ProjectTaskData>
     */
    public function getTaskTree(int $projectId): DataCollection
    {
        $rows = DB::select($this->recursiveTaskTreeSql(), ['projectId' => $projectId]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Task> $tasks */
        $tasks = Task::hydrate($rows);

        if ($tasks->isEmpty()) {
            return ProjectTaskData::collect([], DataCollection::class);
        }

        $tasks->load($this->taskTreeRelations());

        return ProjectTaskData::treeFromTasks($tasks->toBase());
    }

    /**
     * Recursive CTE that returns the full task tree rows for a project (rows only;
     * relations are batch-loaded by the caller, so query count is depth-independent).
     */
    protected function recursiveTaskTreeSql(): string
    {
        return <<<'SQL'
            WITH RECURSIVE task_tree AS (
                SELECT t.id, t.owned_id, t.parent_id, t.status_id, t.priority_id, t.type_id,
                       t.task_category_id, t.created_by, t.updated_by, t.deleted_by,
                       t.emoji, t.title, t.description, t.start_date, t.due_date,
                       t.progress, t.story_points, t.sequence_number, t.is_archived,
                       t.project_id, t.created_at, t.updated_at, t.deleted_at, 0 AS depth
                FROM tasks t
                WHERE t.project_id = :projectId
                  AND t.parent_id IS NULL
                  AND t.deleted_at IS NULL
                UNION ALL
                SELECT c.id, c.owned_id, c.parent_id, c.status_id, c.priority_id, c.type_id,
                       c.task_category_id, c.created_by, c.updated_by, c.deleted_by,
                       c.emoji, c.title, c.description, c.start_date, c.due_date,
                       c.progress, c.story_points, c.sequence_number, c.is_archived,
                       c.project_id, c.created_at, c.updated_at, c.deleted_at, tt.depth + 1
                FROM tasks c
                JOIN task_tree tt ON c.parent_id = tt.id
                WHERE c.deleted_at IS NULL
            )
            SELECT * FROM task_tree
            ORDER BY depth, id
            SQL;
    }

    /**
     * Get users who are not yet members of the project, with their media.
     */
    public function getAvailableUsers(SupportCollection $memberUserIds): Collection
    {
        return User::whereNotIn('id', $memberUserIds)
            ->with('media')
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get active sprints for a project with their tasks + relations eager-loaded.
     *
     * Returns Eloquent ProjectSprint models (NOT DTOs) because sprints carry
     * envelope fields; callers must map each sprint's tasks to ProjectTaskData
     * before serializing (see ProjectService::formatSprints()).
     */
    public function getActiveSprints(int $projectId): Collection
    {
        return ProjectSprint::with([
            'status:id,name,severity',
            'tasks' => function ($q) {
                $q->with($this->taskTreeRelations())
                    ->where(function ($taskQuery) {
                        $taskQuery->whereNull('parent_id')
                            ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
                    })
                    ->orderBy('sequence_number')
                    ->orderBy('id');
            },
        ])
            ->where('project_id', $projectId)
            ->whereNot('sprint_status_id', MsSprintStatus::completed()->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get backlog tasks (not in any sprint) for a project, with user media.
     *
     * @return DataCollection<int, ProjectTaskData>
     */
    public function getBacklogTasks(int $projectId): DataCollection
    {
        $tasks = Task::with($this->taskTreeRelations())
            ->where('project_id', $projectId)
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
            })
            ->doesntHave('sprints')
            ->orderBy('id')
            ->get();

        return ProjectTaskData::collect(
            $tasks->map(fn (Task $task) => ProjectTaskData::fromModel(
                $task,
                ProjectTaskData::collect([], DataCollection::class)
            )),
            DataCollection::class
        );
    }

    /**
     * Get all epics for a project.
     */
    public function getEpics(int $projectId): Collection
    {
        return Task::epics()
            ->where('project_id', $projectId)
            ->get(['id', 'title', 'story_points']);
    }

    public function getTaskStatuses(): Collection
    {
        return MsTaskStatus::select('id', 'name', 'severity', 'score')->orderBy('score')->get();
    }

    public function getTaskPriorities(): Collection
    {
        return MsTaskPriority::select('id', 'name', 'severity')->get();
    }

    public function getTaskTypes(): Collection
    {
        return MsTaskType::select('id', 'name', 'severity')->get();
    }

    public function getTags(): Collection
    {
        return Tag::select('id', 'name', 'severity')->get();
    }

    public function getProjectRoles(): Collection
    {
        return MsProjectRole::all(['id', 'name']);
    }

    public function getProjectStatuses(): Collection
    {
        return MsProjectStatus::select('id', 'name', 'severity')->get();
    }

    public function getProjectPriorities(): Collection
    {
        return MsProjectPriority::select('id', 'name', 'severity')->get();
    }

    public function getTaskCategories(): Collection
    {
        return TaskCategory::select('id', 'name', 'icon', 'severity')->orderBy('severity')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Lazy (per-tab) slim loaders — used by ProjectLazyService.
    | Each loads only what a single tab renders; never the whole show payload.
    |--------------------------------------------------------------------------
    */

    /**
     * Find a project for the persistent shell (header + stats), WITHOUT tasks.
     *
     * @throws NotFoundHttpException
     */
    public function findShell(string $encoded): Project
    {
        try {
            $projectId = Sqids::decode($encoded);

            $project = Cache::tags(self::SHELL_CACHE_TAG)->remember($this->shellCacheKey($projectId), now()->addDays(7), function () use ($projectId) {
                return Project::with([
                    'status:id,name,severity',
                    'priority:id,name,severity',
                    'projectMembers' => function ($query) {
                        $query->whereHas('user', fn ($q) => $q->where('is_active', true));
                    },
                    'projectMembers.user:id,name,email',
                    'projectMembers.user.media',
                    'projectMembers.role:id,name,config',
                ])->find($projectId);
            });

            if (! $project) {
                throw new NotFoundHttpException;
            }

            return $project;
        } catch (\Exception $e) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Kanban tab: tasks belonging to the project's currently-active sprint(s),
     * as slim cards. sub_task_recursive is kept (for client-side subtask counts).
     *
     * @return DataCollection<int, TaskCardData>
     */
    public function getActiveSprintTaskCards(int $projectId): DataCollection
    {
        $activeStatusId = MsSprintStatus::where('name', 'Active')->value('id');

        if (! $activeStatusId) {
            return TaskCardData::collect([], DataCollection::class);
        }

        $tasks = Task::query()
            ->where('project_id', $projectId)
            ->whereHas('sprints', fn ($q) => $q->where('sprint_status_id', $activeStatusId))
            ->where(function ($q) {
                $q->whereNull('parent_id')
                    ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
            })
            ->with([
                'status:id,name,severity,score',
                'priority:id,name,severity',
                'type:id,name,severity',
                'users:id,name,email',
                'users.media',
                'subTaskRecursive',
            ])
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->get();

        return TaskCardData::collect(
            $tasks->map(fn (Task $task) => TaskCardData::fromModel($task)),
            DataCollection::class
        );
    }

    /**
     * List tab: full task tree as slim list items (table columns + tree helpers only).
     *
     * @return DataCollection<int, TaskListItemData>
     */
    public function getTaskListTree(int $projectId): DataCollection
    {
        $rows = DB::select($this->recursiveTaskTreeSql(), ['projectId' => $projectId]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Task> $tasks */
        $tasks = Task::hydrate($rows);

        if ($tasks->isEmpty()) {
            return TaskListItemData::collect([], DataCollection::class);
        }

        $tasks->load([
            'status:id,name,severity,score',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name,email',
            'users.media',
        ]);

        return TaskListItemData::treeFromTasks($tasks->toBase());
    }

    /**
     * On-demand edit payload: a single task with the full set of relations the
     * task form pre-fills (type, status, priority, category, users, tags, media).
     */
    public function getTaskForEdit(int $taskId): Task
    {
        return Task::with([
            'status:id,name,severity,score',
            'priority:id,name,severity',
            'type:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name,email',
            'users.media',
            'tags:id,name,severity',
            'media' => fn ($q) => $q->where('collection_name', 'attachments'),
        ])->findOrFail($taskId);
    }

    /**
     * On-demand parent-task picker options for the task form (flat, minimal).
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getTaskParentOptions(int $projectId): SupportCollection
    {
        return Task::query()
            ->where('project_id', $projectId)
            ->with('category:id,name,icon,severity')
            ->orderBy('title')
            ->get(['id', 'parent_id', 'title', 'task_category_id'])
            ->map(fn (Task $task) => [
                'id' => (int) $task->id,
                'parent_id' => $task->parent_id !== null ? (int) $task->parent_id : null,
                'title' => $task->title,
                'category' => $task->category
                    ? ['id' => (int) $task->category->id, 'name' => $task->category->name]
                    : null,
            ]);
    }
}
