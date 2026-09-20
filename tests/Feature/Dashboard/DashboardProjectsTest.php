<?php

use App\Models\MsProjectStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Database\Factories\ProjectFactory;

beforeEach(function () {
    // See ProjectTabTest: pin user id 1 so the master seeders' hardcoded owned_id/created_by
    // FKs resolve under Postgres' non-transactional sequences.
    $this->user = User::factory()->create(['id' => 1]);
    $this->actingAs($this->user);
});

function makeProjectStatus(string $name, string $severity = 'info'): MsProjectStatus
{
    return MsProjectStatus::create(['name' => $name, 'severity' => $severity, 'owned_id' => 1]);
}

function dashboardProject(?User $member, array $attributes = []): Project
{
    // Model Project belum memakai trait HasFactory, jadi factory-nya dipanggil langsung.
    $project = ProjectFactory::new()->create($attributes);

    if ($member) {
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'is_active' => true,
        ]);
    }

    return $project;
}

/** @return array<string, mixed> */
function dashboardProps(): array
{
    $response = test()->get(route('dashboard'));

    $response->assertOk();

    return $response->viewData('page')['props'];
}

describe('project stats', function () {
    it('hanya menghitung project di mana user terdaftar sebagai anggota', function () {
        dashboardProject($this->user, ['status_id' => null]);
        dashboardProject($this->user, ['status_id' => null]);
        dashboardProject(null, ['status_id' => null]);

        expect(dashboardProps()['stats']['projects']['total'])->toBe(2);
    });

    it('mengembalikan nol untuk user tanpa project', function () {
        $stats = dashboardProps()['stats']['projects'];

        expect($stats['total'])->toBe(0)
            ->and($stats['progress'])->toBe(0)
            ->and($stats['byStatus'])->toBe([]);
    });

    it('memakai rata-rata progress dari seluruh project user', function () {
        dashboardProject($this->user, ['progress' => 20, 'status_id' => null]);
        dashboardProject($this->user, ['progress' => 60, 'status_id' => null]);

        expect(dashboardProps()['stats']['projects']['progress'])->toBe(40);
    });

    it('menjumlahkan sebaran status sebanyak total project', function () {
        $active = makeProjectStatus('Active');

        dashboardProject($this->user, ['status_id' => $active->id]);
        dashboardProject($this->user, ['status_id' => $active->id]);
        dashboardProject($this->user, ['status_id' => null]);

        $stats = dashboardProps()['stats']['projects'];
        $counted = array_sum(array_column($stats['byStatus'], 'count'));

        expect($stats['total'])->toBe(3)
            ->and($counted)->toBe(3);
    });

    it('menyimpan project tanpa status di dalam sebaran', function () {
        dashboardProject($this->user, ['status_id' => null]);

        $byStatus = dashboardProps()['stats']['projects']['byStatus'];

        expect(collect($byStatus)->firstWhere('name', 'Tanpa status')['count'])->toBe(1);
    });

    it('mengurutkan sebaran status dari yang terbanyak', function () {
        $active = makeProjectStatus('Active');
        $paused = makeProjectStatus('Paused');

        dashboardProject($this->user, ['status_id' => $paused->id]);
        dashboardProject($this->user, ['status_id' => $active->id]);
        dashboardProject($this->user, ['status_id' => $active->id]);

        $byStatus = dashboardProps()['stats']['projects']['byStatus'];

        expect($byStatus[0]['name'])->toBe('Active')
            ->and($byStatus[0]['count'])->toBe(2);
    });
});

describe('panel project terbaru', function () {
    it('menampilkan paling banyak lima baris', function () {
        foreach (range(1, 8) as $ignored) {
            dashboardProject($this->user, ['status_id' => null]);
        }

        expect(dashboardProps()['projects'])->toHaveCount(5);
    });

    it('menampilkan project terbaru lebih dulu', function () {
        $first = dashboardProject($this->user, ['title' => 'Pertama', 'status_id' => null]);
        $second = dashboardProject($this->user, ['title' => 'Kedua', 'status_id' => null]);

        expect(array_column(dashboardProps()['projects'], 'title'))
            ->toEqual(['Kedua', 'Pertama'])
            ->and($second->id)->toBeGreaterThan($first->id);
    });

    it('menyembunyikan project milik orang lain', function () {
        dashboardProject($this->user, ['title' => 'Punya saya', 'status_id' => null]);
        dashboardProject(null, ['title' => 'Punya orang lain', 'status_id' => null]);

        expect(array_column(dashboardProps()['projects'], 'title'))->toEqual(['Punya saya']);
    });

    it('membawa emoji yang dipakai kolom judul di frontend', function () {
        dashboardProject($this->user, ['title' => 'Beremoji', 'emoji' => 'Rocket', 'status_id' => null]);

        expect(dashboardProps()['projects'][0]['emoji'])->toBe('Rocket');
    });

    it('mengembalikan panel kosong saat user belum punya project', function () {
        expect(dashboardProps()['projects'])->toBe([]);
    });
});
