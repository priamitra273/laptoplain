<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\TaskOptionsTool;
use App\Models\MsTaskStatus;
use App\Models\TaskCategory;
use App\Models\User;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
    ]);
});

it('returns the task master option lists with sqid-encoded ids', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(TaskOptionsTool::class);

    $response->assertOk();

    // all the master lists the task form needs are bundled together
    $response->assertSee([
        'taskStatuses',
        'taskPriorities',
        'taskTypes',
        'taskCategories',
        'tags',
    ]);

    // a seeded option is present and its id is a sqid string (encoded), not a raw integer
    $status = MsTaskStatus::query()->firstOrFail();
    $response->assertSee($status->name);
    $response->assertSee(Sqids::encode($status->id));

    $category = TaskCategory::query()->firstOrFail();
    $response->assertSee(Sqids::encode($category->id));
});
