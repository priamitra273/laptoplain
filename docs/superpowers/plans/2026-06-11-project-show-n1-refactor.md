# Project Show — N+1 & Query Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hilangkan N+1 dan query redundan pada alur `ProjectService::getShowData()` dengan membuat `calculateProgress()` memakai relasi yang sudah di-eager-load, mengamankan write-on-read, dan memperbaiki query liar di `getAuthUserPolicy()`.

**Architecture:** Sumber N+1 bukan di eager loading repository (itu sudah benar), melainkan di baris `$project->update(['progress' => $project->calculateProgress()])`. Rantai `Project::calculateProgress()` → `Task::calculateProgress()` melakukan `->children()->avg('progress')` per task (query SQL per node) dan me-`re-query` daftar task yang sudah ada di memori. Solusinya: hitung dari koleksi relasi yang sudah dimuat (in-memory) dengan fallback aman saat relasi belum dimuat.

**Tech Stack:** Laravel 11, Eloquent, Pest 3 (PHPUnit), PHP 8.

---

## ⚠️ Catatan Lingkungan Test (baca sebelum mulai)

- Suite **Feature** saat ini **gagal boot** di sqlite `:memory:` karena migrasi `database/migrations/2026_03_26_071450_make_sprint_fields_nullable.php` tidak kompatibel. Memperbaiki migrasi itu **di luar scope** plan ini.
- Karena itu, logika inti (N+1 pada `calculateProgress`) diuji lewat **unit test in-memory murni** di `tests/Unit/` — tanpa `RefreshDatabase`, tanpa DB.
- Kunci desain test: model dibuat dengan `new Task([...])` lalu relasinya di-set manual via `setRelation()`. Implementasi **lama** memanggil `->children()->avg(...)` yang membutuhkan koneksi DB → di unit test ini akan **error/fail** (red). Implementasi **baru** memakai koleksi yang sudah dimuat → **pass** (green). Ini memberi siklus red→green yang sah tanpa DB.
- Jalankan hanya suite Unit: `php artisan test --testsuite=Unit`.

## File Structure

- Modify: `app/Models/Task.php` — `calculateProgress()` pakai relasi `children` yang sudah dimuat.
- Modify: `app/Models/Project.php` — `calculateProgress()` pakai relasi `tasks` yang sudah dimuat, tanpa re-query.
- Modify: `app/Services/ProjectService.php` — guard write-on-read di `getShowData()`; perbaiki `getAuthUserPolicy()`.
- Modify: `app/Repositories/ProjectRepository.php` — tambahkan kolom `config` pada eager load `projectMembers.role`.
- Create: `tests/Unit/TaskProgressTest.php` — unit test `Task::calculateProgress()`.
- Create: `tests/Unit/ProjectProgressTest.php` — unit test `Project::calculateProgress()`.

---

## Task 1: `Task::calculateProgress()` pakai children yang sudah dimuat

**Files:**
- Test: `tests/Unit/TaskProgressTest.php` (Create)
- Modify: `app/Models/Task.php:216-221`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/TaskProgressTest.php`:

```php
<?php

use App\Models\Task;

test('calculateProgress averages loaded children without touching the database', function () {
    // Relasi children di-set manual (in-memory). Jika implementasi memanggil
    // query builder (->children()->avg()), test ini error karena tak ada koneksi DB.
    $parent = new Task(['progress' => 99]);
    $parent->setRelation('children', collect([
        new Task(['progress' => 40]),
        new Task(['progress' => 60]),
    ]));

    expect($parent->calculateProgress())->toBe(50.0);
});

