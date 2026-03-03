<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkLoadUserController extends Controller
{
    public function index(Request $request)
    {
        $names    = $request->input('names', []);
        $statuses = $request->input('workload_statuses', []);
        $search   = $request->input('search');
        $perPage  = $request->input('per_page', 10);

        // =====================================================
        // SUBQUERY: workload calculation (PURE SQL)
        // =====================================================
        $workloadSub = DB::table('users as u')
            ->leftJoin('task_users as tu', 'tu.user_id', '=', 'u.id')
            ->leftJoin('tasks as t', function ($join) {
                $join->on('t.id', '=', 'tu.task_id')
                    ->whereNull('t.deleted_at')
                    ->where('t.is_archived', false);
            })
            ->where('u.is_active', true)
            ->groupBy('u.id')
            ->select([
                'u.id as user_id',
                DB::raw('COALESCE(ROUND(AVG(100 - t.progress)), 0) AS remaining_work_percent'),
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

        // =====================================================
        // MAIN QUERY: User Eloquent (avatar_url works here)
        // =====================================================
        $query = User::query()
            ->select([
                'users.*',
                'w.remaining_work_percent',
                'w.workload_status',
            ])
            ->joinSub($workloadSub, 'w', function ($join) {
                $join->on('w.user_id', '=', 'users.id');
            });

        // =====================================================
        // FILTERS
        // =====================================================
        if (!empty($names)) {
            $query->whereIn('users.id', (array) $names);
        }

        if ($search) {
            $query->where('users.name', 'ILIKE', "%{$search}%"); // Postgres
        }

        if (!empty($statuses)) {
            $map = [
                1 => 'FREE (100%)',
                2 => '80%',
                3 => '50%',
                4 => 'BUSY',
            ];

            $query->whereIn(
                'w.workload_status',
                collect($statuses)->map(fn($id) => $map[(int) $id])->toArray()
            );
        }

        // =====================================================
        // PAGINATION
        // =====================================================
        $users = $query
            ->orderByDesc('w.remaining_work_percent')
            ->paginate($perPage)
            ->withQueryString();

        // =====================================================
        // FILTER OPTIONS
        // =====================================================
        $filterOptions = [
            'users' => User::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']), // avatar_url auto-append
            'workload_statuses' => [
                ['id' => 1, 'name' => 'FREE (100%)', 'severity' => 'success'],
                ['id' => 2, 'name' => '80%', 'severity' => 'warning'],
                ['id' => 3, 'name' => '50%', 'severity' => 'help'],
                ['id' => 4, 'name' => 'BUSY', 'severity' => 'danger'],
            ],
        ];

        return Inertia::render('workload/index', [
            'users' => $users,
            'filters' => $request->only(['names', 'workload_statuses', 'search']),
            'filterOptions' => $filterOptions,
        ]);
    }
}
