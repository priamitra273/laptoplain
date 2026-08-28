<?php

namespace App\Repositories;

use App\Data\Workload\WorkloadFiltersData;
use App\Enums\WorkloadStatus;
use App\Facades\Sqids;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkloadRepository
{
    /**
     * Banyaknya nama yang dikirim untuk tiap tumpukan avatar di kartu sorotan.
     */
    private const PREVIEW_LIMIT = 5;

    public function getWorkloadBaseQuery(): Builder
    {
        $workloadSub = DB::table('users as u')
            ->leftJoin('task_users as tu', 'tu.user_id', '=', 'u.id')
            ->leftJoin('tasks as t', function ($join) {
                $join->on('t.id', '=', 'tu.task_id')
                    ->whereNull('t.deleted_at')
                    ->where('t.is_archived', false);
            })
            ->where('u.is_active', true)
            ->whereNull('u.deleted_at')
            ->groupBy('u.id')
            ->select([
                'u.id as user_id',
                DB::raw('COALESCE(ROUND(AVG(100 - t.progress)), 0) AS remaining_work_percent'),
                DB::raw('COUNT(t.id) AS total_tasks'),
                DB::raw("
                    CASE
                        WHEN COUNT(t.id) = 0 THEN 'Free'
                        WHEN ROUND(AVG(100 - t.progress)) = 0 THEN 'Free'
                        WHEN ROUND(AVG(100 - t.progress)) BETWEEN 1 AND 20 THEN 'Almost Done'
                        WHEN ROUND(AVG(100 - t.progress)) BETWEEN 21 AND 50 THEN 'Ongoing'
                        ELSE 'Overloaded'
                    END AS workload_status
                "),
            ]);

        return User::query()
            ->select([
                'users.*',
                'w.remaining_work_percent',
                'w.workload_status',
                'w.total_tasks',
            ])
            ->joinSub($workloadSub, 'w', fn ($join) => $join->on('w.user_id', '=', 'users.id'));
    }

    public function applyFilters(Builder $query, WorkloadFiltersData $filters): void
    {
        if (! empty($filters->names)) {
            $userIds = collect($filters->names)
                ->map(fn ($encoded) => Sqids::decode($encoded))
                ->filter()
                ->toArray();

            $query->whereIn('users.id', $userIds);
        }

        if (! empty($filters->workload_statuses)) {
            $statusStrings = collect($filters->workload_statuses)
                ->map(fn ($id) => WorkloadStatus::fromId((int) $id)?->value)
                ->filter()
                ->toArray();

            if (! empty($statusStrings)) {
                $query->whereIn('w.workload_status', $statusStrings);
            }
        }

        if (! empty($filters->search)) {
            $query->where('users.name', 'ILIKE', "%{$filters->search}%");
        }
    }

    public function applySort(Builder $query, WorkloadFiltersData $filters): void
    {
        $column = WorkloadFiltersData::SORTABLE_COLUMNS[$filters->sort];

        // Nama sebagai pemecah seri: tanpa ini, dua user dengan angka sama bisa
        // bertukar posisi antar halaman dan barisnya terlihat hilang/dobel.
        $query->orderBy($column, $filters->direction)
            ->orderBy('users.name');
    }

    /**
     * Ringkasan seluruh populasi yang lolos filter, bukan hanya halaman yang tampil.
     *
     * Baris diambil sekali lalu diolah di memori; kolom tambahannya menumpang query
     * yang sama, jadi tidak ada pass baru dibanding versi sebelumnya.
     */
    public function getSummary(Builder $query): array
    {
        $allForSummary = (clone $query)->get([
            'users.id',
            'users.name',
            'w.workload_status',
            'w.remaining_work_percent',
        ]);

        return [
            'total_users' => $allForSummary->count(),
            'free' => $allForSummary->where('workload_status', WorkloadStatus::FREE->value)->count(),
            'light' => $allForSummary->where('workload_status', WorkloadStatus::ALMOST_DONE->value)->count(),
            'moderate' => $allForSummary->where('workload_status', WorkloadStatus::ONGOING->value)->count(),
            'busy' => $allForSummary->where('workload_status', WorkloadStatus::OVERLOADED->value)->count(),
            'users_preview' => $this->preview($allForSummary),
            'overloaded_preview' => $this->preview(
                $allForSummary->where('workload_status', WorkloadStatus::OVERLOADED->value)
            ),
        ];
    }

    /**
     * Beberapa nama untuk tumpukan avatar. Yang bebannya paling berat tampil lebih dulu,
     * dan avatar_url sengaja tidak diikutkan supaya tidak menarik relasi media.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, array{id: string, name: string}>
     */
    private function preview(Collection $users): array
    {
        return $users
            ->sortByDesc('remaining_work_percent')
            ->take(self::PREVIEW_LIMIT)
            ->map(fn ($user) => ['id' => Sqids::encode($user->id), 'name' => $user->name])
            ->values()
            ->all();
    }

    public function getAvailableUsers(): Collection
    {
        return User::where('is_active', true)
            ->with('media')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