test('calculateProgress falls back to own progress when there are no children', function () {
    $parent = new Task(['progress' => 33]);
    $parent->setRelation('children', collect());

    expect($parent->calculateProgress())->toBe(33.0);
});
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --testsuite=Unit --filter=calculateProgress`
Expected: FAIL — implementasi lama memanggil `$this->children()->avg('progress')` yang mencoba membuka koneksi DB / tidak memakai koleksi yang dimuat.

- [ ] **Step 3: Implementasi minimal**

Ganti method di `app/Models/Task.php` (saat ini baris 216-221):

```php
public function calculateProgress(): float
{
    // Pakai relasi children yang sudah di-eager-load (Project::tasks()->with('children')
    // dan scopeWithRecursive() memuatnya). Hanya query bila benar-benar belum dimuat.
    $children = $this->relationLoaded('children')
        ? $this->children
        : $this->children()->get(['id', 'parent_id', 'progress']);

    $avg = $children->isEmpty() ? null : $children->avg('progress');

    return round($avg ?? (float) $this->progress, 2);
}
```

Catatan kesetaraan: `Collection::avg()` mengabaikan nilai `null` sama seperti `AVG()` di SQL, jadi semantik hasilnya identik dengan implementasi lama.

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --testsuite=Unit --filter=calculateProgress`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/TaskProgressTest.php app/Models/Task.php
git commit -m "perf(task): calculateProgress reads loaded children to avoid N+1"
```

---

## Task 2: `Project::calculateProgress()` pakai tasks yang sudah dimuat (tanpa re-query)

**Files:**
- Test: `tests/Unit/ProjectProgressTest.php` (Create)
- Modify: `app/Models/Project.php:166-183`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/ProjectProgressTest.php`:

```php
<?php

use App\Models\Project;
use App\Models\Task;

test('calculateProgress averages loaded tasks without touching the database', function () {
    $task1 = new Task(['progress' => 0]);
    $task1->setRelation('children', collect([new Task(['progress' => 100])])); // avg 100
    $task2 = new Task(['progress' => 0]);
    $task2->setRelation('children', collect([new Task(['progress' => 0])]));   // avg 0

    $project = new Project();
    $project->setRelation('tasks', collect([$task1, $task2]));

    expect($project->calculateProgress())->toBe(50.0);
});

test('calculateProgress returns 0 when there are no tasks', function () {
    $project = new Project();
    $project->setRelation('tasks', collect());

    expect($project->calculateProgress())->toBe(0);
});
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test --testsuite=Unit --filter="calculateProgress averages loaded tasks"`
Expected: FAIL — implementasi lama memanggil `$this->tasks()->with('children')->get()` (butuh koneksi DB) alih-alih memakai relasi `tasks` yang sudah dimuat.

- [ ] **Step 3: Implementasi minimal**

Ganti method di `app/Models/Project.php` (saat ini baris 166-183):

```php
public function calculateProgress(): float
{
    // Pakai relasi tasks yang sudah di-eager-load (findWithRelationsForShow).
    // Hanya query bila belum dimuat (mis. dipanggil di luar alur show).
    $tasks = $this->relationLoaded('tasks')
        ? $this->tasks
        : $this->tasks()->with('children')->get();

    if ($tasks->isEmpty()) {
        return 0;
    }

    return round($tasks->avg(fn (Task $task) => $task->calculateProgress()), 2);
}
```

Pastikan `use App\Models\Task;` sudah ada di bagian atas `app/Models/Project.php`; jika belum, tambahkan.

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test --testsuite=Unit`
Expected: PASS (semua test Unit, termasuk 4 test progress baru)

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/ProjectProgressTest.php app/Models/Project.php
git commit -m "perf(project): calculateProgress reads loaded tasks to avoid re-query + N+1"
```

---

## Task 3: Guard write-on-read di `getShowData()`

**Files:**
- Modify: `app/Services/ProjectService.php:64-65`

Saat ini setiap request `show` melakukan `UPDATE` walau progress tidak berubah. Update hanya bila nilainya benar-benar berubah.

- [ ] **Step 1: Terapkan perubahan**

Ganti baris 64-65 di `app/Services/ProjectService.php`:

```php
$project = $this->projectRepository->findWithRelationsForShow($encoded);

$progress = $project->calculateProgress();
if (abs((float) $project->progress - $progress) > 0.001) {
    $project->update(['progress' => $progress]);
}
```

- [ ] **Step 2: Verifikasi manual (tidak ada query AVG per task + tidak ada UPDATE bila tak berubah)**

