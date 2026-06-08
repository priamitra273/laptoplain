<?php

namespace App\Services;

use App\Models\MsSprintStatus;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Task;
use App\Models\User;
use App\Repositories\SprintRepository;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SprintService
{
    public function __construct(private SprintRepository $repository) {}

    public function getIndexData(int $projectId, ?User $user): array
    {
        if (! $user) {
            abort(403);
        }

        Project::visibleFor($user)->findOrFail($projectId);

        return [
            'sprints' => $this->repository->getActiveSprintsWithTasks($projectId)->toArray(),
            'backlog' => $this->repository->getBacklogTasks($projectId)->toArray(),
            'epics' => $this->repository->getEpics($projectId)->toArray(),
        ];
    }

    public function findByProject(int $sprintId, int $projectId): ProjectSprint
    {
        return $this->repository->findByProject($sprintId, $projectId);
    }

    public function store(int $projectId, array $validated): ProjectSprint
    {
        $lastOrder = $this->repository->getLastOrder($projectId);
        $validated['name'] ??= 'Sprint '.($lastOrder + 1);

        return $this->repository->create([
            ...$validated,
            'project_id' => $projectId,
            'sprint_status_id' => MsSprintStatus::planning()->id,
            'order' => $lastOrder + 1,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function update(ProjectSprint $sprint, array $validated): ProjectSprint
    {
        $sprint->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return $sprint;
    }

    public function destroy(ProjectSprint $sprint): void
    {
        if ($sprint->status?->name === 'Active') {
            throw new HttpException(422, 'Sprint yang sedang berjalan tidak bisa dihapus. Selesaikan sprint terlebih dahulu.');
        }

        $sprint->tasks()->detach();
        $sprint->delete();
    }

    public function start(ProjectSprint $sprint, array $validated): ProjectSprint
    {
        $sprint->update([
            ...$validated,
            'sprint_status_id' => MsSprintStatus::active()->id,
            'start_date' => $validated['start_date'] ?? $sprint->start_date ?? now()->toDateString(),
            'updated_by' => Auth::id(),
        ]);

        return $sprint;
    }

    public function complete(ProjectSprint $sprint, array $validated): void
    {
        $moveIncompleteTo = $validated['move_incomplete_to'] ?? [];
        $existingSprint = $moveIncompleteTo['existing_sprint'] ?? null;
        $other = $moveIncompleteTo['other'] ?? null;

        if (! empty($existingSprint)) {
            $this->moveIncompleteToExistingSprint($sprint, (int) $existingSprint);
        } elseif ($other === 'backlog') {
            $this->moveIncompleteToBacklog($sprint);
        } elseif ($other === 'new_sprint') {
            $this->moveIncompleteToNewSprint($sprint);
        }

        $sprint->update([
            'sprint_status_id' => MsSprintStatus::completed()->id,
            'end_date' => $sprint->end_date ?? now()->toDateString(),
            'retrospective' => $validated['retrospective'] ?? null,
            'updated_by' => Auth::id(),
        ]);
    }

    public function assignTasks(ProjectSprint $sprint, array $taskIds, int $projectId): void
    {
        $validTaskIds = Task::whereIn('id', $taskIds)
            ->where('project_id', $projectId)
            ->pluck('id')
            ->toArray();

        $hasEpic = Task::whereIn('id', $validTaskIds)
            ->whereHas('category', fn ($q) => $q->where('name', 'Epic'))
            ->exists();

        if ($hasEpic) {
            throw new HttpException(422, 'Epic tidak bisa langsung dimasukkan ke sprint. Gunakan Story atau Issue.');
        }

        $sprint->tasks()->syncWithoutDetaching($validTaskIds);
    }

    public function removeTask(ProjectSprint $sprint, int $taskId): void
    {
        $sprint->tasks()->detach($taskId);
    }

    private function moveIncompleteToExistingSprint(ProjectSprint $sprint, int $targetSprintId): void
    {
        $incompleteTasks = $this->repository->getIncompleteTaskIds($sprint);

        if ($incompleteTasks->isEmpty()) {
            return;
        }

        $targetSprint = ProjectSprint::where('project_id', $sprint->project_id)->findOrFail($targetSprintId);
        $targetSprint->tasks()->syncWithoutDetaching($incompleteTasks);
        $sprint->tasks()->detach($incompleteTasks);

        activity('move_incomplete_to_other_sprint')
            ->performedOn($sprint)
            ->withProperties([
                'target_sprint_id' => $targetSprintId,
                'detached' => $incompleteTasks,
            ])
            ->log('Move incomplete tasks to other sprint');
    }

    private function moveIncompleteToBacklog(ProjectSprint $sprint): void
    {
        $incompleteTasks = $this->repository->getIncompleteTaskIds($sprint);

        if ($incompleteTasks->isEmpty()) {
            return;
        }

        $sprint->tasks()->detach($incompleteTasks);

        activity('move_incomplete_to_backlog')
            ->performedOn($sprint)
            ->withProperties(['detached' => $incompleteTasks])
            ->log('Move incomplete tasks to backlog');
    }

    private function moveIncompleteToNewSprint(ProjectSprint $sprint): void
    {
        $incompleteTasks = $this->repository->getIncompleteTaskIds($sprint);

        if ($incompleteTasks->isEmpty()) {
            return;
        }

        $lastOrder = $this->repository->getLastOrder($sprint->project_id);
        $sprintCount = ProjectSprint::where('project_id', $sprint->project_id)->count();

        $newSprint = $this->repository->create([
            'name' => 'Sprint '.($sprintCount + 1),
            'goal' => null,
            'start_date' => null,
            'end_date' => null,
            'duration' => null,
            'project_id' => $sprint->project_id,
            'sprint_status_id' => MsSprintStatus::planning()->id,
            'order' => $lastOrder + 1,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $newSprint->tasks()->syncWithoutDetaching($incompleteTasks);
        $sprint->tasks()->detach($incompleteTasks);

        activity('move_incomplete_to_new_sprint')
            ->performedOn($sprint)
            ->withProperties([
                'target_sprint_id' => $newSprint->id,
                'detached' => $incompleteTasks,
            ])
            ->log('Move incomplete tasks to new sprint');
    }
}
