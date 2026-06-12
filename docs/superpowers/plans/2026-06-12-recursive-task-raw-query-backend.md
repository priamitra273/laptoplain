# Recursive Task Tree via Raw PostgreSQL CTE — Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ganti pemuatan pohon task project (saat ini rekursif via Eloquent `withRecursive`, N-per-level) dengan **satu** raw PostgreSQL recursive CTE untuk baris task, lalu rakit relasi secara batch di aplikasi dan petakan ke DTO Laravel Data `ProjectTaskData`. Sprint & backlog memakai DTO yang sama. Backend saja; migrasi frontend ada di plan terpisah.

**Architecture:** `ProjectRepository::getTaskTree()` menjalankan CTE (baris saja) → `Task::hydrate()` → `->load([relasi flat])` → `ProjectTaskData::treeFromTasks()` merakit pohon (O(n), murni, bisa diuji tanpa DB) → `DataCollection<ProjectTaskData>`. `findWithRelationsForShow()` berhenti memuat pohon rekursif tapi tetap memuat `tasks` minimal (top-level + children) demi `calculateProgress()`. Field komputasi (`is_overdue`, `completed_at`) pindah ke `ProjectTaskData::fromModel()`. Tipe TS digenerate dari DTO.

**Tech Stack:** Laravel 12, Eloquent, `spatie/laravel-data` ^4.21, `spatie/laravel-typescript-transformer` ^3, Pest 3, PostgreSQL (CTE standar, juga jalan di sqlite 3.8.3+).

---

## ⚠️ Catatan lingkungan (baca sebelum mulai)

- **DB test = PostgreSQL lokal.** Suite Feature TIDAK bisa boot di sqlite `:memory:` karena migrasi `2026_03_26_071450_make_sprint_fields_nullable.php` memakai `ALTER TABLE ... DROP CONSTRAINT` (sintaks Postgres). Karena itu Task 1 mengatur DB test ke Postgres lokal. **Logika inti (DTO + perakitan pohon) diuji via Unit test tanpa DB** sehingga tetap bisa diverifikasi walau DB test belum siap. Feature test (integrasi CTE) dijalankan setelah Task 1.
- **Parity field komputasi:** salin logika persis dari `ProjectService::formatProjectTasks()` lama (lihat Task 2) agar output setara.
- **Jaga perf progress:** JANGAN hapus pemuatan `tasks` dari `findWithRelationsForShow()` mentah-mentah — `Project::calculateProgress()` memakainya in-memory. Ganti hanya `withRecursive()` (pohon berat) dengan relasi `tasks` default (top-level + `children`).
- **DRY relasi:** daftar relasi flat dipakai bertiga (tree/sprint/backlog) → satu method `taskTreeRelations()`.
- **created_by/updated_by/deleted_by:** `LogUsers` mengisinya dengan `Auth::id()` (numerik) walau kolomnya string → DTO memakai `?int` (TS `number | null`), TIDAK di-encode Sqids (regex `/_id$/` tak match `_by`).

## File Structure

| File | Aksi | Tanggung jawab |
|------|------|----------------|
| `.env.testing` | Create | Arahkan koneksi test ke Postgres lokal (creds lokal; jangan commit secret). |
| `app/Data/Task/ProjectTaskData.php` | Create | DTO `#[TypeScript]` penuh sesuai interface `Task`; `fromModel()` + `treeFromTasks()` + field komputasi. |
| `tests/Unit/ProjectTaskDataTest.php` | Create | Unit test `fromModel()` (mapping + is_overdue/completed_at) tanpa DB. |
| `tests/Unit/ProjectTaskTreeAssemblyTest.php` | Create | Unit test `treeFromTasks()` (nesting/urutan, sub_task==sub_task_recursive) tanpa DB. |
| `app/Repositories/ProjectRepository.php` | Modify | Tambah `getTaskTree()` + `taskTreeRelations()`; ubah `findWithRelationsForShow()`, `getActiveSprints()`, `getBacklogTasks()`. |
| `app/Services/ProjectService.php` | Modify | `getShowData()` pakai method baru; hapus `formatProjectTasks()`; tambah `formatSprints()`. |
| `tests/Feature/Project/ProjectDetailTaskTreeTest.php` | Create | Feature test (pgsql): struktur, soft-delete, query-count konstan, bentuk field. |
| `resources/js/types/generated.d.ts` | Generated | Hasil `php artisan typescript:transform` (berisi `App.Data.Task.ProjectTaskData`). |

---

