<?php

namespace Database\Seeders;

use App\Models\MsSprintStatus;
use App\Models\Project;
use App\Models\ProjectSprint;
use Illuminate\Database\Seeder;

class ProjectSprintSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatusId = MsSprintStatus::where('name', 'Active')->value('id');

        if (! $activeStatusId) {
            return;
        }

        Project::all()->each(function (Project $project) use ($activeStatusId) {
            $sprint = ProjectSprint::create([
                'project_id' => $project->id,
                'sprint_status_id' => $activeStatusId,
                'name' => 'Sprint 1',
                'goal' => 'Deliver the current batch of planned work.',
                'start_date' => now()->subWeek(),
                'end_date' => now()->addWeek(),
                'order' => 1,
            ]);

            $taskIds = $project->tasks()
                ->whereNull('parent_id')
                ->pluck('id');

            $sprint->tasks()->attach($taskIds);
        });
    }
}
