<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\ProjectDetailTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
    ]);

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'MCP Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    // The acting user has access by being a member of the project (Project::visibleFor).
    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

it('returns the project detail shell without statuses and priorities, with sqid-encoded ids', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(ProjectDetailTool::class, ['project' => $this->encoded]);

    $response->assertOk();

    // shellData payload minus the master option lists
    $response->assertSee(['"project"', '"members"', '"isMember"', '"policy"']);
    $response->assertSee('MCP Project');

    // the acting member appears, and ids are sqid strings (encoded)
    $response->assertSee(Sqids::encode($this->project->id));

    // statuses / priorities option lists are intentionally excluded
    $response->assertDontSee('"statuses"');
    $response->assertDontSee('"priorities"');
});

it('errors when the acting user is not a member of the project', function () {
    $outsider = User::factory()->create(['id' => 2]);

    $response = ProjectManagementServer::actingAs($outsider)
        ->tool(ProjectDetailTool::class, ['project' => $this->encoded]);

    $response->assertHasErrors();
});

it('errors when the project id is invalid', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(ProjectDetailTool::class, ['project' => 'not-a-valid-id']);

    $response->assertHasErrors();
});
