<?php

namespace App\Services;

use App\Data\Project\ProjectData;
use App\Data\Project\ProjectMemberData;
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
use App\Models\MsProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class ProjectService
{
    public function __construct(private ProjectRepository $projectRepository) {}

    /**
     * Create a project and attach the current user as its Owner member, returning the project.
     *
     * Shared by ProjectController::store and the create-project MCP tool. Authorization is the
     * caller's responsibility (route middleware for the controller, the tool's own gate for MCP).
     *
     * @param  array<string, mixed>  $attributes  Validated project attributes (status_id/priority_id as integers).
     */
    public function createProject(array $attributes): Project
    {
        return DB::transaction(function () use ($attributes) {
            $userId = Auth::id();

            $project = Project::create($attributes);

            $project->update([
                'progress' => $project->calculateProgress(),
            ]);

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $userId,
                'project_role_id' => MsProjectRole::where('name', 'Owner')->value('id'),
                'owned_id' => $userId,
                'is_active' => true,
            ]);

            return $project;
        });
    }

    /**
     * Update a project with the given attributes and recalculate its progress, returning it.
     *
     * Shared by ProjectController::update and the update-project MCP tool. Resolving the project
     * and authorization are the caller's responsibility.
     *
     * @param  array<string, mixed>  $attributes  Validated project attributes (status_id/priority_id as integers).
     */
    public function updateProject(Project $project, array $attributes): Project
    {
        return DB::transaction(function () use ($project, $attributes) {
            $project->update($attributes);

            $project->update([
                'progress' => $project->calculateProgress(),
            ]);

            return $project;
        });
    }

    public function findByEncodedId(string $encodedId): Project
    {
        $id = Sqids::decodeOrFail($encodedId);

        return $this->projectRepository->findById($id);
    }

    public function getIndexData(\App\Data\Project\ProjectFiltersData $filters): array
    {
        $user = Auth::user();

        $query = $this->projectRepository->getVisibleForUserQuery($user);
        $this->projectRepository->applyFilters($query, $filters);

        // Dihitung dari query yang sama (sudah kena filter) tapi sebelum paginate,
        // supaya angka di chip status tetap akurat lintas halaman.
        $statusCounts = (clone $query)
            ->without(['status', 'priority', 'owner'])
            ->selectRaw('status_id, count(*) as aggregate')
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id')
            ->mapWithKeys(fn ($count, $statusId) => [Sqids::encode((int) $statusId) => (int) $count])
            ->toArray();

        $this->projectRepository->applySort($query, $filters->sort, $filters->direction);

        $projects = $query->paginate($filters->per_page, ['*'], 'page', $filters->page)
            ->withQueryString()
            ->through(fn ($project) => Sqids::rec_encode_ids_in_list(ProjectData::fromModel($project)->toArray()));

        return [
            'projects' => $projects,
            'statusCounts' => $statusCounts,

            'statuses' => ProjectStatusData::collect(
                $this->projectRepository->getProjectStatuses(),
                DataCollection::class
            )->toArray(),

            'priorities' => ProjectPriorityData::collect(
                $this->projectRepository->getProjectPriorities(),
                DataCollection::class
            )->toArray(),

            'filters' => $filters,
        ];
    }

    public function getShowData(string $encoded): array
    {
        $project = $this->projectRepository->findWithRelationsForShow($encoded);

        $progress = $project->calculateProgress();

        if (abs((float) $project->progress - $progress) > 0.001) {
            $project->update(['progress' => $progress]);
        }

        $project->unsetRelation('tasks');

        $projectId = $project->id;

        // Member user IDs used to determine who is "available" (non-member)
        $memberUserIds = $project->projectMembers
            ->filter(fn ($member) => $member->user !== null)
            ->pluck('user.id')
            ->filter()
            ->values();

        return [
            'project' => $project->toArray(),

            // DTO layer: ProjectMemberData::fromModel() handles member formatting
            'members' => $project->projectMembers
                ->filter(fn ($member) => $member->user !== null && $member->role !== null)
                ->map(fn ($member) => ProjectMemberData::fromModel($member)->toArray())
                ->values()
                ->toArray(),

            'roles' => ProjectRoleData::collect(
                $this->projectRepository->getProjectRoles(),
                DataCollection::class
            )->toArray(),

            // DTO layer: UserData::fromModel() resolves avatar_url from eager-loaded media
            'users' => $this->projectRepository->getAvailableUsers($memberUserIds)
                ->map(fn ($user) => UserData::fromModel($user)->toArray())
                ->toArray(),

            // Full recursive task tree, serialized as ProjectTaskData
            // (computed is_overdue/completed_at + avatar_url resolved within the DTO).
            'tasks' => $this->projectRepository->getTaskTree($projectId)->toArray(),

            'taskStatuses' => TaskStatusData::collect(
                $this->projectRepository->getTaskStatuses(),
                DataCollection::class
            )->toArray(),

            'taskPriorities' => TaskPriorityData::collect(
                $this->projectRepository->getTaskPriorities(),
                DataCollection::class
            )->toArray(),

            'taskTypes' => TaskTypeData::collect(
                $this->projectRepository->getTaskTypes(),
                DataCollection::class
            )->toArray(),

            'tags' => TagData::collect(
                $this->projectRepository->getTags(),
                DataCollection::class
            )->toArray(),

            // DTO layer: UserData::fromModel() for assignable users (project members)
            'assignableUsers' => $project->projectMembers
                ->filter(fn ($member) => $member->user !== null)
                ->map(fn ($member) => UserData::fromModel($member->user)->toArray())
                ->unique('id')
                ->values()
                ->toArray(),

            'statuses' => ProjectStatusData::collect(
                $this->projectRepository->getProjectStatuses(),
                DataCollection::class
            )->toArray(),

            'priorities' => ProjectPriorityData::collect(
                $this->projectRepository->getProjectPriorities(),
                DataCollection::class
            )->toArray(),

            // Sprint/backlog: avatar_url is resolved automatically via User::$appends
            // since users.media is eager-loaded in the repository — no manual formatting needed
            'sprints' => $this->formatSprints($this->projectRepository->getActiveSprints($projectId)),
            'backlog' => $this->projectRepository->getBacklogTasks($projectId)->toArray(),

            'taskCategories' => TaskCategoryData::collect(
                $this->projectRepository->getTaskCategories(),
                DataCollection::class
            )->toArray(),

            'epics' => fn () => Sqids::rec_encode_ids_in_list($this->getEpicTasks($projectId)),

            'isMember' => $this->isAuthUserMemberOfProject($project),
            'policy' => $this->getAuthUserPolicy($project)->toResponse(),
        ];
    }

    /**
     * Map each active sprint to an array, with its tasks as ProjectTaskData.
     *
     * @param  Collection<int, ProjectSprint>  $sprints
     * @return array<int, array<string, mixed>>
     */
    private function formatSprints(Collection $sprints): array
    {
        return $sprints->map(function (ProjectSprint $sprint) {
            $data = $sprint->makeHidden('tasks')->toArray();
            $data['tasks'] = $sprint->tasks
                ->map(fn (Task $task) => ProjectTaskData::fromModel(
                    $task,
                    ProjectTaskData::collect([], DataCollection::class)
                )->toArray())
                ->values()
                ->all();

            return $data;
        })->all();
    }

    protected function isAuthUserMemberOfProject(Project $project): bool
    {
        return (bool) $project->projectMembers
            ->where('user.id', Auth::id())
            ->first();
    }

    /**
     * Returns a full access policy project.
     */
    protected function fullAccessPolicy(): ConfigData
    {
        return ConfigData::from([
            'task' => ['create', 'update', 'delete'],
            'sprint' => ['create', 'update', 'delete'],
            'project_member' => ['create', 'update', 'delete'],
            'allow_task_status' => [],
            'allow_update_task_fields' => [],
        ]);
    }

    /**
     * Returns a full access policy project.
     * If the user is authenticated and is a super admin, returns a full access policy.
     * Otherwise, returns a policy for the user's project membership.
     */
    protected function getAuthUserPolicy(Project $project): ?ConfigData
    {
        if (! Auth::check()) {
            return null;
        }

        if (Auth::user()->is_super_admin) {
            return $this->fullAccessPolicy();
        }

        $config = $project->projectMembers
            ->firstWhere('user.id', Auth::id())
            ?->role?->config;

        return $config ? ConfigData::from($config) : null;
    }

    protected function getEpicTasks(int $projectId): array
    {
        return $this->projectRepository->getEpics($projectId)->toArray();
    }
}
