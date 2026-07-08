<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\ProjectOptionsTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
    ]);
});

it('returns the project status and priority option lists with sqid-encoded ids', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(ProjectOptionsTool::class);

    $response->assertOk();

    $response->assertSee(['"statuses"', '"priorities"']);

    // a seeded option is present and its id is a sqid string (encoded), not a raw integer
    $status = MsProjectStatus::query()->firstOrFail();
    $response->assertSee($status->name);
    $response->assertSee(Sqids::encode($status->id));

    $priority = MsProjectPriority::query()->firstOrFail();
    $response->assertSee(Sqids::encode($priority->id));
});
