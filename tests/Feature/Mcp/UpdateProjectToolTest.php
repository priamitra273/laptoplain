<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\UpdateProjectTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded owned_id/created_by FKs resolve
    // under Postgres' non-transactional sequences (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
    ]);

    // "On Hold" is not one of the statuses that demand a due date, so partial updates
    // that don't touch the due date stay valid.
    $this->onHold = MsProjectStatus::where('name', 'On Hold')->firstOrFail();

    $this->project = Project::create([
        'status_id' => $this->onHold->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Original Title',
        'emoji' => '🗂️',
        'description' => '<p>original</p>',
        'start_date' => '2026-01-01',
        'due_date' => '2026-12-31',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function grantUpdate(): void
{
    test()->user->givePermissionTo(Permission::findOrCreate('project.update', 'web'));
}

it('updates a project (partial) for a user with project.update permission', function () {
    grantUpdate();
    $completed = MsProjectStatus::where('name', 'Completed')->firstOrFail();

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateProjectTool::class, [
            'project' => $this->encoded,
            'title' => 'Updated Title',
            'status_id' => Sqids::encode($completed->id),
        ]);

    $response->assertOk();
    $response->assertSee('Updated Title');

    $fresh = $this->project->fresh();
    expect($fresh->title)->toBe('Updated Title')
        ->and($fresh->status_id)->toBe($completed->id)
        // untouched fields are preserved
        ->and($fresh->emoji)->toBe('🗂️');
});

it('errors and changes nothing when the user lacks project.update permission', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateProjectTool::class, [
            'project' => $this->encoded,
            'title' => 'Hacked Title',
        ]);

    $response->assertHasErrors();
    expect($this->project->fresh()->title)->toBe('Original Title');
});

it('errors when the project id is invalid', function () {
    grantUpdate();

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateProjectTool::class, [
            'project' => 'not-a-valid-id',
            'title' => 'Whatever',
        ]);

    $response->assertHasErrors();
});

it('errors and changes nothing when the status_id is not a valid option', function () {
    grantUpdate();

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateProjectTool::class, [
            'project' => $this->encoded,
            'status_id' => Sqids::encode(999999),
        ]);

    $response->assertHasErrors();
    expect($this->project->fresh()->status_id)->toBe($this->onHold->id);
});

it('requires a due date when the status is set to one that demands it', function () {
    grantUpdate();
    // "In Progress" is one of the statuses that require a due date (mirrors
    // ProjectUpdateRequest::withValidator). No due_date is provided here.
    $inProgress = MsProjectStatus::where('name', 'In Progress')->firstOrFail();

    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(UpdateProjectTool::class, [
            'project' => $this->encoded,
            'status_id' => Sqids::encode($inProgress->id),
        ]);

    $response->assertHasErrors();
    expect($this->project->fresh()->status_id)->toBe($this->onHold->id);
});
