<?php

use App\Models\MsTaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Database\Factories\ProjectFactory;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function makeTaskStatus(string $name, string $severity = 'info'): MsTaskStatus
{
    return MsTaskStatus::create(['name' => $name, 'severity' => $severity, 'score' => 0]);
}

function dashboardTask(User $user, array $attributes = []): Task
{
    return Task::factory()->create(array_merge(['created_by' => $user->id, 'status_id' => null], $attributes));
}

function makeMemberProject(User $user): Project
{
    // Model Project belum memakai trait HasFactory, jadi factory-nya dipanggil langsung.
    $project = ProjectFactory::new()->create();

    ProjectMember::create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $project;
}

/** @return array<string, mixed> */
function fetchDashboardProps(): array
{
    $response = test()->get(route('dashboard'));

    $response->assertOk();

    return $response->viewData('page')['props'];
}

describe('status breakdown', function () {
    it('accounts for every task the user owns, not only the rows on the panel', function () {
        $todo = makeTaskStatus('Todo');
        $doing = makeTaskStatus('Doing');

        Task::factory()->count(6)->create(['created_by' => $this->user->id, 'status_id' => $todo->id]);
        Task::factory()->count(3)->create(['created_by' => $this->user->id, 'status_id' => $doing->id]);

        $stats = fetchDashboardProps()['stats']['tasks'];
        $counted = array_sum(array_column($stats['byStatus'], 'count'));

        expect($stats['total'])->toBe(9)
            ->and($counted)->toBe(9);
    });

    it('keeps tasks without a status inside the breakdown', function () {
        $todo = makeTaskStatus('Todo');

        dashboardTask($this->user, ['status_id' => $todo->id]);
        dashboardTask($this->user, ['status_id' => null]);

        $stats = fetchDashboardProps()['stats']['tasks'];
        $counted = array_sum(array_column($stats['byStatus'], 'count'));

        expect($stats['total'])->toBe(2)
            ->and($counted)->toBe(2)
            ->and(collect($stats['byStatus'])->firstWhere('name', 'Tanpa status')['count'])->toBe(1);
    });

    it('ignores tasks belonging to other people', function () {
        $stranger = User::factory()->create();

        dashboardTask($this->user);
        dashboardTask($stranger);

        $stats = fetchDashboardProps()['stats']['tasks'];

        expect($stats['total'])->toBe(1)
            ->and(array_sum(array_column($stats['byStatus'], 'count')))->toBe(1);
    });

    it('counts a task once even when several people are assigned to it', function () {
        $mate = User::factory()->create();

        Task::factory()
            ->assignUsers([$this->user, $mate])
            ->create(['created_by' => $this->user->id, 'status_id' => null]);

        $stats = fetchDashboardProps()['stats']['tasks'];

        expect($stats['total'])->toBe(1)
            ->and(array_sum(array_column($stats['byStatus'], 'count')))->toBe(1);
    });
});

describe('tasks needing attention', function () {
    it('puts the most overdue task first', function () {
        dashboardTask($this->user, ['title' => 'Besok', 'due_date' => today()->addDay()]);
        dashboardTask($this->user, ['title' => 'Paling telat', 'due_date' => today()->subDays(9)]);
        dashboardTask($this->user, ['title' => 'Telat sedikit', 'due_date' => today()->subDay()]);

        $titles = array_column(fetchDashboardProps()['attention'], 'title');

        expect($titles)->toEqual(['Paling telat', 'Telat sedikit', 'Besok']);
    });

    it('leaves out completed, archived, and undated tasks', function () {
        dashboardTask($this->user, ['title' => 'Aktif', 'due_date' => today()]);
        Task::factory()->completed()->create(['created_by' => $this->user->id, 'title' => 'Selesai', 'due_date' => today()]);
        Task::factory()->archived()->create(['created_by' => $this->user->id, 'title' => 'Diarsipkan', 'due_date' => today()]);
        dashboardTask($this->user, ['title' => 'Tanpa tenggat', 'due_date' => null]);

        $titles = array_column(fetchDashboardProps()['attention'], 'title');

        expect($titles)->toEqual(['Aktif']);
    });

    it('shows at most five rows', function () {
        foreach (range(1, 8) as $offset) {
            dashboardTask($this->user, ['due_date' => today()->addDays($offset)]);
        }

        expect(fetchDashboardProps()['attention'])->toHaveCount(5);
    });

    it('includes tasks assigned to the user but created by someone else', function () {
        $stranger = User::factory()->create();

        Task::factory()
            ->assignUsers($this->user)
            ->create(['created_by' => $stranger->id, 'title' => 'Ditugaskan', 'due_date' => today()]);

        expect(array_column(fetchDashboardProps()['attention'], 'title'))->toEqual(['Ditugaskan']);
    });
});

