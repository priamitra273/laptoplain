<?php

namespace App\Services;

use App\Data\Sprint\BurndownChartData;
use App\Data\Sprint\SprintStatusReportData;
use App\Models\ProjectSprint;
use App\Repositories\SprintReportRepository;

class SprintReportService
{
    public function __construct(
        protected SprintReportRepository $repository
    ) {}

    public function getSprintStatusReport(ProjectSprint $sprint): SprintStatusReportData
    {
        $data = $this->repository->getSprintStatusData($sprint->id);

        return SprintStatusReportData::from($data);
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
