<?php

namespace App\Services;

use App\Data\Sprint\BurndownChartData;
use App\Data\Sprint\SprintStatusReportData;
use App\Data\Task\TaskData;
use App\Models\ProjectSprint;
use App\Repositories\SprintReportRepository;
use Spatie\LaravelData\DataCollection;

class SprintReportService
{
    public function __construct(
        protected SprintReportRepository $repository
    ) {}

    public function getSprintStatusReport(ProjectSprint $sprint): SprintStatusReportData
    {
        $data = $this->repository->getSprintStatusData($sprint->id);

        return new SprintStatusReportData(
            completed_tasks: TaskData::collect($data['completed_tasks'], DataCollection::class),
            incomplete_tasks: TaskData::collect($data['incomplete_tasks'], DataCollection::class)
        );
    }

    /**
     * Get burndown chart data
     *
     * @return array<int, BurndownChartData>
     */
    public function getBurndownChartData(ProjectSprint $sprint): array
    {
        $data = $this->repository->getBurndownChartData($sprint->id);

        return BurndownChartData::collect($data);
    }
}
