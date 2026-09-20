<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * Status project yang dianggap sedang berjalan: sudah dimulai, belum selesai.
     *
     * @var array<int, string>
     */
    private const RUNNING_PROJECT_STATUSES = ['In Progress', 'On Hold'];

    private const IN_PROGRESS_STATUS = 'In Progress';

    /**
     * Project yang diikuti user, apa pun perannya di dalamnya.
     */
    public function userProjects(int $userId): Builder
    {
        return Project::whereHas('projectMembers', fn (Builder $member) => $member->where('user_id', $userId));
    }

    /**
     * Task milik user: yang dia buat maupun yang ditugaskan kepadanya.
     *
     * whereHas dipakai, bukan join, supaya baris tidak terduplikasi saat satu task
     * punya banyak assignee. `created_by` diprefiks nama tabel karena users juga
     * punya kolom bernama sama.
     */
    public function userTasks(int $userId): Builder
    {
        return Task::where(function (Builder $query) use ($userId) {
            $query->where('tasks.created_by', $userId)
                ->orWhereHas('users', fn (Builder $assignee) => $assignee->where('users.id', $userId));
        });
    }

    /**
     * Task yang masih berjalan: belum selesai dan belum diarsipkan.
     */
    public function openTasks(int $userId): Builder
    {
        return $this->userTasks($userId)
            ->whereNull('completed_at')
            ->where('is_archived', false);
    }

    /**
     * Id project berjalan yang diikuti user.
     *
     * Dikembalikan sebagai array, bukan angka, karena dipakai dua kali: jumlahnya
     * untuk "N projects running", dan isinya untuk membatasi query statistik task.
     *
     * @return array<int, int>
     */
    public function runningProjectIds(int $userId): array
    {
        return $this->userProjects($userId)
            ->whereHas('status', fn (Builder $status) => $status->whereIn('name', self::RUNNING_PROJECT_STATUSES))
            ->pluck('projects.id')
            ->all();
    }

    /**
     * Task yang tenggatnya jatuh dalam rentang hari ini sampai hari ini + $days.
     */
    public function countTasksDueWithin(int $userId, int $days): int
    {
        return $this->openTasks($userId)
            // Buang baris ini kalau task yang sudah telat ingin ikut dihitung.
            ->where('due_date', '>=', today())
            ->where('due_date', '<=', today()->addDays($days))
            ->count();
    }

    /**
     * Task yang sudah lewat tenggat atau jatuh tempo hari ini.
     */
    public function countTasksNeedingAttention(int $userId): int
    {
        return $this->openTasks($userId)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today())
            ->count();
    }

    public function taskStats(int $userId, int $dueWindowDays, int $completedWindowDays): object
    {
        $today = today();

        return DB::table('tasks')
            ->leftJoin('task_users as assignee', function ($join) use ($userId) {
                $join->on('assignee.task_id', '=', 'tasks.id')
                    ->where('assignee.user_id', $userId);
            })
            ->leftJoin('ms_task_statuses as task_status', 'task_status.id', '=', 'tasks.status_id')
            ->where(function (\Illuminate\Database\Query\Builder $query) use ($userId) {
                $query->where('tasks.created_by', $userId)
                    ->orWhereNotNull('assignee.user_id');
            })
            ->whereNull('tasks.deleted_at')
            ->where('tasks.is_archived', false)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(*) FILTER (WHERE tasks.completed_at IS NOT NULL) AS done')
            ->selectRaw('COUNT(*) FILTER (WHERE tasks.completed_at >= ?) AS done_recently', [$today->copy()->subDays($completedWindowDays)])
            // Kartu "In progress" sengaja memakai status, bukan partisi donut.
            // Kalau nanti ingin diubah jadi "semua yang belum selesai dan belum telat",
            // ganti dua baris di bawah ini dengan kondisi yang sama seperti `active`.
            // Donut tetap tiga segmen — angkanya diambil dari done/active/overdue.
            ->selectRaw('COUNT(*) FILTER (WHERE task_status.name = ?) AS in_progress', [self::IN_PROGRESS_STATUS])
            ->selectRaw('COUNT(*) FILTER (WHERE task_status.name = ? AND assignee.user_id IS NOT NULL) AS in_progress_assigned_to_me', [self::IN_PROGRESS_STATUS])
            ->selectRaw('COUNT(*) FILTER (WHERE tasks.completed_at IS NULL AND tasks.due_date < ?) AS overdue', [$today])
            ->selectRaw('COUNT(*) FILTER (WHERE tasks.completed_at IS NULL AND tasks.due_date >= ? AND tasks.due_date <= ?) AS due_soon', [$today, $today->copy()->addDays($dueWindowDays)])
            ->selectRaw('COUNT(*) FILTER (WHERE tasks.completed_at IS NULL AND (tasks.due_date IS NULL OR tasks.due_date >= ?)) AS active', [$today])
            ->first();
    }

    public function taskActivityByDay(int $userId, int $days): array
    {
        return DB::table('activity_log')
            ->where('causer_type', User::class)
            ->where('causer_id', $userId)
            ->where('subject_type', Task::class)
            ->where('created_at', '>=', today()->subDays($days - 1))
            ->groupByRaw('created_at::date')
            ->orderByRaw('created_at::date')
            ->selectRaw('created_at::date AS date, COUNT(*) AS count')
            ->get()
            ->all();
    }

    public function attentionTasks(int $userId, int $windowDays, int $limit): Collection
    {
        return $this->attentionQuery($userId, $windowDays)
            ->withCount([
                'children as open_subtasks' => fn (Builder $child) => $child
                    ->whereNull('completed_at')
                    ->where('is_archived', false),
            ])
            ->with([
                'project:id,title',
                // Penanggung jawab diambil dari anggota project yang berperan Owner.
                // Tidak dibatasi satu di sini: satu project bisa punya lebih dari satu
                // Owner, dan pemilihannya diserahkan ke service.
                'project.projectMembers' => fn ($member) => $member
                    ->whereHas('role', fn (Builder $role) => $role->where('name', 'Owner'))
                    ->with('user:id,name'),
            ])
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'title', 'due_date', 'project_id']);
    }

    public function countAttentionTasks(int $userId, int $windowDays): int
    {
        return $this->attentionQuery($userId, $windowDays)->count();
    }

    /**
     * Dasar yang dipakai bersama oleh daftar dan penghitungnya, supaya keduanya
     * tidak bisa berselisih kriteria.
     */
    private function attentionQuery(int $userId, int $windowDays): Builder
    {
        return $this->openTasks($userId)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today()->addDays($windowDays));
    }

    public function projectProgress(int $userId, string $tab, int $limit): Collection
    {
        $query = $this->userProjects($userId)
            // Kolom disebut lebih dulu, sebelum selectSub. Kalau dibalik, daftar kolom
            // sudah terisi subquery dan argumen kolom di get() akan diabaikan diam-diam.
            ->select([
                'projects.id',
                'projects.title',
                'projects.emoji',
                'projects.due_date',
                'projects.progress',
                'projects.status_id',
            ])
            ->with('status:id,name,severity')
            ->selectSub($this->taskCountSub(), 'total_tasks')
            ->selectSub($this->taskCountSub(doneOnly: true), 'done_tasks');

        if ($tab === 'active') {
            $query->whereHas('status', fn (Builder $status) => $status->whereIn('name', self::RUNNING_PROJECT_STATUSES));
        }

        if ($tab === 'at-risk') {
            $query->whereExists($this->overdueTaskSub());
        }

        return $query
            // Paling mendesak dulu; project tanpa tenggat ditaruh paling belakang.
            ->orderByRaw('due_date ASC NULLS LAST')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function latestProjects(int $userId, int $limit): Collection
    {
        return $this->userProjects($userId)
            ->with('status:id,name,severity')
            ->withCount('projectMembers as members_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'title', 'description', 'created_at', 'status_id']);
    }

    private function taskCountSub(bool $doneOnly = false): \Illuminate\Database\Query\Builder
    {
        $sub = DB::table('tasks')
            ->whereColumn('tasks.project_id', 'projects.id')
            ->whereNull('tasks.deleted_at')
            ->where('tasks.is_archived', false);

        if ($doneOnly) {
            $sub->whereNotNull('tasks.completed_at');
        }

        return $sub->selectRaw('COUNT(*)');
    }

    /**
     * Penanda "at risk": project punya minimal satu task yang lewat tenggat
     * dan belum selesai.
     */
    private function overdueTaskSub(): \Illuminate\Database\Query\Builder
    {
        return DB::table('tasks')
            ->whereColumn('tasks.project_id', 'projects.id')
            ->whereNull('tasks.deleted_at')
            ->whereNull('tasks.completed_at')
            ->where('tasks.is_archived', false)
            ->whereNotNull('tasks.due_date')
            ->where('tasks.due_date', '<', today())
            ->selectRaw('1');
    }
}