## Task 1: Setup DB test PostgreSQL

**Files:**
- Create: `.env.testing`

> Tujuan: memulihkan boot suite Feature (saat ini SEMUA feature test gagal di sqlite). Jika DB test belum bisa disiapkan sekarang, **lewati verifikasi feature test** dan tetap kerjakan Task 2–3 (Unit, tanpa DB); jalankan feature test (Task 4–5) setelah DB siap.

- [ ] **Step 1: Buat `.env.testing`**

Isi dengan koneksi Postgres lokal (sesuaikan host/port/user/password/database lokalmu; gunakan database khusus test yang boleh di-`RefreshDatabase`):

```dotenv
APP_ENV=testing
APP_KEY=
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=project_management_test
DB_USERNAME=postgres
DB_PASSWORD=
BCRYPT_ROUNDS=4
CACHE_STORE=array
MAIL_MAILER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
PULSE_ENABLED=false
TELESCOPE_ENABLED=false
```

Catatan: `phpunit.xml` saat ini meng-set `DB_CONNECTION=sqlite`/`:memory:` lewat `<env>`. `<env>` di phpunit.xml hanya berlaku bila variabel belum ada di environment; Laravel memuat `.env.testing` saat `APP_ENV=testing`. Bila DB tetap terbaca sqlite, hapus/komentari dua baris berikut di `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 2: Buat database test & generate key**

```bash
createdb -h 127.0.0.1 -p 5432 -U postgres project_management_test
php artisan key:generate --env=testing
```

- [ ] **Step 3: Verifikasi boot suite Feature**

Run: `php artisan test tests/Feature/DashboardTest.php`
Expected: test boot tanpa `QueryException` migrasi (lulus / redirect sesuai logika). Bila masih `near "CONSTRAINT": syntax error`, berarti masih memakai sqlite — ulangi Step 1.

- [ ] **Step 4: Commit (tanpa secret)**

`.env.testing` umumnya di-gitignore. Jika repo melacaknya, pastikan tanpa password produksi. Bila tidak ada perubahan file yang dilacak, lewati commit ini.

---

## Task 2: `ProjectTaskData` DTO + `fromModel()` (Unit TDD)

**Files:**
- Create: `tests/Unit/ProjectTaskDataTest.php`
- Create: `app/Data/Task/ProjectTaskData.php`

- [ ] **Step 1: Tulis test yang gagal**

Create `tests/Unit/ProjectTaskDataTest.php`:

```php
<?php

use App\Data\Task\ProjectTaskData;
use App\Models\MsTaskStatus;
use App\Models\Task;
use Spatie\LaravelData\DataCollection;

function makeLeafTask(array $attrs = []): Task
{
    $task = new Task(array_merge([
        'title' => 'Leaf',
        'progress' => 10,
        'sequence_number' => 1,
        'is_archived' => false,
    ], $attrs));
    $task->id = $attrs['id'] ?? 1;
    $task->status_id = $attrs['status_id'] ?? 1;
    $task->priority_id = $attrs['priority_id'] ?? 1;
    $task->type_id = $attrs['type_id'] ?? 1;
    $task->project_id = $attrs['project_id'] ?? 1;
    // Relasi di-set manual agar fromModel tidak menyentuh DB.
    $task->setRelation('users', collect());
    $task->setRelation('tags', collect());
    $task->setRelation('media', collect());

    return $task;
}

function emptyChildren(): DataCollection
{
    return ProjectTaskData::collect([], DataCollection::class);
}

it('maps scalar fields and casts ids/progress', function () {
    $task = makeLeafTask(['id' => 7, 'title' => 'Hello', 'progress' => 42]);

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->id)->toBe(7)
        ->and($data->title)->toBe('Hello')
        ->and($data->progress)->toBe(42.0)
        ->and($data->is_archived)->toBeFalse();
});

it('marks completed task with completed_at and overdue rule', function () {
    $task = makeLeafTask([
        'due_date' => '2026-01-01',
    ]);
    $task->updated_at = \Carbon\Carbon::parse('2026-01-05 10:00:00');
    $task->setRelation('status', new MsTaskStatus(['name' => 'Completed']));

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->completed_at)->not->toBeNull()
        // updated_at (Jan 5) > due_date end-of-day (Jan 1) → overdue
        ->and($data->is_overdue)->toBeTrue();
});

