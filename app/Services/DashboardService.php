<?php

namespace App\Services;

use App\Data\Dashboard\ActivityGraphData;
use App\Data\Dashboard\AttentionTaskData;
use App\Data\Dashboard\DashboardSummaryData;
use App\Data\Dashboard\LatestProjectData;
use App\Data\Dashboard\NeedsAttentionData;
use App\Data\Dashboard\ProjectProgressData;
use App\Data\Dashboard\TaskStatsData;
use App\Facades\Sqids;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardService
{
    /**
     * Panjang jendela "due this week" dalam hari.
     */
    private const DUE_WINDOW_DAYS = 7;

    /**
     * Panjang jendela "completed this week" ke belakang, dalam hari.
     */
    private const COMPLETED_WINDOW_DAYS = 7;

    public function __construct(
        protected DashboardRepository $repository
    ) {}

    /**
     * Hasil runningProjectIds() dipakai summary() dan taskStats() dalam satu request.
     *
     * @var array<int, int>|null
     */
    private ?array $cachedRunningProjectIds = null;

    /** @return array<int, int> */
    private function runningProjectIds(int $userId): array
    {
        return $this->cachedRunningProjectIds ??= $this->repository->runningProjectIds($userId);
    }

    public function summary(int $userId): DashboardSummaryData
    {
        return new DashboardSummaryData(
            running_projects: count($this->runningProjectIds($userId)),
            tasks_due_this_week: $this->repository->countTasksDueWithin($userId, self::DUE_WINDOW_DAYS),
            attention_items: $this->repository->countTasksNeedingAttention($userId),
        );
    }

    /**
     * Statistik seluruh task di project yang sedang berjalan.
     */
    public function taskStats(int $userId): TaskStatsData
    {
        $row = $this->repository->taskStats(
            $this->runningProjectIds($userId),
            $userId,
            self::DUE_WINDOW_DAYS,
            self::COMPLETED_WINDOW_DAYS,
        );

        $total = (int) $row->total;

        return new TaskStatsData(
            total: $total,
            done: (int) $row->done,
            done_recently: (int) $row->done_recently,
            in_progress: (int) $row->in_progress,
            in_progress_assigned_to_me: (int) $row->in_progress_assigned_to_me,
            overdue: (int) $row->overdue,
            due_soon: (int) $row->due_soon,
            active: (int) $row->active,
            progress_percent: $total > 0 ? (int) round((int) $row->done / $total * 100) : 0,
        );
    }

    /**
     * Panjang rentang activity graph dalam minggu. Jumlah harinya diturunkan dari sini
     * supaya label "last N weeks" di frontend dan query-nya tidak pernah berselisih.
     */
    private const ACTIVITY_WEEKS = 52;

    public function activityGraph(int $userId): ActivityGraphData
    {
        $rows = $this->repository->taskActivityByDay($userId, self::ACTIVITY_WEEKS * 7);

        /** @var array<string, int> $counts */
        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row->date] = (int) $row->count;
        }

        $total = array_sum($counts);
        $busiestCount = $counts === [] ? 0 : max($counts);
        $busiestDate = $counts === [] ? null : (string) array_search($busiestCount, $counts, true);

        return new ActivityGraphData(
            values: array_map(
                fn (string $date, int $count) => ['date' => $date, 'count' => $count],
                array_keys($counts),
                $counts,
            ),
            total_events: $total,
            weeks: self::ACTIVITY_WEEKS,
            busiest_date: $busiestDate,
            busiest_count: $busiestCount,
            current_streak: $this->currentStreak($counts),
            weekly_average: (int) round($total / self::ACTIVITY_WEEKS),
        );
    }

    /**
     * Hari berturut-turut yang punya aktivitas, dihitung mundur.
     *
     * Kalau hari ini masih kosong, hitungan dimulai dari kemarin: halaman yang dibuka
     * pagi-pagi seharusnya tidak melaporkan streak putus hanya karena harinya baru mulai.
     *
     * @param  array<string, int>  $counts
     */
    private function currentStreak(array $counts): int
    {
        $day = today();

        if (! isset($counts[$day->toDateString()])) {
            $day = $day->subDay();
        }

        $streak = 0;

        while (isset($counts[$day->toDateString()])) {
            $streak++;
            $day = $day->subDay();
        }

        return $streak;
    }

    /**
     * Seberapa jauh ke depan tenggat masih dianggap butuh perhatian.
     */
    private const ATTENTION_WINDOW_DAYS = 14;

    /**
     * Baris yang ditampilkan di kartu. Sisanya lewat "View all".
     */
    private const ATTENTION_LIMIT = 4;

    public function needsAttention(int $userId): NeedsAttentionData
    {
        $tasks = $this->repository->attentionTasks($userId, self::ATTENTION_WINDOW_DAYS, self::ATTENTION_LIMIT);

        return new NeedsAttentionData(
            total: $this->repository->countAttentionTasks($userId, self::ATTENTION_WINDOW_DAYS),
            items: $tasks->map(function (Task $task): AttentionTaskData {
                // due_date tidak di-cast di model Task, jadi nilainya string dari database.
                // Diuraikan di sini dan disamakan ke awal hari supaya jam tidak ikut
                // mempengaruhi selisih harinya.
                $dueDate = Carbon::parse($task->due_date)->startOfDay();

                return new AttentionTaskData(
                    id: Sqids::encode($task->id),
                    title: $task->title,
                    due_date: $dueDate->toDateString(),
                    // Negatif berarti sudah lewat tenggat.
                    days_remaining: (int) today()->diffInDays($dueDate, false),
                    // Hasil withCount, bukan kolom asli — diambil eksplisit supaya
                    // analisis statis tidak menganggapnya properti yang hilang.
                    open_subtasks: (int) $task->getAttribute('open_subtasks'),
                    owner_name: $task->project?->projectMembers->first()?->user?->name,
                );
            })->all(),
        );
    }

    /**
     * Tab yang boleh dipakai. Whitelist, bukan validasi: nilai dari request tidak
     * pernah diteruskan mentah ke repository.
     *
     * @var array<int, string>
     */
    private const PROJECT_TABS = ['all', 'active', 'at-risk'];

    private const PROJECT_PROGRESS_LIMIT = 5;

    private const LATEST_PROJECTS_LIMIT = 4;

    public function normalizeProjectTab(?string $tab): string
    {
        return in_array($tab, self::PROJECT_TABS, true) ? $tab : 'all';
    }

    /**
     * @return array<int, ProjectProgressData>
     */
    public function projectProgress(int $userId, string $tab): array
    {
        return $this->repository->projectProgress($userId, $tab, self::PROJECT_PROGRESS_LIMIT)
            ->map(fn (Project $project) => new ProjectProgressData(
                id: Sqids::encode($project->id),
                title: $project->title,
                emoji: $project->emoji,
                total_tasks: (int) $project->getAttribute('total_tasks'),
                done_tasks: (int) $project->getAttribute('done_tasks'),
                progress_percent: (int) round((float) $project->progress),
                due_date: $project->due_date?->toDateString(),
                status_name: $project->status?->name,
                status_severity: $project->status?->severity,
            ))->all();
    }

    /**
     * @return array<int, LatestProjectData>
     */
    public function latestProjects(int $userId): array
    {
        return $this->repository->latestProjects($userId, self::LATEST_PROJECTS_LIMIT)
            ->map(fn (Project $project) => new LatestProjectData(
                id: Sqids::encode($project->id),
                title: $project->title,
                // Deskripsi disimpan sebagai HTML dari editor. Tag dibuang di sini supaya
                // frontend tidak perlu v-html hanya untuk satu baris teks.
                description: $project->description
                    ? Str::limit(trim(strip_tags($project->description)), 120)
                    : null,
                created_at: $project->created_at->toIso8601String(),
                members_count: (int) $project->getAttribute('members_count'),
                status_name: $project->status?->name,
                status_severity: $project->status?->severity,
            ))->all();
    }
}