Karena suite Feature tidak bisa boot, verifikasi via tinker terhadap DB dev. Run:

```bash
php artisan tinker --execute="
\DB::enableQueryLog();
app(\App\Services\\ProjectService::class)->getShowData('<ENCODED_PROJECT_ID>');
\$log = \DB::getQueryLog();
echo 'total queries: '.count(\$log).PHP_EOL;
echo 'update queries: '.count(array_filter(\$log, fn(\$q)=>str_starts_with(strtolower(trim(\$q['query'])),'update'))).PHP_EOL;
echo 'avg queries: '.count(array_filter(\$log, fn(\$q)=>str_contains(strtolower(\$q['query']),'avg('))).PHP_EOL;
"
```

Ganti `<ENCODED_PROJECT_ID>` dengan encoded id project yang punya banyak task + subtask.
Expected: `avg queries: 0`; jalankan dua kali berturut-turut — pemanggilan kedua `update queries: 0` (progress sudah sinkron).

- [ ] **Step 3: Commit**

```bash
git add app/Services/ProjectService.php
git commit -m "perf(project): skip progress write on show when unchanged"
```

> Out of scope (catat untuk perbaikan lanjutan, jangan dikerjakan sekarang): memindahkan rekalkulasi progress ke `TaskObserver` (saat task saved/deleted) agar endpoint `show` murni read-only.

---

## Task 4: Perbaiki query liar + null-safety di `getAuthUserPolicy()`

**Files:**
- Modify: `app/Repositories/ProjectRepository.php:65`
- Modify: `app/Services/ProjectService.php:217-224`

`->role()->first()->config` menembak DB lagi (mengabaikan relasi `role` yang sudah dimuat) dan rawan null pointer. Muat `config` ikut eager load lalu pakai relasi yang sudah dimuat.

- [ ] **Step 1: Tambahkan `config` ke eager load role**

Di `app/Repositories/ProjectRepository.php`, dalam `findWithRelationsForShow()` (baris 65), ubah:

```php
'projectMembers.role:id,name',
```

menjadi:

```php
'projectMembers.role:id,name,config',
```

- [ ] **Step 2: Pakai relasi yang sudah dimuat + null-safe penuh**

Di `app/Services/ProjectService.php`, ganti isi `getAuthUserPolicy()` bagian baris 217-224:

```php
        $config = $project->projectMembers
            ->firstWhere('user.id', Auth::id())
            ?->role?->config;

        return $config ? ConfigData::from($config) : null;
```

- [ ] **Step 3: Verifikasi manual**

```bash
php artisan tinker --execute="
\DB::enableQueryLog();
app(\App\Services\\ProjectService::class)->getShowData('<ENCODED_PROJECT_ID>');
\$log = \DB::getQueryLog();
echo 'queries to ms_project_roles: '.count(array_filter(\$log, fn(\$q)=>str_contains(strtolower(\$q['query']),'ms_project_role'))).PHP_EOL;
"
```

Login (`actingAs`) sebagai member non-super-admin sebelum memanggil bila perlu. Expected: tidak ada query tambahan ke `ms_project_roles` setelah eager load awal (hanya 1 dari `with`), dan tidak ada error walau member tanpa role.

- [ ] **Step 4: Commit**

```bash
git add app/Repositories/ProjectRepository.php app/Services/ProjectService.php
git commit -m "perf(project): resolve member role policy from eager-loaded relation"
```

---

## Self-Review Checklist (jalankan setelah semua task)

- [ ] `php artisan test --testsuite=Unit` hijau (4 test progress baru + test lama).
- [ ] Verifikasi tinker Task 3: `avg queries: 0`, dan `update queries: 0` pada pemanggilan kedua.
- [ ] Verifikasi tinker Task 4: tidak ada query ulang ke `ms_project_roles`, tidak ada error untuk member tanpa role.
- [ ] Konsistensi nama: method tetap `calculateProgress()` di kedua model; signature tidak berubah (`: float`).
- [ ] Tidak ada perubahan perilaku output `getShowData()` selain `progress` yang tetap tersinkron.