it('computes overdue for non-completed past-due task', function () {
    $task = makeLeafTask(['due_date' => '2020-01-01']);
    $task->setRelation('status', new MsTaskStatus(['name' => 'In Progress']));

    $data = ProjectTaskData::fromModel($task, emptyChildren());

    expect($data->completed_at)->toBeNull()
        ->and($data->is_overdue)->toBeTrue();
});

it('sets sub_task and sub_task_recursive to the same children collection', function () {
    $parent = makeLeafTask(['id' => 1]);
    $childData = ProjectTaskData::fromModel(makeLeafTask(['id' => 2, 'parent_id' => 1]), emptyChildren());
    $children = ProjectTaskData::collect([$childData], DataCollection::class);

    $data = ProjectTaskData::fromModel($parent, $children);

    expect($data->sub_task)->toBe($data->sub_task_recursive)
        ->and($data->sub_task->count())->toBe(1);
});
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `php artisan test tests/Unit/ProjectTaskDataTest.php`
Expected: FAIL — `Class "App\Data\Task\ProjectTaskData" not found`.

- [ ] **Step 3: Implementasi DTO**

Create `app/Data/Task/ProjectTaskData.php`:

```php
<?php

namespace App\Data\Task;

use App\Data\MediaData;
use App\Data\UserData;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class ProjectTaskData extends Data
{
    private const COMPLETED_STATUSES = ['COMPLETED', 'FINISHED'];

    public function __construct(
        #[TypeScriptType('string')] public int $id,
        #[TypeScriptType('string|null')] public ?int $owned_id,
        #[TypeScriptType('string|null')] public ?int $parent_id,
        #[TypeScriptType('string|null')] public ?int $status_id,
        #[TypeScriptType('string|null')] public ?int $priority_id,
        #[TypeScriptType('string|null')] public ?int $type_id,
        public ?int $created_by,
        public ?int $updated_by,
        public ?int $deleted_by,
        public ?string $emoji,
        public string $title,
        public ?string $description,
        public ?string $start_date,
        public ?string $due_date,
        public float $progress,
        public ?int $story_points,
        public ?int $sequence_number,
        public bool $is_archived,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        public ?string $completed_at,
        public bool $is_overdue,
        #[TypeScriptType('string')] public int $project_id,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskTypeData $type,
        public ?TaskCategoryData $category,
        public ?UserData $creator,
        /** @var DataCollection<int, UserData> */
        #[TypeScriptType('Array<UserData>')]
        public DataCollection $users,
        /** @var DataCollection<int, TagData> */
        #[TypeScriptType('Array<TagData>')]
        public DataCollection $tags,
        /** @var DataCollection<int, MediaData> */
        #[TypeScriptType('Array<MediaData>')]
        public DataCollection $media,
        /** @var DataCollection<int, ProjectTaskData> */
        #[TypeScriptType('Array<ProjectTaskData>')]
        public DataCollection $sub_task,
        /** @var DataCollection<int, ProjectTaskData> */
        #[TypeScriptType('Array<ProjectTaskData>')]
        public DataCollection $sub_task_recursive,
        public ?bool $is_assigned = null,
        public ?bool $is_created_by_me = null,
    ) {}

    public static function fromModel(Task $task, DataCollection $children): self
    {
        $isCompleted = in_array(
            strtoupper($task->status?->name ?? ''),
            self::COMPLETED_STATUSES,
            true
        );

        $completedAt = $isCompleted ? $task->updated_at?->toJSON() : null;

        $isOverdue = $isCompleted
            ? Carbon::parse($task->updated_at)->isAfter(Carbon::parse($task->due_date)->endOfDay())
            : Carbon::parse($task->due_date)->endOfDay()->isPast();

        return new self(
            id: (int) $task->id,
            owned_id: $task->owned_id !== null ? (int) $task->owned_id : null,
            parent_id: $task->parent_id !== null ? (int) $task->parent_id : null,
            status_id: $task->status_id !== null ? (int) $task->status_id : null,
            priority_id: $task->priority_id !== null ? (int) $task->priority_id : null,
            type_id: $task->type_id !== null ? (int) $task->type_id : null,
            created_by: is_numeric($task->created_by) ? (int) $task->created_by : null,
            updated_by: is_numeric($task->updated_by) ? (int) $task->updated_by : null,
            deleted_by: is_numeric($task->deleted_by) ? (int) $task->deleted_by : null,
            emoji: $task->emoji,
            title: (string) $task->title,
            description: $task->description,
            start_date: self::asString($task->start_date),
            due_date: self::asString($task->due_date),
            progress: (float) $task->progress,
            story_points: $task->story_points !== null ? (int) $task->story_points : null,
            sequence_number: $task->sequence_number !== null ? (int) $task->sequence_number : null,
            is_archived: (bool) $task->is_archived,
            created_at: $task->created_at?->toJSON(),
            updated_at: $task->updated_at?->toJSON(),
            deleted_at: $task->deleted_at?->toJSON(),
            completed_at: $completedAt,
            is_overdue: $isOverdue,
            project_id: (int) $task->project_id,
            status: $task->relationLoaded('status') && $task->status ? TaskStatusData::from($task->status) : null,
            priority: $task->relationLoaded('priority') && $task->priority ? TaskPriorityData::from($task->priority) : null,
            type: $task->relationLoaded('type') && $task->type ? TaskTypeData::from($task->type) : null,
            category: $task->relationLoaded('category') && $task->category ? TaskCategoryData::from($task->category) : null,
            creator: $task->relationLoaded('creator') && $task->creator ? UserData::fromModel($task->creator) : null,
            users: $task->relationLoaded('users')
                ? UserData::collect($task->users, DataCollection::class)
                : UserData::collect([], DataCollection::class),
            tags: $task->relationLoaded('tags')
                ? TagData::collect($task->tags, DataCollection::class)
                : TagData::collect([], DataCollection::class),
            media: $task->relationLoaded('media')
                ? MediaData::collect($task->media, DataCollection::class)
                : MediaData::collect([], DataCollection::class),
            sub_task: $children,
            sub_task_recursive: $children,
        );
    }

    /**
     * Build a tree of ProjectTaskData from a flat collection of Task models.
     * Pure (no DB). Input order is preserved within each parent group.
     *
     * @param  SupportCollection<int, Task>  $tasks
     * @return DataCollection<int, ProjectTaskData>
     */
    public static function treeFromTasks(SupportCollection $tasks): DataCollection
    {
        $grouped = $tasks->groupBy(fn (Task $t) => (string) ($t->parent_id ?? ''));

        $build = function (Task $task) use (&$build, $grouped): ProjectTaskData {
            $childModels = $grouped->get((string) $task->id, collect());
            $childrenData = $childModels->map(fn (Task $child) => $build($child))->values();

            return self::fromModel($task, self::collect($childrenData, DataCollection::class));
        };

        $roots = $grouped->get('', collect())->map(fn (Task $t) => $build($t))->values();

        return self::collect($roots, DataCollection::class);
    }

    private static function asString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `php artisan test tests/Unit/ProjectTaskDataTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Pint + Commit**

