<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Jumlah baris yang ditampilkan tiap panel.
     */
    private const PANEL_LIMIT = 5;

    public function index(): Response
    {
        $userId = Auth::id();

        $data = [
            'attention' => $this->getTasksNeedingAttention($userId),
            'tasks' => $this->getRecentTasks($userId),
            'projects' => $this->getRecentProjects($userId),
            'stats' => [
                'tasks' => $this->getTaskStats($userId),
                'projects' => $this->getProjectStats($userId),
            ],
        ];

        return Inertia::render('Dashboard', [
            ...Sqids::rec_encode_ids_in_list($data),
            // Query terberat di halaman: memuat seluruh proyek user beserta anggotanya
            // hanya untuk dedupe. Ditunda supaya sisa dashboard render lebih dulu.
            'members' => Inertia::defer(fn () => Sqids::rec_encode_ids_in_list($this->getTeamMembers($userId))),
        ]);
    }

    /**
     * Tugas milik user, baik yang dia buat maupun yang ditugaskan kepadanya.
     *
     * whereHas dipakai (bukan join) supaya baris tidak terduplikasi saat satu tugas
     * punya banyak assignee.
     *
     * `created_by` wajib diprefiks tabel: statusBreakdown() menempelkan leftJoin ke
     * ms_task_statuses yang juga punya kolom created_by, dan Postgres menolak yang ambigu.
     */
    private function userTasks(int $userId): Builder
    {
        return Task::where(function (Builder $query) use ($userId) {
            $query->where('tasks.created_by', $userId)
                ->orWhereHas('users', fn (Builder $assignee) => $assignee->where('users.id', $userId));
        });
    }

    private function userProjects(int $userId): Builder
    {
        return Project::whereHas('projectMembers', fn (Builder $member) => $member->where('user_id', $userId));
    }

    /**
     * Tugas yang benar-benar perlu ditindaklanjuti: belum selesai, belum diarsipkan,
     * punya tenggat, dan yang paling telat tampil lebih dulu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTasksNeedingAttention(int $userId): array
    {
        return $this->taskPanel($this->openTasks($userId)->whereNotNull('due_date')->orderBy('due_date'), $userId);
    }

    /**
     * Tugas terbaru milik user, apa pun statusnya, sebagai penyeimbang panel triase.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentTasks(int $userId): array
    {
        return $this->taskPanel($this->userTasks($userId)->where('is_archived', false)->latest('tasks.id'), $userId);
    }

    /**
     * Bentuk baris yang dipakai kedua panel tugas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function taskPanel(Builder $query, int $userId): array
    {
        return $query
            ->with([
                'project:id,title,emoji',
                'status:id,name,severity',
                'priority:id,name,severity',
                'users:id,name',
            ])
            ->limit(self::PANEL_LIMIT)
            ->get()
            ->map(function (Task $task) use ($userId): array {
                $row = $task->toArray();

                // Panel memisahkan tugas yang di-assign ke user dari yang dia buat sendiri.
                // Relasi users hanya dipakai untuk itu dan untuk avatar; pivotnya tidak.
                $row['assignees'] = $task->users
                    ->map(fn (User $assignee) => ['id' => $assignee->id, 'name' => $assignee->name])
                    ->all();
                $row['is_assigned'] = $task->users->contains('id', $userId);
                $row['is_created_by_me'] = $task->created_by === $userId;
                unset($row['users']);

                return $row;
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentProjects(int $userId): array
    {
        return $this->userProjects($userId)
            ->with([
                'status:id,name,severity',
                'priority:id,name,severity',
            ])
            ->latest('id')
            ->limit(self::PANEL_LIMIT)
            ->get()
            ->toArray();
    }

    /**
     * @return array{total: int, progress: int, overdue: int, dueSoon: int, byStatus: array<int, array{name: string, severity: string|null, count: int}>}
     */
    private function getTaskStats(int $userId): array
    {
        $totals = $this->userTasks($userId)
            ->selectRaw('COUNT(*) as total, AVG(progress) as avg_progress')
            ->first();

        return [
            'total' => (int) ($totals->total ?? 0),
            'progress' => (int) round((float) ($totals->avg_progress ?? 0)),
            'overdue' => $this->openTasks($userId)
                ->where('due_date', '<', today())
                ->count(),
            // Panel triase merangkum seluruh populasi, bukan hanya lima baris yang tampil.
            'dueSoon' => $this->openTasks($userId)
                ->whereBetween('due_date', [today(), today()->addWeek()])
                ->count(),
            'byStatus' => $this->statusBreakdown($this->userTasks($userId), 'ms_task_statuses', 'tasks.status_id'),
        ];
    }

    /**
     * Tugas user yang masih berjalan: belum selesai dan belum diarsipkan.
     */
    private function openTasks(int $userId): Builder
    {
        return $this->userTasks($userId)
            ->whereNull('completed_at')
            ->where('is_archived', false);
    }

    /**
     * @return array{total: int, progress: int, byStatus: array<int, array{name: string, severity: string|null, count: int}>}
     */
    private function getProjectStats(int $userId): array
    {
        $totals = $this->userProjects($userId)
            ->selectRaw('COUNT(*) as total, AVG(progress) as avg_progress')
            ->first();

        return [
            'total' => (int) ($totals->total ?? 0),
            'progress' => (int) round((float) ($totals->avg_progress ?? 0)),
            'byStatus' => $this->statusBreakdown($this->userProjects($userId), 'ms_project_statuses', 'projects.status_id'),
        ];
    }

    /**
     * Sebaran status untuk SELURUH populasi, bukan hanya baris yang tampil di panel.
     *
     * leftJoin dipakai dengan sengaja: kolom status_id nullable, dan inner join akan
     * membuang baris tanpa status sehingga jumlah sebaran tidak lagi sama dengan total.
     *
     * @return array<int, array{name: string, severity: string|null, count: int}>
     */
    private function statusBreakdown(Builder $query, string $statusTable, string $foreignKey): array
    {
        return $query
            ->leftJoin($statusTable, "{$statusTable}.id", '=', $foreignKey)
            ->groupBy("{$statusTable}.name", "{$statusTable}.severity")
            ->selectRaw("{$statusTable}.name, {$statusTable}.severity, COUNT(*) as count")
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? 'Tanpa status',
                'severity' => $row->severity,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * Anggota unik dari seluruh proyek yang diikuti user.
     */
    private function getTeamMembers(int $userId): Collection
    {
        return $this->userProjects($userId)
            ->with('projectMembers.user:id,name,email')
            ->get()
            ->flatMap(fn (Project $project) => $project->projectMembers->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();
    }
}
