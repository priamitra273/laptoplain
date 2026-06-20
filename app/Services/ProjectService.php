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
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Repositories\ProjectRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\DataCollection;

class ProjectService
{
    public function __construct(private ProjectRepository $projectRepository) {}

    public function findByEncodedId(string $encodedId): Project
    {
        $id = Sqids::decodeOrFail($encodedId);

        return $this->projectRepository->findById($id);
    }

    public function getIndexData(): array
    {
        $user = Auth::user();

        return [
            'projects' => ProjectData::collect(
                $this->projectRepository->getAllVisibleForUser($user),
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