```bash
vendor/bin/pint --dirty
git add app/Data/Task/ProjectTaskData.php tests/Unit/ProjectTaskDataTest.php
git commit -m "feat(task): add ProjectTaskData DTO with computed overdue/completed fields"
```

---

## Task 3: Perakitan pohon `treeFromTasks()` (Unit TDD)

**Files:**
- Create: `tests/Unit/ProjectTaskTreeAssemblyTest.php`

> `treeFromTasks()` sudah ditulis di Task 2; task ini menambah test perakitan eksplisit (nesting 3 level + urutan) tanpa DB.

- [ ] **Step 1: Tulis test yang gagal-lalu-lulus**

Create `tests/Unit/ProjectTaskTreeAssemblyTest.php`:

```php
<?php

use App\Data\Task\ProjectTaskData;
use App\Models\Task;

function flatTask(int $id, ?int $parentId, int $seq): Task
{
    $task = new Task(['title' => "T{$id}", 'progress' => 0, 'sequence_number' => $seq, 'is_archived' => false]);
    $task->id = $id;
    $task->parent_id = $parentId;
    $task->project_id = 1;
    $task->setRelation('users', collect());
    $task->setRelation('tags', collect());
    $task->setRelation('media', collect());

    return $task;
}

it('assembles a 3-level tree from a flat ordered collection', function () {
    // root(1) → child(2) → grandchild(3); plus second root(4)
    $flat = collect([
        flatTask(1, null, 1),
        flatTask(4, null, 2),
        flatTask(2, 1, 1),
        flatTask(3, 2, 1),
    ]);

    $tree = ProjectTaskData::treeFromTasks($flat);

    expect($tree)->toHaveCount(2);

    $root = $tree->toCollection()->firstWhere('id', 1);
    expect($root->sub_task_recursive)->toHaveCount(1);

    $child = $root->sub_task_recursive->toCollection()->first();
    expect($child->id)->toBe(2)
        ->and($child->sub_task_recursive)->toHaveCount(1)
        ->and($child->sub_task_recursive->toCollection()->first()->id)->toBe(3);
});

it('keeps roots ordered by input order', function () {
    $flat = collect([flatTask(10, null, 1), flatTask(20, null, 2)]);

    $tree = ProjectTaskData::treeFromTasks($flat);

    expect($tree->toCollection()->pluck('id')->all())->toBe([10, 20]);
});

it('returns empty collection for empty input', function () {
    expect(ProjectTaskData::treeFromTasks(collect()))->toHaveCount(0);
});
```