describe('overdue counter', function () {
    it('counts every overdue task, including ones past the panel limit', function () {
        foreach (range(1, 7) as $offset) {
            dashboardTask($this->user, ['due_date' => today()->subDays($offset)]);
        }

        dashboardTask($this->user, ['due_date' => today()->addWeek()]);

        $stats = fetchDashboardProps()['stats']['tasks'];

        expect($stats['overdue'])->toBe(7);
    });

    it('does not count a task that is due today', function () {
        dashboardTask($this->user, ['due_date' => today()]);

        expect(fetchDashboardProps()['stats']['tasks']['overdue'])->toBe(0);
    });
});

describe('team members', function () {
    it('is deferred so the rest of the dashboard renders first', function () {
        expect(fetchDashboardProps())->not->toHaveKey('members');
    });

    it('arrives on a partial reload without duplicating people across projects', function () {
        $mate = User::factory()->create();

        foreach (range(1, 2) as $ignored) {
            $project = makeMemberProject($this->user);

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $mate->id,
                'is_active' => true,
            ]);
        }

        // Versi diambil dari payload halaman pertama; menebaknya membuat Inertia balas 409.
        $version = $this->get(route('dashboard'))->viewData('page')['version'];

        $members = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Dashboard',
            'X-Inertia-Partial-Data' => 'members',
        ])->get(route('dashboard'))->json('props.members');

        expect($members)->toHaveCount(2)
            ->and(array_column($members, 'name'))
            ->toEqualCanonicalizing([$this->user->name, $mate->name]);
    });
});

describe('due soon counter', function () {
    it('counts open tasks from today through the next seven days', function () {
        dashboardTask($this->user, ['due_date' => today()]);
        dashboardTask($this->user, ['due_date' => today()->addDays(7)]);
        dashboardTask($this->user, ['due_date' => today()->addDays(8)]);
        dashboardTask($this->user, ['due_date' => today()->subDay()]);

        expect(fetchDashboardProps()['stats']['tasks']['dueSoon'])->toBe(2);
    });

    it('leaves out completed and archived tasks', function () {
        Task::factory()->completed()->create(['created_by' => $this->user->id, 'status_id' => null, 'due_date' => today()]);
        Task::factory()->archived()->create(['created_by' => $this->user->id, 'status_id' => null, 'due_date' => today()]);

        expect(fetchDashboardProps()['stats']['tasks']['dueSoon'])->toBe(0);
    });
});

describe('recent tasks panel', function () {
    it('puts the newest task first, whether or not it has a due date', function () {
        dashboardTask($this->user, ['title' => 'Lebih lama', 'due_date' => null]);
        dashboardTask($this->user, ['title' => 'Paling baru', 'due_date' => null]);

        expect(array_column(fetchDashboardProps()['tasks'], 'title'))->toEqual(['Paling baru', 'Lebih lama']);
    });

    it('keeps completed tasks but drops archived ones', function () {
        Task::factory()->completed()->create(['created_by' => $this->user->id, 'status_id' => null, 'title' => 'Selesai']);
        Task::factory()->archived()->create(['created_by' => $this->user->id, 'status_id' => null, 'title' => 'Diarsipkan']);

        expect(array_column(fetchDashboardProps()['tasks'], 'title'))->toEqual(['Selesai']);
    });

    it('shows at most five rows', function () {
        foreach (range(1, 8) as $ignored) {
            dashboardTask($this->user);
        }

        expect(fetchDashboardProps()['tasks'])->toHaveCount(5);
    });
});

describe('task origin flags', function () {
    it('marks a task the user was assigned to but did not create', function () {
        $stranger = User::factory()->create();

        Task::factory()
            ->assignUsers($this->user)
            ->create(['created_by' => $stranger->id, 'status_id' => null, 'due_date' => today()]);

        $row = fetchDashboardProps()['attention'][0];

        expect($row['is_assigned'])->toBeTrue()
            ->and($row['is_created_by_me'])->toBeFalse()
            ->and(array_column($row['assignees'], 'name'))->toEqual([$this->user->name]);
    });

    it('marks a task the user created without being assigned to it', function () {
        dashboardTask($this->user, ['due_date' => today()]);

        $row = fetchDashboardProps()['attention'][0];

        expect($row['is_assigned'])->toBeFalse()
            ->and($row['is_created_by_me'])->toBeTrue()
            ->and($row['assignees'])->toBe([]);
    });

    it('keeps the task_users pivot out of the payload', function () {
        dashboardTask($this->user, ['due_date' => today()]);

        expect(fetchDashboardProps()['attention'][0])->not->toHaveKey('users');
    });
});
