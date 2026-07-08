<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
    ]);

    $this->status = MsProjectStatus::query()->firstOrFail();
    $this->priority = MsProjectPriority::query()->firstOrFail();
});

function createProjectArgs(array $overrides = []): array
{
    return array_merge([
        'title' => 'New MCP Project',
        'description' => '<p>Rich <strong>text</strong> description</p>',
        'emoji' => '🚀',
        'start_date' => '2026-01-01',
        'due_date' => '2026-02-01',
        'status_id' => Sqids::encode(test()->status->id),
        'priority_id' => Sqids::encode(test()->priority->id),
    ], $overrides);
}

it('creates a project (with the creator as Owner member) for a user with project.create permission', function () {
    $this->user->givePermissionTo(Permission::findOrCreate('project.create', 'web'));

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(CreateProjectTool::class, createProjectArgs());

    $response->assertOk();
    $response->assertSee('New MCP Project');

    $project = Project::where('title', 'New MCP Project')->firstOrFail();

    expect($project->status_id)->toBe($this->status->id)
        ->and($project->priority_id)->toBe($this->priority->id)
        ->and($project->description)->toBe('<p>Rich <strong>text</strong> description</p>');

    // creator is attached as an Owner project member
    $ownerRoleId = MsProjectRole::where('name', 'Owner')->value('id');
    expect(
        ProjectMember::where('project_id', $project->id)
            ->where('user_id', $this->user->id)
            ->where('project_role_id', $ownerRoleId)
            ->where('is_active', true)
            ->exists()
    )->toBeTrue();
});

it('errors and creates nothing when the user lacks project.create permission', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(CreateProjectTool::class, createProjectArgs(['title' => 'Denied Project']));

    $response->assertHasErrors();

    expect(Project::where('title', 'Denied Project')->exists())->toBeFalse();
});

it('errors and creates nothing when the status_id is not a valid option', function () {
    $this->user->givePermissionTo(Permission::findOrCreate('project.create', 'web'));

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(CreateProjectTool::class, createProjectArgs([
            'title' => 'Bad Status Project',
            'status_id' => Sqids::encode(999999),
        ]));

    $response->assertHasErrors();

    expect(Project::where('title', 'Bad Status Project')->exists())->toBeFalse();
});