- [ ] **Step 2: Jalankan test, pastikan LULUS**

Run: `php artisan test tests/Unit/ProjectTaskTreeAssemblyTest.php`
Expected: PASS (3 tests). Bila gagal pada lookup key, periksa cast key `(string)` di `treeFromTasks()`.

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/ProjectTaskTreeAssemblyTest.php
git commit -m "test(task): cover ProjectTaskData tree assembly"
```

---

## Task 4: `ProjectRepository::getTaskTree()` (raw CTE) + ubah `findWithRelationsForShow()`

**Files:**
- Modify: `app/Repositories/ProjectRepository.php`
- Create: `tests/Feature/Project/ProjectDetailTaskTreeTest.php`

- [ ] **Step 1: Tambah `use` + `taskTreeRelations()` + `getTaskTree()`**

Di `app/Repositories/ProjectRepository.php`, tambahkan import di blok `use`:

```php
use App\Data\Task\ProjectTaskData;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;
```

Tambahkan dua method (mis. setelah `findWithRelationsForShow()`):

```php
/**
 * Flat relation list shared by tree, sprint, and backlog task loading.
 *
 * @return array<int|string, string|\Closure>
 */
protected function taskTreeRelations(): array
{
    return [
        'status:id,name,severity,score',
        'priority:id,name,severity',
        'type:id,name,severity',
        'category:id,name,icon,severity',
        'users:id,name,email',
        'users.media',
        'tags:id,name,severity',
        'creator:id,name,email',
        'creator.media',
        'media' => fn ($q) => $q->where('collection_name', 'attachments'),
    ];
}

/**
 * Load the project's full task tree using a single recursive CTE for the rows,
 * then batch-load relations in the app (query count independent of tree depth).
 *
 * @return DataCollection<int, ProjectTaskData>
 */
public function getTaskTree(int $projectId): DataCollection
{
    $sql = <<<'SQL'
        WITH RECURSIVE task_tree AS (
            SELECT t.id, t.owned_id, t.parent_id, t.status_id, t.priority_id, t.type_id,
                   t.task_category_id, t.created_by, t.updated_by, t.deleted_by,
                   t.emoji, t.title, t.description, t.start_date, t.due_date,
                   t.progress, t.story_points, t.sequence_number, t.is_archived,
                   t.project_id, t.created_at, t.updated_at, t.deleted_at, 0 AS depth
            FROM tasks t
            WHERE t.project_id = :projectId
              AND t.parent_id IS NULL
              AND t.deleted_at IS NULL
            UNION ALL
            SELECT c.id, c.owned_id, c.parent_id, c.status_id, c.priority_id, c.type_id,
                   c.task_category_id, c.created_by, c.updated_by, c.deleted_by,
                   c.emoji, c.title, c.description, c.start_date, c.due_date,
                   c.progress, c.story_points, c.sequence_number, c.is_archived,
                   c.project_id, c.created_at, c.updated_at, c.deleted_at, tt.depth + 1
            FROM tasks c
            JOIN task_tree tt ON c.parent_id = tt.id
            WHERE c.deleted_at IS NULL
        )
        SELECT * FROM task_tree
        ORDER BY depth, sequence_number, id
        SQL;

    $rows = DB::select($sql, ['projectId' => $projectId]);

    /** @var \Illuminate\Database\Eloquent\Collection<int, Task> $tasks */
    $tasks = Task::hydrate($rows);

    if ($tasks->isEmpty()) {
        return ProjectTaskData::collect([], DataCollection::class);
    }

    $tasks->load($this->taskTreeRelations());

    return ProjectTaskData::treeFromTasks($tasks->toBase());
}
```

> Catatan: `:projectId` di bind via named parameter. `Task::hydrate()` melewati global scope SoftDeletes — itulah kenapa `deleted_at IS NULL` direplikasi di anchor & recursive. `toBase()` mengubah Eloquent Collection menjadi Support Collection untuk `treeFromTasks()`.

- [ ] **Step 2: Ubah `findWithRelationsForShow()` — hentikan pohon rekursif, pertahankan progress**

Di `findWithRelationsForShow()`, ganti blok:

```php
                'tasks' => function ($query) {
                    $query->withRecursive()
                        ->orderBy('sequence_number')
                        ->orderBy('id');
                },
