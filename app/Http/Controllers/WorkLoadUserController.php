<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkLoadUserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['names', 'workload_statuses', 'search', 'per_page']);

        $perPage = $request->input('per_page', 50);

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
                        WHEN COUNT(t.id) = 0 THEN 'FREE (100%)'
                        WHEN ROUND(AVG(100 - t.progress)) = 0 THEN 'FREE (100%)'
                        WHEN ROUND(AVG(100 - t.progress)) BETWEEN 1 AND 20 THEN '80%'
                        WHEN ROUND(AVG(100 - t.progress)) BETWEEN 21 AND 50 THEN '50%'
                        ELSE 'BUSY'
                    END AS workload_status
                "),
            ]);


        $query = User::query()
            ->select([
                'users.*',
                'w.remaining_work_percent',
                'w.workload_status',
                'w.total_tasks',
            ])
            ->joinSub($workloadSub, 'w', fn($join) => $join->on('w.user_id', '=', 'users.id'));

        $this->applyFilters($query, $filters);


        $allForSummary = (clone $query)->get(['w.workload_status']);

        $summary = [
            'total_users' => $allForSummary->count(),
            'free'        => $allForSummary->where('workload_status', 'FREE (100%)')->count(),
            'light'       => $allForSummary->where('workload_status', '80%')->count(),
            'moderate'    => $allForSummary->where('workload_status', '50%')->count(),
            'busy'        => $allForSummary->where('workload_status', 'BUSY')->count(),
        ];

        $users = $query
            ->orderByDesc('w.remaining_work_percent')
            ->paginate($perPage)
            ->withQueryString();

        $formattedUsers = $users->through(function ($user) {
            return [
                'id'                     => Sqids::encode($user->id),
                'name'                   => $user->name,
                'avatar_url'             => $user->avatar_url ?? null,
                'remaining_work_percent' => $user->remaining_work_percent,
                'workload_status'        => $user->workload_status,
                'total_tasks'            => $user->total_tasks,
            ];
        });

        $filterOptions = $this->getFilterOptions();

        return Inertia::render('workload/index', [
            'users'         => $formattedUsers,
            'summary'       => $summary,
            'filters'       => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    private function applyFilters($query, array $filters): void
    {

        if (!empty($filters['names'])) {
            $names = is_array($filters['names'])
                ? $filters['names']
                : explode(',', $filters['names']);

            $userIds = collect($names)
                ->map(fn($encoded) => Sqids::decode($encoded))
                ->filter()
                ->toArray();

            $query->whereIn('users.id', $userIds);
        }


        if (!empty($filters['workload_statuses'])) {
            $statuses = is_array($filters['workload_statuses'])
                ? $filters['workload_statuses']
                : explode(',', $filters['workload_statuses']);

            $statusStrings = collect($statuses)
                ->map(fn($id) => $this->statusMap()[(int) $id] ?? null)
                ->filter()
                ->values()
                ->toArray();

            if (!empty($statusStrings)) {
                $query->whereIn('w.workload_status', $statusStrings);
            }
        }


        if (!empty($filters['search'])) {
            $query->where('users.name', 'ILIKE', "%{$filters['search']}%");
        }
    }


    private function getFilterOptions(): array
    {
        $users = User::where('is_active', true)
            ->with('media')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($user) => [
                'id'        => Sqids::encode($user->id),
                'name'      => $user->name,
                'avatar_url' => $user->avatar_url ?? null,
            ]);

        $workloadStatuses = collect($this->statusMap())
            ->map(fn($name, $id) => [
                'id'       => $id,
                'name'     => $name,
                'severity' => $this->getSeverity($name),
            ])
            ->values();

        return [
            'users'            => $users->toArray(),
            'workload_statuses' => $workloadStatuses->toArray(),
        ];
    }

    private function statusMap(): array
    {
        return [
            1 => 'FREE (100%)',
            2 => '80%',
            3 => '50%',
            4 => 'BUSY',
        ];
    }

    private function getSeverity(string $status): string
    {
        return match ($status) {
            'FREE (100%)' => 'success',
            '80%'         => 'warning',
            '50%'         => 'help',
            default       => 'danger',
        };
    }
}
