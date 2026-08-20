<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        Project::all()->each(function (Project $project) use ($userIds) {
            Task::factory()
                ->forProject($project)
                ->count(fake()->numberBetween(5, 15))
                ->create()
                ->each(function (Task $task) use ($userIds) {
                    $task->users()->attach($userIds->random(fake()->numberBetween(1, min(2, $userIds->count()))));
                });
        });
    }
}