```

menjadi (relasi `tasks()` sudah `whereNull('parent_id')->with('children')`, cukup untuk `calculateProgress()`):

```php
                'tasks',
```

- [ ] **Step 3: Tulis feature test pohon (butuh DB test dari Task 1)**

Create `tests/Feature/Project/ProjectDetailTaskTreeTest.php`:

```php
<?php

use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Repositories\ProjectRepository;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
    ]);

    $this->user = User::factory()->create();

    $this->status = MsTaskStatus::query()->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => \App\Models\MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => \App\Models\MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Tree Project',
        'emoji' => '🌳',
        'progress' => 0,
    ]);
});

/**
 * Helper: create a task under the project.
 */
function makeTask(int $projectId, array $attrs): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

it('loads a recursive tree, excludes soft-deleted tasks, and nests correctly', function () {
    $root = makeTask($this->project->id, [
        'title' => 'Root', 'status_id' => $this->status->id, 'priority_id' => $this->priority->id,
        'type_id' => $this->type->id, 'task_category_id' => $this->category->id, 'sequence_number' => 1,
    ]);
    $child = makeTask($this->project->id, ['title' => 'Child', 'parent_id' => $root->id, 'sequence_number' => 1]);
    $grandchild = makeTask($this->project->id, ['title' => 'Grandchild', 'parent_id' => $child->id, 'sequence_number' => 1]);
    $deleted = makeTask($this->project->id, ['title' => 'Deleted', 'parent_id' => $root->id, 'sequence_number' => 2]);

    // assign user (avatar) + soft-delete one pivot
    $root->users()->attach($this->user->id);
    $deleted->delete();

    $tree = app(ProjectRepository::class)->getTaskTree($this->project->id)->toCollection();

    expect($tree)->toHaveCount(1);
    $rootData = $tree->first();
    expect($rootData->id)->toBe($root->id)
        ->and($rootData->sub_task_recursive)->toHaveCount(1) // 'Deleted' excluded
        ->and($rootData->users)->toHaveCount(1);

    $childData = $rootData->sub_task_recursive->toCollection()->first();
    expect($childData->id)->toBe($child->id)
        ->and($childData->sub_task_recursive->toCollection()->first()->id)->toBe($grandchild->id);
});

it('keeps relation query count constant regardless of tree depth', function () {
    // shallow: 2 roots, no children
    $shallow = Project::create([
        'status_id' => \App\Models\MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => \App\Models\MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id, 'owned_id' => $this->user->id, 'title' => 'Shallow', 'emoji' => '•', 'progress' => 0,
    ]);
    makeTask($shallow->id, ['title' => 'A', 'sequence_number' => 1]);
    makeTask($shallow->id, ['title' => 'B', 'sequence_number' => 2]);

    // deep: 5-level chain
    $deep = Project::create([
        'status_id' => \App\Models\MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => \App\Models\MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id, 'owned_id' => $this->user->id, 'title' => 'Deep', 'emoji' => '•', 'progress' => 0,
    ]);
    $parentId = null;
    for ($i = 0; $i < 5; $i++) {
        $parentId = makeTask($deep->id, ['title' => "L{$i}", 'parent_id' => $parentId, 'sequence_number' => 1])->id;
    }

    $repo = app(ProjectRepository::class);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $repo->getTaskTree($shallow->id);
    $shallowCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $repo->getTaskTree($deep->id);
    $deepCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($deepCount)->toBe($shallowCount);
});
```

- [ ] **Step 4: Jalankan feature test**

Run: `php artisan test tests/Feature/Project/ProjectDetailTaskTreeTest.php`
Expected: PASS. Bila `Project::create` gagal karena kolom NOT NULL yang belum diisi (mis. `code`), tambahkan kolom itu ke payload `Project::create` sesuai pesan error (schema `projects` hasil banyak migrasi). Bila gagal boot DB → selesaikan Task 1 dulu.

- [ ] **Step 5: Pint + Commit**

```bash
vendor/bin/pint --dirty
git add app/Repositories/ProjectRepository.php tests/Feature/Project/ProjectDetailTaskTreeTest.php
git commit -m "feat(project): load task tree via raw recursive CTE + batch relations"
```

---

## Task 5: Wire `ProjectService::getShowData()` (tasks/backlog/sprints) + hapus `formatProjectTasks`

**Files:**
- Modify: `app/Services/ProjectService.php`
- Modify: `app/Repositories/ProjectRepository.php` (getActiveSprints, getBacklogTasks)

- [ ] **Step 1: `getBacklogTasks()` → `DataCollection<ProjectTaskData>` + relasi seragam**

Di `ProjectRepository::getBacklogTasks()`, ganti seluruh isi method menjadi:

```php
public function getBacklogTasks(int $projectId): DataCollection
{
    $tasks = Task::with($this->taskTreeRelations())
        ->where('project_id', $projectId)
        ->where(function ($q) {
            $q->whereNull('parent_id')
                ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
        })
        ->doesntHave('sprints')
        ->orderBy('id')
        ->get();

    return ProjectTaskData::collect(
        $tasks->map(fn (Task $task) => ProjectTaskData::fromModel(
            $task,
            ProjectTaskData::collect([], DataCollection::class)
        )),
        DataCollection::class
    );
}
```

Ubah return type method di signature menjadi `: DataCollection`.

- [ ] **Step 2: `getActiveSprints()` → pakai relasi task seragam**

Di `ProjectRepository::getActiveSprints()`, ganti blok `with([...])` pada relasi `tasks` menjadi `$this->taskTreeRelations()`:

```php
            'tasks' => function ($q) {
                $q->with($this->taskTreeRelations())
                    ->where(function ($taskQuery) {
                        $taskQuery->whereNull('parent_id')
                            ->orWhereHas('parent.category', fn ($q) => $q->where('name', 'Epic'));
                    })
                    ->orderBy('sequence_number')
                    ->orderBy('id');
            },
