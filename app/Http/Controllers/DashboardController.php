<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
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
        return $this->userTasks($userId)
            ->with([
                'project:id,title,emoji',
                'status:id,name,severity',
                'priority:id,name,severity',
            ])
            ->whereNull('completed_at')
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(self::PANEL_LIMIT)
            ->get()
            ->toArray();
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
     * @return array{total: int, progress: int, overdue: int, byStatus: array<int, array{name: string, severity: string|null, count: int}>}
     */
    private function getTaskStats(int $userId): array
    {
        $totals = $this->userTasks($userId)
            ->selectRaw('COUNT(*) as total, AVG(progress) as avg_progress')
            ->first();

        return [
            'total' => (int) ($totals->total ?? 0),
            'progress' => (int) round((float) ($totals->avg_progress ?? 0)),
            'overdue' => $this->userTasks($userId)
                ->whereNull('completed_at')
                ->where('is_archived', false)
                ->where('due_date', '<', today())
                ->count(),
            'byStatus' => $this->statusBreakdown($this->userTasks($userId), 'ms_task_statuses', 'tasks.status_id'),
        ];
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
