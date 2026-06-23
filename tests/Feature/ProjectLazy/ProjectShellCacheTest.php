<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Pin user id 1 so master seeders' hardcoded FKs resolve under Postgres.
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
    ]);

    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Cached Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
    $this->repository = app(ProjectRepository::class);
});

function shellIsCached(): bool
{
    return Cache::tags('project-shell')->has('project-shell:'.test()->project->id);
}

function warmShellCache(): void
{
    test()->repository->findShell(test()->encoded);
    expect(shellIsCached())->toBeTrue();
}

it('caches the project shell on first lookup', function () {
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
});

it('forgets the project shell when the project status changes', function () {
    warmShellCache();

    $otherStatus = MsProjectStatus::where('id', '!=', $this->project->status_id)->firstOrFail();
    $this->project->update(['status_id' => $otherStatus->id]);

    expect(shellIsCached())->toBeFalse();
});

it('forgets the project shell when the project priority changes', function () {
    warmShellCache();

    $otherPriority = MsProjectPriority::where('id', '!=', $this->project->priority_id)->firstOrFail();
    $this->project->update(['priority_id' => $otherPriority->id]);

    expect(shellIsCached())->toBeFalse();
});

it('keeps the project shell when an unrelated project field changes', function () {
    warmShellCache();

    $this->project->update(['title' => 'Renamed only']);

    expect(shellIsCached())->toBeTrue();
});

it('forgets the project shell when the project is deleted', function () {
    warmShellCache();

    $this->project->delete();

    expect(shellIsCached())->toBeFalse();
});

it('forgets the project shell when a member is created, updated or deleted', function () {
    $ownerRole = MsProjectRole::where('name', 'Owner')->firstOrFail();

    warmShellCache();
    $member = ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => $ownerRole->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
    $member->update(['is_active' => false]);
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
    $member->delete();
    expect(shellIsCached())->toBeFalse();
});

it('flushes shells when a project status master is updated or deleted', function () {
    warmShellCache();
    MsProjectStatus::query()->firstOrFail()->update(['name' => 'Renamed status']);
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
    MsProjectStatus::where('id', '!=', $this->project->status_id)->firstOrFail()->delete();
    expect(shellIsCached())->toBeFalse();
});

it('flushes shells when a project priority master is updated or deleted', function () {
    warmShellCache();
    MsProjectPriority::query()->firstOrFail()->update(['name' => 'Renamed priority']);
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
    MsProjectPriority::where('id', '!=', $this->project->priority_id)->firstOrFail()->delete();
    expect(shellIsCached())->toBeFalse();
});

it('flushes shells when a project role is updated or deleted', function () {
    warmShellCache();
    MsProjectRole::where('name', 'Owner')->firstOrFail()->update(['name' => 'Lead']);
    expect(shellIsCached())->toBeFalse();

    warmShellCache();
    MsProjectRole::where('name', '!=', 'Lead')->firstOrFail()->delete();
    expect(shellIsCached())->toBeFalse();
});

it('flushes shells when a relevant user field changes or the user is deleted', function () {
    warmShellCache();
    $this->user->update(['name' => 'Renamed user']);
    expect(shellIsCached())->toBeFalse();

    $member = User::factory()->create(['id' => 999]);
    warmShellCache();
    $member->delete();
    expect(shellIsCached())->toBeFalse();
});

it('keeps shells when an irrelevant user field changes', function () {
    warmShellCache();

    $this->user->update(['created_by' => $this->user->id]);

    expect(shellIsCached())->toBeTrue();
});

it('flushes shells when a user avatar is added or replaced', function () {
    Storage::fake('public');
    warmShellCache();

    $this->user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    expect(shellIsCached())->toBeFalse();
});

it('flushes shells when a user avatar is removed', function () {
    Storage::fake('public');
    $this->user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    warmShellCache();
    $this->user->clearMediaCollection('avatar');

    expect(shellIsCached())->toBeFalse();
});

it('keeps shells when non-avatar media changes', function () {
    Storage::fake('public');
    warmShellCache();

    $this->user->addMedia(UploadedFile::fake()->create('doc.pdf'))
        ->toMediaCollection('documents');

    expect(shellIsCached())->toBeTrue();
});