```

(Sisanya — `status:id,name,severity` pada sprint, filter `sprint_status_id`, order — tetap.)

- [ ] **Step 3: Ubah `ProjectService` — import, tasks/backlog/sprints, hapus formatProjectTasks**

Di `app/Services/ProjectService.php`:

a. Tambah import:

```php
use App\Data\Task\ProjectTaskData;
use App\Models\ProjectSprint;
```

b. Setelah guard progress (`$project->update(...)` block), tambahkan agar payload `project` tidak menyertakan relasi `tasks` internal:

```php
        $project->unsetRelation('tasks');
```

c. Ganti baris `'tasks' => $this->formatProjectTasks($project->tasks),` menjadi:

```php
            'tasks' => $this->projectRepository->getTaskTree($projectId)->toArray(),
```

d. Ganti baris sprints/backlog:

```php
            'sprints' => $this->formatSprints($this->projectRepository->getActiveSprints($projectId)),
            'backlog' => $this->projectRepository->getBacklogTasks($projectId)->toArray(),
```

e. Hapus method `formatProjectTasks()` sepenuhnya dan tambahkan `formatSprints()`:

```php
    /**
     * Map each active sprint to an array, with its tasks as ProjectTaskData.
     *
     * @param  Collection<int, ProjectSprint>  $sprints
     * @return array<int, array<string, mixed>>
     */
    private function formatSprints(Collection $sprints): array
    {
        return $sprints->map(function (ProjectSprint $sprint) {
            $data = $sprint->toArray();
            $data['tasks'] = $sprint->tasks
                ->map(fn (Task $task) => ProjectTaskData::fromModel(
                    $task,
                    ProjectTaskData::collect([], DataCollection::class)
                )->toArray())
                ->values()
                ->all();

            return $data;
        })->all();
    }
