<?php

namespace App\Services;

use App\Data\Project\Lazy\BacklogSprintData;
use App\Data\Project\Lazy\BacklogTaskData;
use App\Data\Project\Lazy\ShellMemberData;
use App\Data\Project\ProjectPriorityData;
use App\Data\Project\ProjectRoleData;
use App\Data\Project\ProjectStatusData;
use App\Data\ProjectRole\ConfigData;
use App\Data\Task\ProjectTaskData;
use App\Data\Task\TagData;
use App\Data\Task\TaskCategoryData;
use App\Data\Task\TaskPriorityData;
use App\Data\Task\TaskStatusData;
use App\Data\Task\TaskTypeData;
use App\Data\UserData;
use App\Facades\Sqids;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Repositories\ProjectRepository;
use App\Repositories\SprintRepository;
use Illuminate\Support\Facades\Auth;
use Inertia\DeferProp;
use Inertia\Inertia;
use Spatie\LaravelData\DataCollection;

/**
 * Builds the slim, per-tab payloads for the lazy project detail (project-lazy/*).
 *
 * Each tab receives the cheap "shell" props (project header + stats + policy)
 * plus ONLY the data that tab renders — never the full project.show payload.
 */
class ProjectLazyService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private SprintRepository $sprintRepository,
    ) {}

    /**
     * Cheap props rendered by the persistent shell on every tab
     * (header avatars, stats cards, permission policy).
     *
     * @return array<string, mixed>
     */
    public function shellData(Project $project): array
    {
        return [
            'project' => $this->projectShell($project),

            'members' => ShellMemberData::collect(
                $project->projectMembers->map(fn (ProjectMember $member) => ShellMemberData::fromModel($member)),
                DataCollection::class
            )->toArray(),

            'statuses' => ProjectStatusData::collect(
                $this->projectRepository->getProjectStatuses(),
                DataCollection::class
            )->toArray(),

            'priorities' => ProjectPriorityData::collect(
                $this->projectRepository->getProjectPriorities(),
                DataCollection::class
            )->toArray(),

            'isMember' => $this->isMember($project),
            'policy' => $this->policy($project),
        ];
    }

    /**
     * Kanban tab: active-sprint task cards + the small option lists the board
     * filters/edits with, plus assignable users and epic options.
     *
     * All of it is deferred (Inertia::defer) so the persistent shell paints
     * immediately and the board payload streams in via a single follow-up
     * request — see project-lazy/Kanban.vue's <Deferred> wrapper.
     *
     * @return array<string, DeferProp>
     */
    public function kanbanData(Project $project): array
    {
        return [
            'activeSprintId' => $this->activeSprintId($project),
            'taskStatuses' => $this->deferred(fn () => $this->taskStatuses()),
            'taskPriorities' => $this->deferred(fn () => $this->taskPriorities()),
            'taskTypes' => $this->deferred(fn () => $this->taskTypes()),
            'taskCategories' => $this->deferred(fn () => $this->taskCategories()),
            'tags' => $this->deferred(fn () => $this->tags()),
            'tasks' => $this->deferred(fn () => $this->projectRepository->getActiveSprintTaskCards($project->id)->toArray()),
            'assignableUsers' => $this->deferred(fn () => $this->assignableUsers($project)),
            'epics' => $this->deferred(fn () => $this->projectRepository->getEpics($project->id)->toArray()),
        ];
    }

    /**
     * The id of the project's currently active sprint, if any. Used so newly
     * created tasks from the Kanban tab can be attached to it directly.
     *
     * Encoded here directly since the "activeSprintId" key doesn't match the
     * `_id` suffix pattern that `Sqids::rec_encode_ids_in_list()` auto-encodes.
     */
    private function activeSprintId(Project $project): ?string
    {
        $id = ProjectSprint::where('project_id', $project->id)
            ->whereHas('status', fn ($q) => $q->where('name', 'Active'))
            ->value('id');

        return $id ? Sqids::encode((int) $id) : null;
    }

    /**
     * List tab: slim task tree + assignable users deferred behind the cheap shell
     * (like the Backlog tab), plus the small master option lists its create/edit
     * form needs bundled eagerly (per the "options bundled with the tab" decision).
     *
     * @return array<string, mixed>
     */
    public function listData(Project $project): array
    {
        return array_merge($this->taskFormOptions(), [
            'tasks' => $this->deferred(fn () => $this->projectRepository->getTaskListTree($project->id)->toArray(), 'list'),
            'assignableUsers' => $this->deferred(fn () => $this->assignableUsers($project), 'list'),
        ]);
    }

    /**
     * Backlog tab: planning/active sprints with their (non-epic) tasks, the backlog
     * tasks, the epic option list and assignable users — all deferred behind the cheap
     * shell — plus the small master option lists the create/edit form needs (bundled
     * eagerly, like the List tab).
     *
     * @return array<string, mixed>
     */
    public function backlogData(Project $project): array
    {
        return array_merge($this->taskFormOptions(), [
            'sprints' => $this->deferred(fn () => $this->backlogSprints($project->id), 'backlog'),
            'backlog' => $this->deferred(fn () => $this->backlogTasks($project->id), 'backlog'),
            'epics' => $this->deferred(fn () => $this->projectRepository->getEpics($project->id)->toArray(), 'backlog'),
            'assignableUsers' => $this->deferred(fn () => $this->assignableUsers($project), 'backlog'),
        ]);
    }

    /**
     * Team tab: members (with role + active flag), roles, and assignable users.
     *
     * @return array<string, mixed>
     */
    public function teamData(Project $project): array
    {
        $memberUserIds = $project->projectMembers
            ->filter(fn ($member) => $member->user !== null)
            ->pluck('user.id')
            ->filter()
            ->values();

        return [
            'members' => $project->projectMembers
                ->filter(fn ($member) => $member->user !== null && $member->role !== null)
                ->map(fn ($member) => [
                    'id' => (int) $member->id,
                    'is_active' => (bool) $member->is_active,
                    'user' => [
                        'id' => (int) $member->user->id,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'avatar_url' => $member->user->avatar_url,
                    ],
                    'role' => [
                        'id' => (int) $member->role->id,
                        'name' => $member->role->name,
                    ],
                ])
                ->values()
                ->toArray(),

            'roles' => ProjectRoleData::collect(
                $this->projectRepository->getProjectRoles(),
                DataCollection::class
            )->toArray(),

            'users' => $this->projectRepository->getAvailableUsers($memberUserIds)
                ->map(fn ($user) => UserData::fromModel($user)->toArray())
                ->toArray(),
        ];
    }

    /**
     * Details tab: project description (the rest comes from the shell payload).
     *
     * @return array<string, mixed>
     */
    public function detailData(Project $project): array
    {
        return [
            'description' => $project->description,
        ];
    }

    /**
     * Timeline (Gantt) tab: the slim task tree (title, dates, progress, hierarchy).
     *
     * @return array<string, mixed>
     */
    public function timelineData(Project $project): array
    {
        return [
            'tasks' => $this->projectRepository->getTaskListTree($project->id)->toArray(),
        ];
    }

    /**
     * Small master option lists shared by any tab that hosts the task form
     * (statuses, priorities, types, categories, tags). All tiny lookup tables.
     *
     * @return array<string, mixed>
     */
    public function taskFormOptions(): array
    {
        return [
            'taskStatuses' => $this->taskStatuses(),
            'taskPriorities' => $this->taskPriorities(),
            'taskTypes' => $this->taskTypes(),
            'taskCategories' => $this->taskCategories(),
            'tags' => $this->tags(),
        ];
    }

    /**
     * Full task payload for the edit form, on-demand.
     *
     * @return array<string, mixed>
     */
    public function taskEditData(Task $task): array
    {
        return ProjectTaskData::fromModel(
            $task,
            ProjectTaskData::collect([], DataCollection::class)
        )->toArray();
    }

    /**
     * Flat parent-task picker options for the form, on-demand.
     *
     * @return array<int, array<string, mixed>>
     */
    public function taskParentOptions(int $projectId): array
    {
        return $this->projectRepository->getTaskParentOptions($projectId)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function projectShell(Project $project): array
    {
        return [
            'id' => (int) $project->id,
            'project_no' => $project->project_no,
            'title' => $project->title,
            'emoji' => $project->emoji,
            'progress' => (float) $project->progress,
            'start_date' => $project->start_date?->format('Y-m-d'),
            'due_date' => $project->due_date?->format('Y-m-d'),
            'status_id' => $project->status_id !== null ? (int) $project->status_id : null,
            'priority_id' => $project->priority_id !== null ? (int) $project->priority_id : null,
            'status' => $project->status
                ? ['id' => (int) $project->status->id, 'name' => $project->status->name, 'severity' => $project->status->severity]
                : null,
            'priority' => $project->priority
                ? ['id' => (int) $project->priority->id, 'name' => $project->priority->name, 'severity' => $project->priority->severity]
                : null,
        ];
    }

    /**
     * Planning/active sprints (no Completed) mapped to the slim board shape.
     *
     * @return array<int, array<string, mixed>>
     */
    private function backlogSprints(int $projectId): array
    {
        return $this->sprintRepository->getActiveSprintsWithTasks($projectId)
            ->map(fn (ProjectSprint $sprint) => BacklogSprintData::fromModel($sprint)->toArray())
            ->values()
            ->all();
    }

    /**
     * Unassigned (root or epic-parented) backlog tasks, slim and without epic rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function backlogTasks(int $projectId): array
    {
        return $this->sprintRepository->getBacklogTasks($projectId)
            ->reject(fn (Task $task) => strtolower((string) $task->category?->name) === 'epic')
            ->map(fn (Task $task) => BacklogTaskData::fromModel($task)->toArray())
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assignableUsers(Project $project): array
    {
        return $project->projectMembers
            ->filter(fn ($member) => $member->user !== null)
            ->map(fn ($member) => UserData::fromModel($member->user)->toArray())
            ->unique('id')
            ->values()
            ->toArray();
    }

    /**
     * Wrap a payload resolver as an Inertia deferred prop. The id-encoding runs
     * inside the closure (not in the controller's renderTab) so encoding still
     * happens when the deferred group is fetched on the follow-up request.
     */
    private function deferred(callable $resolver, string $group = 'kanban'): DeferProp
    {
        return Inertia::defer(fn () => Sqids::rec_encode_ids_in_list($resolver()), $group);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function taskCategories(): array
    {
        return TaskCategoryData::collect(
            $this->projectRepository->getTaskCategories(),
            DataCollection::class
        )->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tags(): array
    {
        return TagData::collect(
            $this->projectRepository->getTags(),
            DataCollection::class
        )->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function taskStatuses(): array
    {
        return TaskStatusData::collect(
            $this->projectRepository->getTaskStatuses(),
            DataCollection::class
        )->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function taskPriorities(): array
    {
        return TaskPriorityData::collect(
            $this->projectRepository->getTaskPriorities(),
            DataCollection::class
        )->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function taskTypes(): array
    {
        return TaskTypeData::collect(
            $this->projectRepository->getTaskTypes(),
            DataCollection::class
        )->toArray();
    }

    private function isMember(Project $project): bool
    {
        return (bool) $project->projectMembers
            ->where('user.id', Auth::id())
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function policy(Project $project): ?array
    {
        if (! Auth::check()) {
            return null;
        }

        if (Auth::user()->is_super_admin) {
            return $this->fullAccessPolicy()->toResponse();
        }

        $config = $project->projectMembers
            ->firstWhere('user.id', Auth::id())
            ?->role?->config;

        return $config ? ConfigData::from($config)->toResponse() : null;
    }

    private function fullAccessPolicy(): ConfigData
    {
        return ConfigData::from([
            'task' => ['create', 'update', 'delete'],
            'sprint' => ['create', 'update', 'delete'],
            'project_member' => ['create', 'update', 'delete'],
            'allow_task_status' => [],
            'allow_update_task_fields' => [],
        ]);
    }
}
