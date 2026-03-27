<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\MsSprintStatus;
use App\Models\Project;
use App\Models\ProjectSprint;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SprintController extends Controller
{
    // GET /project/{projectEncoded}/sprints
    public function index(string $projectEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $project   = Project::findOrFail($projectId);

        if (Auth::user()->cannot('view', $project)) abort(403);

        $sprints = ProjectSprint::with([
            'status',
            'tasks.status:id,name,severity',
            'tasks.priority:id,name,severity',
            'tasks.category:id,name,icon,severity',
            'tasks.users:id,name',
            'tasks.users.media',
        ])
            ->where('project_id', $projectId)
            ->orderBy('order')
            ->get();

        $backlog = Task::with([
            'status:id,name,severity',
            'priority:id,name,severity',
            'category:id,name,icon,severity',
            'users:id,name',
            'users.media',
        ])
            ->where('project_id', $projectId)
            ->backlog()
            ->orderBy('id')
            ->get();

        // Epics: task dengan category 'Epic' di project ini (tidak masuk sprint)
        $epics = Task::with(['status:id,name,severity'])
            ->where('project_id', $projectId)
            ->epics()
            ->get(['id', 'title', 'task_category_id', 'status_id', 'story_points']);

        return response()->json(
            Sqids::rec_encode_ids_in_list([
                'sprints' => $sprints->toArray(),
                'backlog' => $backlog->toArray(),
                'epics'   => $epics->toArray(),
            ])
        );
    }


    // POST /project/{projectEncoded}/sprints
    public function store(Request $request, string $projectEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'duration'   => 'nullable|in:1 week,2 weeks,3 weeks,4 weeks,Custom',
        ]);

        $lastOrder = ProjectSprint::where('project_id', $projectId)->max('order') ?? 0;

        ProjectSprint::create([
            ...$validated,
            'project_id'       => $projectId,
            'sprint_status_id' => MsSprintStatus::planning()->id,
            'order'            => $lastOrder + 1,
            'created_by'       => Auth::id(),
            'updated_by'       => Auth::id(),
        ]);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint created successfully');
    }

    // PUT /project/{projectEncoded}/sprints/{sprintEncoded}
    public function update(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'duration'   => 'nullable|in:1 week,2 weeks,3 weeks,4 weeks,Custom',
        ]);

        $sprint->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint updated successfully');
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}
    public function destroy(string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        if ($sprint->status?->name === 'Active') {
            return back()->with('error', 'Sprint yang sedang berjalan tidak bisa dihapus. Selesaikan sprint terlebih dahulu.');
        }

        $sprint->tasks()->detach();
        $sprint->delete();

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Sprint deleted successfully');
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/start
    public function start(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $hasActive = ProjectSprint::where('project_id', $projectId)
            ->whereHas('status', fn($q) => $q->where('name', 'Active'))
            ->exists();

        if ($hasActive) {
            return back()->with('error', 'Masih ada sprint yang sedang berjalan. Selesaikan dulu sebelum memulai sprint baru.');
        }

        $validated = $request->validate([
            'goal'       => 'nullable|string',
            'duration'   => 'nullable|in:1 week,2 weeks,3 weeks,4 weeks,Custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $sprint->update([
            ...$validated,
            'sprint_status_id' => MsSprintStatus::active()->id,
            'start_date'       => $validated['start_date'] ?? $sprint->start_date ?? now()->toDateString(),
            'updated_by'       => Auth::id(),
        ]);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', "Sprint \"{$sprint->name}\" started");
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/complete
    public function complete(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $validated = $request->validate([
            'retrospective'      => 'nullable|string',
            'move_incomplete_to' => 'nullable|string',
        ]);

        if (!empty($validated['move_incomplete_to'])) {
            $targetSprintId = Sqids::decode($validated['move_incomplete_to']);

            $incompleteTasks = $sprint->tasks()
                ->whereHas('status', fn($q) => $q->whereNotIn('name', ['Completed', 'Finished', 'Done']))
                ->pluck('tasks.id');

            if ($incompleteTasks->isNotEmpty()) {
                $targetSprint = ProjectSprint::where('project_id', $projectId)->findOrFail($targetSprintId);
                $targetSprint->tasks()->syncWithoutDetaching($incompleteTasks);
                $sprint->tasks()->detach($incompleteTasks);
            }
        }

        $sprint->update([
            'sprint_status_id' => MsSprintStatus::completed()->id,
            'end_date'         => $sprint->end_date ?? now()->toDateString(),
            'retrospective'    => $validated['retrospective'] ?? null,
            'updated_by'       => Auth::id(),
        ]);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', "Sprint \"{$sprint->name}\" completed");
    }

    // POST /project/{projectEncoded}/sprints/{sprintEncoded}/tasks
    public function assignTask(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);

        $validated = $request->validate([
            'task_ids'   => 'required|array',
            'task_ids.*' => 'string',
        ]);

        $sprint = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $rawTaskIds = collect($validated['task_ids'])
            ->map(fn($encoded) => Sqids::decode($encoded))
            ->filter()
            ->values()
            ->toArray();

        if (empty($rawTaskIds)) {
            return back()->with('error', 'Tidak ada task yang valid.');
        }

        $validTaskIds = Task::whereIn('id', $rawTaskIds)
            ->where('project_id', $projectId)
            ->pluck('id')
            ->toArray();

        $hasEpic = Task::whereIn('id', $validTaskIds)
            ->whereHas('category', fn($q) => $q->where('name', 'Epic'))
            ->exists();

        if ($hasEpic) {
            return back()->with('error', 'Epic tidak bisa langsung dimasukkan ke sprint. Gunakan Story atau Issue.');
        }

        $sprint->tasks()->syncWithoutDetaching($validTaskIds);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Task berhasil ditambahkan ke sprint.');
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}/tasks/{taskEncoded}
    public function removeTask(string $projectEncoded, string $sprintEncoded, string $taskEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $taskId    = Sqids::decode($taskEncoded);

        $sprint = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);
        $sprint->tasks()->detach($taskId);

        return to_route('project.show', ['encoded' => $projectEncoded])
            ->with('success', 'Task dipindahkan ke backlog.');
    }
}