```

f. Hapus import yang tak lagi dipakai bila dilaporkan pint/IDE: `use App\Data\MediaData;` dan `use Carbon\Carbon;` (keduanya hanya dipakai oleh `formatProjectTasks` lama — verifikasi tidak dipakai di tempat lain sebelum menghapus).

- [ ] **Step 4: Tambah assertion show-endpoint ke feature test**

Tambahkan di `tests/Feature/Project/ProjectDetailTaskTreeTest.php`:

```php
it('renders project/Detail with ProjectTaskData-shaped tasks and constant relation queries', function () {
    $root = makeTask($this->project->id, [
        'title' => 'Root', 'status_id' => $this->status->id, 'priority_id' => $this->priority->id,
        'type_id' => $this->type->id, 'task_category_id' => $this->category->id, 'sequence_number' => 1, 'due_date' => '2026-01-01',
    ]);
    makeTask($this->project->id, ['title' => 'Child', 'parent_id' => $root->id, 'sequence_number' => 1]);
    $root->users()->attach($this->user->id);

    $encoded = \App\Facades\Sqids::encode($this->project->id);

    $response = $this->actingAs($this->user)->get("/project/{$encoded}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('project/Detail')
        ->has('tasks', 1)
        ->has('tasks.0.sub_task_recursive', 1)
        ->has('tasks.0.status.score')          // status carries score
        ->has('tasks.0.users.0.avatar_url')    // users carry avatar, no pivot
        ->where('tasks.0.is_overdue', true)     // past-due, not completed
        ->missing('tasks.0.users.0.pivot')
    );
});
```

> Sesuaikan path route bila berbeda (lihat `routes/web.php` untuk nama route `project.show`). Bila ada middleware/policy menolak akses, pastikan `$this->user` punya membership/izin yang relevan (tambah `ProjectMember` + role bila perlu).

- [ ] **Step 5: Jalankan feature test + Unit**

Run: `php artisan test tests/Feature/Project/ProjectDetailTaskTreeTest.php tests/Unit/ProjectTaskDataTest.php tests/Unit/ProjectTaskTreeAssemblyTest.php`
Expected: PASS semua.

- [ ] **Step 6: Pint + Commit**

```bash
vendor/bin/pint --dirty
git add app/Services/ProjectService.php app/Repositories/ProjectRepository.php tests/Feature/Project/ProjectDetailTaskTreeTest.php
git commit -m "refactor(project): serve task tree/sprints/backlog as ProjectTaskData"
```

---

## Task 6: Generate tipe TypeScript

**Files:**
- Generated: `resources/js/types/generated.d.ts`

- [ ] **Step 1: Jalankan transformer**

Run: `php artisan typescript:transform`
Expected: sukses; Prettier memformat output (butuh node/prettier terpasang).

- [ ] **Step 2: Verifikasi `ProjectTaskData` muncul**

Run: `grep -n "ProjectTaskData" resources/js/types/generated.d.ts`
Expected: ada `export type ProjectTaskData = { ... }` di namespace `App.Data.Task`, dengan `id: string`, `created_by: number | null`, `sub_task_recursive: App.Data.Task.ProjectTaskData[]`, `users: App.Data.UserData[]`, `status: App.Data.Task.TaskStatusData | null`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/types/generated.d.ts
git commit -m "chore(types): generate ProjectTaskData TypeScript type"
```

---

## Task 7: Verifikasi penuh backend + self-review

- [ ] **Step 1: Jalankan test terkait**

Run: `php artisan test --filter=ProjectTaskData` lalu `php artisan test tests/Feature/Project/ProjectDetailTaskTreeTest.php tests/Unit`
Expected: hijau.

- [ ] **Step 2: Pint seluruh perubahan**

Run: `vendor/bin/pint --dirty`
Expected: tidak ada perubahan tersisa (atau auto-fix bersih).

- [ ] **Step 3: Self-review checklist**

- [ ] `getTaskTree()` = 1 query CTE + N relasi konstan (dibuktikan test depth-invariance).
- [ ] `findWithRelationsForShow()` tetap memuat `tasks` (top-level+children) → `Project::calculateProgress()` tidak re-query (cek tidak ada query `avg(` saat show).
- [ ] Soft-deleted task & pivot soft-deleted tidak muncul.
- [ ] `formatProjectTasks` terhapus; tidak ada referensi tersisa (`grep -rn formatProjectTasks app/`).
- [ ] Import tak terpakai di `ProjectService` (MediaData, Carbon) sudah dibersihkan bila memang tak dipakai.
- [ ] Tipe `App.Data.Task.ProjectTaskData` tergenerate dengan benar.

- [ ] **Step 4: Tawarkan run suite penuh ke user**

Tanyakan apakah perlu `php artisan test` (seluruh suite) untuk memastikan tidak ada regresi.

---

## Di luar cakupan plan ini (plan frontend terpisah)

- Migrasi tipe frontend (sempit) ke `App.Data.Task.ProjectTaskData` pada rantai `project/Detail` (props `tasks`/`backlog`, `Sprint.tasks`, komponen render tree/backlog/sprint/kanban). Interface `Task` hand-written DIPERTAHANKAN untuk konteks lain (detail task, dashboard, form).
- `npx vue-tsc --noEmit` hijau setelah migrasi.
- Raw SQL untuk sprint/backlog (tetap Eloquent).
