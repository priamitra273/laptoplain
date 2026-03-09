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
            'tasks.status',
            'tasks.priority',
            'tasks.category',
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

        return response()->json(
            Sqids::rec_encode_ids_in_list([
                'sprints' => $sprints->toArray(),
                'backlog' => $backlog->toArray(),
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
            // ✅ FIX: duration adalah string seperti "2 weeks", bukan integer
            'duration'   => 'nullable|string|max:50',
        ]);

        $lastOrder = ProjectSprint::where('project_id', $projectId)->max('order') ?? 0;

        $sprint = ProjectSprint::create([
            ...$validated,
            'project_id'       => $projectId,
            'sprint_status_id' => MsSprintStatus::planning()->id,
            'order'            => $lastOrder + 1,
            'created_by'       => Auth::id(),
            'updated_by'       => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'sprint'  => Sqids::rec_encode_ids_in_list($sprint->load('status')->toArray()),
        ]);
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
            // ✅ FIX: duration adalah string seperti "2 weeks", bukan integer
            'duration'   => 'nullable|string|max:50',
        ]);

        $sprint->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'sprint'  => Sqids::rec_encode_ids_in_list($sprint->fresh('status')->toArray()),
        ]);
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}
    public function destroy(string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        if ($sprint->status?->name === 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Sprint yang sedang berjalan tidak bisa dihapus. Selesaikan sprint terlebih dahulu.',
            ], 422);
        }

        // Kembalikan semua task ke backlog (detach dari pivot)
        $sprint->tasks()->detach();
        $sprint->delete();

        return response()->json(['success' => true]);
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/start
    public function start(string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $hasActive = ProjectSprint::where('project_id', $projectId)
            ->whereHas('status', fn($q) => $q->where('name', 'Active'))
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => 'Masih ada sprint yang sedang berjalan. Selesaikan dulu sebelum memulai sprint baru.',
            ], 422);
        }

        $sprint->update([
            'sprint_status_id' => MsSprintStatus::active()->id,
            'start_date'       => $sprint->start_date ?? now()->toDateString(),
            'updated_by'       => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'sprint'  => Sqids::rec_encode_ids_in_list($sprint->fresh('status')->toArray()),
        ]);
    }

    // PATCH /project/{projectEncoded}/sprints/{sprintEncoded}/complete
    public function complete(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $sprint    = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        $validated = $request->validate([
            'retrospective'      => 'nullable|string',
            // ✅ FIX: frontend kirim encoded Sqids string (atau null untuk backlog)
            'move_incomplete_to' => 'nullable|string',
        ]);

        // Decode dan pindahkan task yang belum complete
        if (!empty($validated['move_incomplete_to'])) {
            $targetSprintId = Sqids::decode($validated['move_incomplete_to']);

            $incompleteTasks = $sprint->tasks()
                ->whereHas('status', fn($q) => $q->whereNotIn('name', ['Completed', 'Finished', 'Done']))
                ->pluck('tasks.id');

            if ($incompleteTasks->isNotEmpty()) {
                $targetSprint = ProjectSprint::where('project_id', $projectId)
                    ->findOrFail($targetSprintId);
                $targetSprint->tasks()->syncWithoutDetaching($incompleteTasks);
                $sprint->tasks()->detach($incompleteTasks);
            }
        }
        // Jika move_incomplete_to null → task otomatis jadi backlog karena tetap
        // di pivot tapi sprint sudah Completed (atau bisa detach semua incomplete)

        $sprint->update([
            'sprint_status_id' => MsSprintStatus::completed()->id,
            'end_date'         => $sprint->end_date ?? now()->toDateString(),
            'retrospective'    => $validated['retrospective'] ?? null,
            'updated_by'       => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'sprint'  => Sqids::rec_encode_ids_in_list($sprint->fresh('status')->toArray()),
        ]);
    }

    // POST /project/{projectEncoded}/sprints/{sprintEncoded}/tasks
    public function assignTask(Request $request, string $projectEncoded, string $sprintEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);

        $validated = $request->validate([
            'task_ids'   => 'required|array',
            // ✅ FIX: task_ids dikirim sebagai encoded string dari frontend
            'task_ids.*' => 'string',
        ]);

        $sprint = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);

        // Decode semua task IDs
        $rawTaskIds = collect($validated['task_ids'])
            ->map(fn($encoded) => Sqids::decode($encoded))
            ->filter()
            ->values()
            ->toArray();

        if (empty($rawTaskIds)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada task yang valid.'], 422);
        }

        // Pastikan task milik project ini
        $validTaskIds = Task::whereIn('id', $rawTaskIds)
            ->where('project_id', $projectId)
            ->pluck('id')
            ->toArray();

        // Validasi: Epic tidak boleh masuk sprint
        $hasEpic = Task::whereIn('id', $validTaskIds)
            ->whereHas('category', fn($q) => $q->where('name', 'Epic'))
            ->exists();

        if ($hasEpic) {
            return response()->json([
                'success' => false,
                'message' => 'Epic tidak bisa langsung dimasukkan ke sprint. Gunakan Story atau Issue.',
            ], 422);
        }

        $sprint->tasks()->syncWithoutDetaching($validTaskIds);

        return response()->json(['success' => true, 'message' => 'Task berhasil ditambahkan ke sprint.']);
    }

    // DELETE /project/{projectEncoded}/sprints/{sprintEncoded}/tasks/{taskEncoded}
    public function removeTask(string $projectEncoded, string $sprintEncoded, string $taskEncoded)
    {
        $projectId = Sqids::decode($projectEncoded);
        $sprintId  = Sqids::decode($sprintEncoded);
        $taskId    = Sqids::decode($taskEncoded);

        $sprint = ProjectSprint::where('project_id', $projectId)->findOrFail($sprintId);
        $sprint->tasks()->detach($taskId);

        return response()->json(['success' => true, 'message' => 'Task dipindahkan ke backlog.']);
    }
}
