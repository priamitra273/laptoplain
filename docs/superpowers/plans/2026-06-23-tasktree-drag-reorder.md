# TaskTree Drag-Reorder + Re-parent Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengganti drag-and-drop kolom Title yang rapuh dengan drag native HTML5 dari handle grip yang mendukung reorder urutan sibling sekaligus re-parent (nesting) dalam satu gerakan, dengan optimistic update di frontend.

**Architecture:** Endpoint backend baru `PUT tasks/{task}/move` (parent_id + position) yang menormalkan `sequence_number` per sibling group, terpisah dari `tasks.parent.update` lama (masih dipakai Backlog). Frontend: helper murni `computeMoveTarget` menghitung `{parentId, position}` dari zona drop; `useLocalTaskTree.moveNode` menerapkan perubahan optimistic ke tree lokal; `useTaskDragDrop` dirombak jadi murni interaksi (native DnD + deteksi zona) dan melapor ke `List.vue` lewat emit `move`.

**Tech Stack:** Laravel 12 / PHP 8.4, Pest v3, PostgreSQL; Vue 3 + Inertia v2, PrimeVue 4.3.6 TreeTable, TailwindCSS v3, Vitest v4, Ziggy v2 (runtime `@routes`).

## Global Constraints

- **Testing DB safety:** Test memakai `RefreshDatabase` → `migrate:fresh`. WAJIB pakai `.env.testing`; verifikasi DB ter-resolve adalah database test (disposable) SEBELUM menjalankan test apa pun. Verifikasi: `APP_ENV=testing php artisan tinker --execute="echo config('database.connections.pgsql.database');"`.
- **PHP:** curly braces wajib untuk semua control structure; explicit return type pada semua method; constructor property promotion; hindari `DB::` kecuali untuk transaksi/operasi yang memang butuh (di sini `DB::transaction` boleh).
- **Endpoint lama `tasks.parent.update` TIDAK boleh diubah** (dipakai `useBacklogBoard.ts:222`, `project/task/Backlog.vue:293`, `project/task/Table.vue:347`).
- **Pint:** jalankan `vendor/bin/pint --dirty` sebelum commit perubahan PHP.
- **Pest:** `RefreshDatabase` di-apply global via `tests/Pest.php` (jangan deklarasi ulang `uses()` di file test, ikuti `LazyTaskWriteTest.php`).
- **DB:** pgsql-only; `orderByRaw` harus kompatibel Postgres.
- **Urutan tampil = urutan array `sub_task_recursive`** (`formatTasks` di `useTaskTree.ts:9` tidak menyortir).

---

### Task 1: Backend endpoint `tasks/{task}/move`

**Files:**
- Create: `app/Http/Requests/Task/TaskMoveRequest.php`
- Modify: `app/Http/Controllers/TaskController.php` (tambah method `move`)
- Modify: `app/Services/TaskService.php` (tambah method `move`, tambah import `DB`)
- Modify: `routes/web.php` (tambah route setelah `tasks.parent.update`, ~baris 106)
- Test: `tests/Feature/ProjectLazy/TaskMoveTest.php`

**Interfaces:**
- Produces:
  - Route name `project.tasks.move` → `PUT project/{projectEncoded}/tasks/{task}/move`.
  - `TaskService::move(Task $task, ?int $parentId, int $position): void`.
  - Response JSON `{ success: true, message: string }`.
  - Request body: `parent_id` (nullable, sqids string) + `position` (required integer ≥ 0).

- [ ] **Step 1: Tulis feature test yang gagal**

Create `tests/Feature/ProjectLazy/TaskMoveTest.php`:

```php
<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded FKs resolve (see LazyTaskWriteTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
    ]);

    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->status = MsTaskStatus::query()->where('score', '>', 0)->orderBy('score')->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Move Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function moveTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
    ], $attrs));
}

function moveRoute(string $encoded, Task $task): string
{
    return route('project.tasks.move', [
        'projectEncoded' => $encoded,
        'task' => Sqids::encode($task->id),
    ]);
}

it('reorders a root sibling to the end and normalizes sequence numbers', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);
    $c = moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 2]);

    // siblings excluding A = [B, C]; insert at index 2 -> [B, C, A]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 2])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect((int) $b->fresh()->sequence_number)->toBe(0)
        ->and((int) $c->fresh()->sequence_number)->toBe(1)
        ->and((int) $a->fresh()->sequence_number)->toBe(2);
});

it('reorders a root sibling to the front', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);
    $c = moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 2]);

    // siblings excluding C = [A, B]; insert at index 0 -> [C, A, B]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $c), ['parent_id' => null, 'position' => 0])
        ->assertSuccessful();

    expect((int) $c->fresh()->sequence_number)->toBe(0)
        ->and((int) $a->fresh()->sequence_number)->toBe(1)
        ->and((int) $b->fresh()->sequence_number)->toBe(2);
});

it('nests a task under a target and recalculates parent progress', function () {
    $parent = moveTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $movable = moveTask($this->project->id, [
        'title' => 'Movable',
        'status_id' => $this->status->id,
        'progress' => (float) $this->status->score,
    ]);
    $score = (float) $this->status->score;

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $movable), [
            'parent_id' => Sqids::encode($parent->id),
            'position' => 0,
        ])
        ->assertSuccessful();

    expect((int) $movable->fresh()->parent_id)->toBe($parent->id)
        ->and((int) $movable->fresh()->sequence_number)->toBe(0)
        ->and((float) $parent->fresh()->progress)->toBe($score);
});

it('moves a child back to the root level', function () {
    $parent = moveTask($this->project->id, ['title' => 'Parent']);
    $child = moveTask($this->project->id, ['title' => 'Child', 'parent_id' => $parent->id]);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $child), ['parent_id' => null, 'position' => 0])
        ->assertSuccessful();

    expect($child->fresh()->parent_id)->toBeNull();
});

it('clamps an out-of-range position to the end of the sibling group', function () {
    $a = moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 0]);
    $b = moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 1]);

    // siblings excluding A = [B]; position 999 clamps to 1 -> [B, A]
    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 999])
        ->assertSuccessful();

    expect((int) $b->fresh()->sequence_number)->toBe(0)
        ->and((int) $a->fresh()->sequence_number)->toBe(1);
});

it('rejects moving a task under its own descendant', function () {
    $a = moveTask($this->project->id, ['title' => 'A']);
    $b = moveTask($this->project->id, ['title' => 'B', 'parent_id' => $a->id]);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), [
            'parent_id' => Sqids::encode($b->id),
            'position' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

it('requires a position', function () {
    $a = moveTask($this->project->id, ['title' => 'A']);

    $this->actingAs($this->user)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['position']);
});

it('forbids moving a task for a user without permission', function () {
    $stranger = User::factory()->create(['id' => 777]);
    $a = moveTask($this->project->id, ['title' => 'A']);

    $this->actingAs($stranger)
        ->putJson(moveRoute($this->encoded, $a), ['parent_id' => null, 'position' => 0])
        ->assertForbidden();
});
```

- [ ] **Step 2: Verifikasi DB test, lalu jalankan test (harus gagal)**

Konfirmasi target DB test dulu (lihat Global Constraints), lalu:

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/TaskMoveTest.php`
Expected: FAIL — route `project.tasks.move` belum ada (`RouteNotFoundException` / `Symfony\...RouteNotFoundException`).

- [ ] **Step 3: Tambah route**

Di `routes/web.php`, tepat setelah baris `tasks.parent.update` (~baris 106):

```php
Route::put('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
```

- [ ] **Step 4: Buat `TaskMoveRequest`**

Create `app/Http/Requests/Task/TaskMoveRequest.php`:

```php
<?php

namespace App\Http\Requests\Task;

use App\Facades\Sqids;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class TaskMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail): void {
                    if ($value) {
                        try {
                            $parent_id = Sqids::decode($value);
                            $project_id = Sqids::decode($this->route('projectEncoded'));
                            $parent = Task::where('id', $parent_id)->where('project_id', $project_id)->firstOrFail();
                        } catch (\Throwable $th) {
                            $fail("The $attribute must be a valid task id.");

                            return;
                        }

                        $task = $this->route('task');

                        if ($parent_id === $task->id) {
                            $fail("The $attribute cannot be a child of the task.");
                        }

                        $cursor = $parent;
                        while ($cursor) {
                            if ($cursor->id === $task->id) {
                                $fail("The $attribute cannot move task under its own descendant.");
                            }

                            $cursor = $cursor->parent;
                        }
                    }
                },
            ],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

- [ ] **Step 5: Tambah method `move` di controller**

Di `app/Http/Controllers/TaskController.php`, tambah import di blok `use` (urutkan alfabetis dekat request lain):

```php
use App\Http\Requests\Task\TaskMoveRequest;
```

Tambah method (letakkan dekat `updateParent`, ~setelah baris 230):

```php
public function move(TaskMoveRequest $request, string $projectEncoded, Task $task): \Illuminate\Http\JsonResponse
{
    $this->authorize('update', $projectEncoded, $task);

    $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;

    $this->service->move($task, $parentId, $request->integer('position'));

    return response()->json([
        'success' => true,
        'message' => 'Task moved successfully.',
    ]);
}
```

- [ ] **Step 6: Tambah method `move` di `TaskService`**

Di `app/Services/TaskService.php`, tambah import setelah `use Spatie\LaravelData\DataCollection;`:

```php
use Illuminate\Support\Facades\DB;
```

Tambah method setelah `updateParent` (~setelah baris 297):

```php
public function move(Task $task, ?int $parentId, int $position): void
{
    DB::transaction(function () use ($task, $parentId, $position): void {
        $oldParent = $task->parent;

        if ($oldParent) {
            $hasSiblings = $oldParent->children()->where('id', '!=', $task->id)->exists();
            $oldParentForProgress = $hasSiblings
                ? $oldParent->children()->where('id', '!=', $task->id)->first()
                : $oldParent;
        } else {
            $oldParentForProgress = null;
        }

        $task->update(['parent_id' => $parentId]);
        $task->unsetRelation('parent'); // drop the stale cached relation so the NEW parent is recalculated

        $siblings = Task::query()
            ->where('project_id', $task->project_id)
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->where('id', '!=', $task->id)
            ->orderByRaw('sequence_number IS NULL, sequence_number, id')
            ->get();

        $ordered = $siblings->all();
        $clamped = max(0, min($position, count($ordered)));
        array_splice($ordered, $clamped, 0, [$task]);

        foreach ($ordered as $index => $sibling) {
            if ((int) $sibling->sequence_number !== $index) {
                $sibling->update(['sequence_number' => $index]);
            }
        }

        $this->calculateParentProgress($task);

        if ($oldParentForProgress) {
            $this->calculateParentProgress($oldParentForProgress);
        }
    });
}
```

- [ ] **Step 7: Jalankan test (harus lulus)**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/TaskMoveTest.php`
Expected: PASS (8 tests passed).

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty
git add app/Http/Requests/Task/TaskMoveRequest.php app/Http/Controllers/TaskController.php app/Services/TaskService.php routes/web.php tests/Feature/ProjectLazy/TaskMoveTest.php
git commit -m "feat(task): add move endpoint for reorder + re-parent with sequence normalization"
```

---

### Task 1B: Persist reorder — List ordering by `sequence_number` (NULLS LAST)

**Konteks:** Commit `b4b4cdb` (2026-06-23) mengubah `recursiveTaskTreeSql` ORDER BY menjadi `depth, id` (menghapus `sequence_number`) dan menonaktifkan `sortByRecency`. Halaman List (`ProjectTabController::list` → `ProjectService::listData` → `ProjectRepository::getTaskTree` → `recursiveTaskTreeSql` → `ProjectTaskData::treeFromTasks`) menyusun urutan sibling dari urutan baris SQL ini. Tanpa `sequence_number` di ORDER BY, hasil reorder (Task 1) tidak persisten saat reload. Task ini mengembalikannya dengan `NULLS LAST` (backward-compatible: grup all-NULL tetap jatuh ke urutan `id`, identik perilaku sekarang; `sortByRecency` tetap nonaktif).

**Files:**
- Modify: `app/Repositories/ProjectRepository.php:182` (ORDER BY pada `recursiveTaskTreeSql`)
- Test: `tests/Feature/ProjectLazy/TaskMoveTest.php` (tambah 2 test ordering; reuse `beforeEach` + helper `moveTask` yang sudah ada dari Task 1)

**Interfaces:**
- Consumes: `ProjectRepository::getTaskTree(int $projectId): DataCollection`, helper `moveTask()` + `beforeEach` dari Task 1.
- Produces: jaminan urutan sibling List = `sequence_number` (NULLS LAST), lalu `id`.

- [ ] **Step 1: Tambah test ordering yang gagal**

Tambahkan dua test berikut di AKHIR `tests/Feature/ProjectLazy/TaskMoveTest.php`:

```php
it('orders sibling tasks by sequence_number with nulls last then id', function () {
    // ids ascending (A < B < C); sequence_number arranges them B, C, A
    moveTask($this->project->id, ['title' => 'A', 'sequence_number' => 2]);
    moveTask($this->project->id, ['title' => 'B', 'sequence_number' => 0]);
    moveTask($this->project->id, ['title' => 'C', 'sequence_number' => 1]);

    $tree = app(\App\Repositories\ProjectRepository::class)->getTaskTree($this->project->id)->toArray();

    expect(collect($tree)->pluck('title')->all())->toBe(['B', 'C', 'A']);
});

it('falls back to id order when sequence_number is null', function () {
    moveTask($this->project->id, ['title' => 'A']); // sequence_number null
    moveTask($this->project->id, ['title' => 'B']); // sequence_number null

    $tree = app(\App\Repositories\ProjectRepository::class)->getTaskTree($this->project->id)->toArray();

    expect(collect($tree)->pluck('title')->all())->toBe(['A', 'B']);
});
```

- [ ] **Step 2: Jalankan test (test pertama harus gagal)**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/TaskMoveTest.php --filter="orders sibling tasks by sequence_number"`
Expected: FAIL — saat ini ORDER BY `depth, id` mengembalikan `['A', 'B', 'C']`, bukan `['B', 'C', 'A']`.

- [ ] **Step 3: Ubah ORDER BY**

Di `app/Repositories/ProjectRepository.php`, dalam `recursiveTaskTreeSql()`, ganti baris (~182):

```sql
            ORDER BY depth, id
```

menjadi:

```sql
            ORDER BY depth, sequence_number NULLS LAST, id
```

- [ ] **Step 4: Jalankan kedua test ordering (harus lulus)**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/TaskMoveTest.php`
Expected: PASS (10 tests: 8 dari Task 1 + 2 ordering).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty
git add app/Repositories/ProjectRepository.php tests/Feature/ProjectLazy/TaskMoveTest.php
git commit -m "fix(task-list): order task tree by sequence_number (nulls last) so reorder persists"
```

---

### Task 2: Pure helper `computeMoveTarget`

**Files:**
- Create: `resources/js/pages/project-lazy/utils/computeMoveTarget.ts`
- Test: `resources/js/pages/project-lazy/utils/computeMoveTarget.test.ts`

**Interfaces:**
- Consumes: tipe `ListTask` dari `@/pages/project-lazy` (punya `id`, `parent_id`, `sub_task_recursive`).
- Produces:
  - `type DropMode = 'before' | 'inside' | 'after'`
  - `interface MoveTarget { parentId: string | null; position: number }`
  - `computeMoveTarget(tree: ListTask[], draggedKey: string, targetKey: string, mode: DropMode): MoveTarget | null`

- [ ] **Step 1: Tulis unit test yang gagal**

Create `resources/js/pages/project-lazy/utils/computeMoveTarget.test.ts`:

```ts
import type { ListTask } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { computeMoveTarget } from './computeMoveTarget';

const task = (id: string, parentId: string | null, children: ListTask[] = []): ListTask => ({
    id,
    parent_id: parentId,
    title: id,
    progress: 0,
    is_overdue: false,
    users: [],
    sub_task_recursive: children,
});

// Tree: A, B, C at root; B has children B1, B2.
const tree = (): ListTask[] => [
    task('A', null),
    task('B', null, [task('B1', 'B'), task('B2', 'B')]),
    task('C', null),
];

describe('computeMoveTarget', () => {
    it('returns null when dragging onto itself', () => {
        expect(computeMoveTarget(tree(), 'A', 'A', 'before')).toBeNull();
    });

    it('computes inside as append to target children (excluding dragged)', () => {
        expect(computeMoveTarget(tree(), 'A', 'B', 'inside')).toEqual({ parentId: 'B', position: 2 });
    });

    it('computes before a root sibling', () => {
        // siblings excl A = [B, C]; before C -> index 1
        expect(computeMoveTarget(tree(), 'A', 'C', 'before')).toEqual({ parentId: null, position: 1 });
    });

    it('computes after a root sibling', () => {
        // siblings excl A = [B, C]; after B -> index 0 + 1 = 1
        expect(computeMoveTarget(tree(), 'A', 'B', 'after')).toEqual({ parentId: null, position: 1 });
    });

    it('computes before within a nested sibling group', () => {
        // siblings of B's children excl A (not present) = [B1, B2]; before B2 -> index 1
        expect(computeMoveTarget(tree(), 'A', 'B2', 'before')).toEqual({ parentId: 'B', position: 1 });
    });

    it('accounts for the dragged node when it is already a sibling of the target', () => {
        // dragging B1 after B2: siblings of parent B excl B1 = [B2]; after B2 -> index 1
        expect(computeMoveTarget(tree(), 'B1', 'B2', 'after')).toEqual({ parentId: 'B', position: 1 });
    });

    it('returns null when the target does not exist', () => {
        expect(computeMoveTarget(tree(), 'A', 'ZZZ', 'inside')).toBeNull();
    });
});
```

- [ ] **Step 2: Jalankan test (harus gagal)**

Run: `npx vitest run resources/js/pages/project-lazy/utils/computeMoveTarget.test.ts`
Expected: FAIL — `computeMoveTarget` belum ada (module/export not found).

- [ ] **Step 3: Implementasi helper**

Create `resources/js/pages/project-lazy/utils/computeMoveTarget.ts`:

```ts
import type { ListTask } from '@/pages/project-lazy';

export type DropMode = 'before' | 'inside' | 'after';

export interface MoveTarget {
    parentId: string | null;
    position: number;
}

const findNode = (list: ListTask[], id: string): ListTask | null => {
    for (const item of list) {
        if (item.id === id) {
            return item;
        }
        const found = findNode(item.sub_task_recursive ?? [], id);
        if (found) {
            return found;
        }
    }
    return null;
};

const siblingsOf = (tree: ListTask[], parentId: string | null): ListTask[] => {
    if (parentId === null) {
        return tree;
    }
    return findNode(tree, parentId)?.sub_task_recursive ?? [];
};

export const computeMoveTarget = (tree: ListTask[], draggedKey: string, targetKey: string, mode: DropMode): MoveTarget | null => {
    if (draggedKey === targetKey) {
        return null;
    }

    const target = findNode(tree, targetKey);
    if (!target) {
        return null;
    }

    if (mode === 'inside') {
        const children = target.sub_task_recursive ?? [];
        const position = children.filter((child) => child.id !== draggedKey).length;
        return { parentId: target.id, position };
    }

    const parentId = target.parent_id ?? null;
    const siblings = siblingsOf(tree, parentId).filter((sibling) => sibling.id !== draggedKey);
    const index = siblings.findIndex((sibling) => sibling.id === targetKey);
    if (index === -1) {
        return null;
    }

    return { parentId, position: mode === 'before' ? index : index + 1 };
};
```

- [ ] **Step 4: Jalankan test (harus lulus)**

Run: `npx vitest run resources/js/pages/project-lazy/utils/computeMoveTarget.test.ts`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/project-lazy/utils/computeMoveTarget.ts resources/js/pages/project-lazy/utils/computeMoveTarget.test.ts
git commit -m "feat(task-list): add computeMoveTarget helper for drag reorder"
```

---

### Task 3: Optimistic `moveNode` di `useLocalTaskTree`

**Files:**
- Modify: `resources/js/pages/project-lazy/composables/useLocalTaskTree.ts`
- Test: `resources/js/pages/project-lazy/composables/useLocalTaskTree.move.test.ts`

**Interfaces:**
- Consumes: `useLocalTaskTree<T>(source)` existing (`tasks`, `findIn`, `removeFrom`, `recalc`).
- Produces: method baru pada return value:
  - `moveNode(id: string, parentId: string | null, position: number): { parentId: string | null; index: number } | null` — menerapkan move optimistic dan mengembalikan lokasi LAMA untuk rollback (`null` jika node tak ditemukan).

- [ ] **Step 1: Tulis unit test yang gagal**

Create `resources/js/pages/project-lazy/composables/useLocalTaskTree.move.test.ts`:

```ts
import type { ListTask } from '@/pages/project-lazy';
import { computed } from 'vue';
import { describe, expect, it } from 'vitest';
import { useLocalTaskTree } from './useLocalTaskTree';

const task = (id: string, parentId: string | null, children: ListTask[] = []): ListTask => ({
    id,
    parent_id: parentId,
    title: id,
    progress: 0,
    is_overdue: false,
    users: [],
    sub_task_recursive: children,
});

const seed = (): ListTask[] => [task('A', null), task('B', null), task('C', null)];

const ids = (list: ListTask[]): string[] => list.map((t) => t.id);

describe('useLocalTaskTree.moveNode', () => {
    it('reorders a root sibling to a new index', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        const prev = moveNode('A', null, 2);

        expect(ids(tasks.value)).toEqual(['B', 'C', 'A']);
        expect(prev).toEqual({ parentId: null, index: 0 });
    });

    it('nests a node under a new parent and updates parent_id', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        moveNode('A', 'B', 0);

        const b = tasks.value.find((t) => t.id === 'B')!;
        expect(ids(tasks.value)).toEqual(['B', 'C']);
        expect(ids(b.sub_task_recursive)).toEqual(['A']);
        expect(b.sub_task_recursive[0].parent_id).toBe('B');
    });

    it('rolls back to the original location using the returned snapshot', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        const prev = moveNode('A', 'B', 0)!;
        moveNode('A', prev.parentId, prev.index);

        expect(ids(tasks.value)).toEqual(['A', 'B', 'C']);
        expect(tasks.value[0].parent_id).toBeNull();
    });

    it('returns null for an unknown id', () => {
        const { moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));
        expect(moveNode('ZZZ', null, 0)).toBeNull();
    });
});
```

- [ ] **Step 2: Jalankan test (harus gagal)**

Run: `npx vitest run resources/js/pages/project-lazy/composables/useLocalTaskTree.move.test.ts`
Expected: FAIL — `moveNode` is not a function / undefined.

- [ ] **Step 3: Implementasi `moveNode`**

Di `resources/js/pages/project-lazy/composables/useLocalTaskTree.ts`, tambah helper di dalam `useLocalTaskTree` (setelah `insert`, sebelum `recalcNode`):

```ts
const findParentList = (id: string): T[] | null => {
    const search = (list: T[]): T[] | null => {
        if (list.some((item) => item.id === id)) {
            return list;
        }
        for (const item of list) {
            const found = search(item.sub_task_recursive ?? []);
            if (found) {
                return found;
            }
        }
        return null;
    };
    return search(tasks.value);
};

const insertAt = (item: T, parentId: string | null, position: number): void => {
    const list = parentId ? (findIn(tasks.value, parentId)?.sub_task_recursive ?? null) : tasks.value;
    if (!list) {
        tasks.value.push(item);
        return;
    }
    const clamped = Math.max(0, Math.min(position, list.length));
    list.splice(clamped, 0, item);
};

const moveNode = (id: string, parentId: string | null, position: number): { parentId: string | null; index: number } | null => {
    const node = findIn(tasks.value, id);
    if (!node) {
        return null;
    }

    const previousParentId = node.parent_id ?? null;
    const previousList = findParentList(id);
    const previousIndex = previousList ? previousList.findIndex((item) => item.id === id) : 0;

    const detached = removeFrom(tasks.value, id);
    if (!detached) {
        return null;
    }

    detached.parent_id = parentId;
    insertAt(detached, parentId, position);

    recalc();
    tasks.value = [...tasks.value];

    return { parentId: previousParentId, index: previousIndex };
};
```

Lalu tambahkan `moveNode` ke object yang di-`return` (di samping `applySaved`, `recalc`, `findNode`):

```ts
    return {
        tasks,
        applySaved,
        recalc,
        moveNode,
        findNode: (id: string): T | null => findIn(tasks.value, id),
    };
```

- [ ] **Step 4: Jalankan test (harus lulus)**

Run: `npx vitest run resources/js/pages/project-lazy/composables/useLocalTaskTree.move.test.ts`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/project-lazy/composables/useLocalTaskTree.ts resources/js/pages/project-lazy/composables/useLocalTaskTree.move.test.ts
git commit -m "feat(task-list): add optimistic moveNode to useLocalTaskTree"
```

---

### Task 4: Rombak `useTaskDragDrop` (native DnD + deteksi zona) + tipe

**Files:**
- Modify: `resources/js/pages/project-lazy/index.d.ts` (tambah `TaskMovePayload`, tambah emit `move`)
- Modify (rewrite): `resources/js/pages/project-lazy/task/composables/useTaskDragDrop.ts`

**Interfaces:**
- Consumes: `computeMoveTarget`, `DropMode` (Task 2); tipe `ListTask`, `TaskMovePayload`.
- Produces (return value `useTaskDragDrop`):
  - state: `dropTargetKey: Ref<string|null>`, `dropMode: Ref<DropMode|null>`, `draggedKey: Ref<string|null>`, `isDraggingTask: ComputedRef<boolean>`, `isRootDropActive: ComputedRef<boolean>`
  - handlers: `onDragStart(event: DragEvent, node: LazyTaskFormatted)`, `onDragEnd()`, `onRowDragOver(event: DragEvent, node: LazyTaskFormatted)`, `onRowDrop(event: DragEvent, node: LazyTaskFormatted)`, `onRootDragOver(event: DragEvent)`, `onRootDrop(event: DragEvent)`
- Context input: `{ tree: Ref<ListTask[]>; canMove: Ref<boolean>; isDescendant: (s: string, t: string) => boolean; onMove: (payload: TaskMovePayload) => void }`

- [ ] **Step 1: Tambah tipe di `index.d.ts`**

Di `resources/js/pages/project-lazy/index.d.ts`, ganti blok `LazyTaskTableEmits` (baris 274-277) menjadi:

```ts
export interface TaskMovePayload {
    taskId: string;
    parentId: string | null;
    position: number;
}

export interface LazyTaskTableEmits {
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: ListTask, parentId: string | null): void;
    (e: 'move', payload: TaskMovePayload): void;
}
```

- [ ] **Step 2: Rewrite `useTaskDragDrop.ts`**

Ganti SELURUH isi `resources/js/pages/project-lazy/task/composables/useTaskDragDrop.ts` dengan:

```ts
import type { LazyTaskFormatted, ListTask, TaskMovePayload } from '@/pages/project-lazy';
import { useToast } from 'primevue/usetoast';
import { computed, ref, type Ref } from 'vue';
import { computeMoveTarget, type DropMode } from '../../utils/computeMoveTarget';

interface DragDropContext {
    tree: Ref<ListTask[]>;
    canMove: Ref<boolean>;
    isDescendant: (sourceId: string, targetId: string) => boolean;
    onMove: (payload: TaskMovePayload) => void;
}

const TASK_DRAG_MIME = 'application/x-task-id';
const TASK_DRAG_TEXT_MIME = 'text/plain';

export const useTaskDragDrop = ({ tree, canMove, isDescendant, onMove }: DragDropContext) => {
    const toast = useToast();

    const draggedKey = ref<string | null>(null);
    const dropTargetKey = ref<string | null>(null);
    const dropMode = ref<DropMode | null>(null);
    const pointerOnRootDropzone = ref(false);

    const isDraggingTask = computed(() => !!draggedKey.value);
    const isRootDropActive = computed(() => isDraggingTask.value && pointerOnRootDropzone.value && !dropTargetKey.value);

    const resetDragState = (): void => {
        draggedKey.value = null;
        dropTargetKey.value = null;
        dropMode.value = null;
        pointerOnRootDropzone.value = false;
    };

    const zoneFromEvent = (event: DragEvent): DropMode => {
        const element = event.currentTarget as HTMLElement | null;
        if (!element) {
            return 'inside';
        }
        const rect = element.getBoundingClientRect();
        const ratio = rect.height > 0 ? (event.clientY - rect.top) / rect.height : 0.5;
        if (ratio < 0.3) {
            return 'before';
        }
        if (ratio > 0.7) {
            return 'after';
        }
        return 'inside';
    };

    const wouldCreateCycle = (parentId: string | null): boolean => {
        if (!parentId || !draggedKey.value) {
            return false;
        }
        return parentId === draggedKey.value || isDescendant(draggedKey.value, parentId);
    };

    const onDragStart = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value) {
            event.preventDefault();
            return;
        }
        draggedKey.value = node.key;
        dropTargetKey.value = null;
        dropMode.value = null;
        if (event.dataTransfer) {
            event.dataTransfer.setData(TASK_DRAG_TEXT_MIME, node.key);
            try {
                event.dataTransfer.setData(TASK_DRAG_MIME, node.key);
            } catch {
                /* ignore */
            }
            event.dataTransfer.effectAllowed = 'move';
        }
    };

    const onDragEnd = (): void => {
        resetDragState();
    };

    const onRowDragOver = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value || !draggedKey.value || node.key === draggedKey.value) {
            return;
        }
        event.preventDefault();
        pointerOnRootDropzone.value = false;
        dropTargetKey.value = node.key;
        dropMode.value = zoneFromEvent(event);
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    };

    const onRowDrop = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        event.preventDefault();

        const mode = dropMode.value ?? 'inside';
        const target = computeMoveTarget(tree.value, draggedKey.value, node.key, mode);
        if (!target) {
            resetDragState();
            return;
        }
        if (wouldCreateCycle(target.parentId)) {
            toast.add({ severity: 'warn', summary: 'Invalid move', detail: 'Cannot move task under its own descendant.', life: 2500 });
            resetDragState();
            return;
        }

        onMove({ taskId: draggedKey.value, parentId: target.parentId, position: target.position });
        resetDragState();
    };

    const onRootDragOver = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        const targetEl = event.target as Element | null;
        if (targetEl?.closest('[data-task-drop-row="true"]')) {
            return;
        }
        event.preventDefault();
        pointerOnRootDropzone.value = true;
        dropTargetKey.value = null;
        dropMode.value = null;
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    };

    const onRootDrop = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        event.preventDefault();
        const position = tree.value.filter((task) => task.id !== draggedKey.value).length;
        onMove({ taskId: draggedKey.value, parentId: null, position });
        resetDragState();
    };

    return {
        draggedKey,
        dropTargetKey,
        dropMode,
        isDraggingTask,
        isRootDropActive,
        onDragStart,
        onDragEnd,
        onRowDragOver,
        onRowDrop,
        onRootDragOver,
        onRootDrop,
    };
};
```

- [ ] **Step 3: Typecheck file ini lewat build (lihat Task 5 untuk verifikasi penuh)**

Catatan: file ini dikonsumsi `TaskTable.vue` (Task 5). Verifikasi kompilasi dilakukan di Task 5 Step 4 (`npm run build`) karena perubahan saling bergantung. Jangan commit sendiri — gabung commit dengan Task 5.

---

### Task 5: Wire `TaskTable.vue` (grip + zona) & `List.vue` (`onMove` optimistic)

**Files:**
- Modify: `resources/js/pages/project-lazy/task/TaskTable.vue`
- Modify: `resources/js/pages/project-lazy/task/List.vue`

**Interfaces:**
- Consumes: `useTaskDragDrop` API baru (Task 4), `useLocalTaskTree.moveNode` (Task 3), `TaskMovePayload`, emit `move` (Task 4).

- [ ] **Step 1: Update `TaskTable.vue` — script (destructure drag composable)**

Di `resources/js/pages/project-lazy/task/TaskTable.vue`, ganti blok pemanggilan `useTaskDragDrop` (baris 43-60) menjadi:

```ts
const {
    dropTargetKey,
    dropMode,
    isDraggingTask,
    isRootDropActive,
    onDragStart,
    onDragEnd,
    onRowDragOver,
    onRowDrop,
    onRootDragOver,
    onRootDrop,
} = useTaskDragDrop({ tree: tasksRef, canMove: canMoveTask, isDescendant, onMove: (payload) => emit('move', payload) });
```

- [ ] **Step 2: Update `TaskTable.vue` — container (hapus handler pointer lama)**

Ganti pembuka div container scrollable (baris 133-147) menjadi (hapus `@mousemove`/`@mouseleave`):

```html
        <div
            class="overflow-x-auto"
            :class="
                isRootDropActive
                    ? 'rounded-lg border-2 border-dashed border-emerald-400 bg-emerald-50/60 p-1 transition-colors dark:border-emerald-500/80 dark:bg-emerald-950/35'
                    : isDraggingTask
                      ? 'rounded-lg border border-dashed border-blue-300/80 bg-blue-50/40 p-1 transition-colors dark:border-blue-700/70 dark:bg-blue-950/20'
                      : 'transition-colors'
            "
            @dragover.prevent="onRootDragOver"
            @dragenter.prevent="onRootDragOver"
            @drop.stop.prevent="onRootDrop"
        >
```

- [ ] **Step 3: Update `TaskTable.vue` — Title column body (grip + zona)**

Ganti SELURUH `<template #body="{ node }">` pada Column `field="title"` (baris 183-221) menjadi:

```html
                    <template #body="{ node }">
                        <div
                            data-task-drop-row="true"
                            class="flex items-center gap-2 rounded px-1 py-1 transition-colors"
                            :class="[
                                dropTargetKey === node.key && dropMode === 'inside'
                                    ? 'bg-blue-100 ring-1 ring-blue-300 dark:bg-blue-900/40 dark:ring-blue-600/70'
                                    : '',
                                dropTargetKey === node.key && dropMode === 'before' ? 'border-t-2 border-blue-500' : '',
                                dropTargetKey === node.key && dropMode === 'after' ? 'border-b-2 border-blue-500' : '',
                            ]"
                            @dragover.prevent="onRowDragOver($event, node)"
                            @dragenter.prevent="onRowDragOver($event, node)"
                            @drop.stop.prevent="onRowDrop($event, node)"
                        >
                            <i
                                v-if="canMoveTask"
                                class="pi pi-bars shrink-0 cursor-grab text-surface-400 transition-colors hover:text-surface-600 active:cursor-grabbing dark:text-surface-500 dark:hover:text-surface-300"
                                :class="draggedKey === node.key ? 'text-blue-500 dark:text-blue-400' : ''"
                                :draggable="true"
                                style="-webkit-user-drag: element"
                                aria-label="Drag to reorder or nest"
                                v-tooltip.top="'Drag to reorder / nest'"
                                @dragstart.stop="onDragStart($event, node)"
                                @dragend="onDragEnd"
                            />

                            <i
                                v-if="node.data.category?.id"
                                v-tooltip.top="node.data.category.name"
                                :class="getCategoryIcon(node.data.category)"
                                :style="{ color: getCategoryColor(node.data.category) }"
                                class="shrink-0 cursor-default text-sm"
                            />

                            <div
                                :title="node.data.title"
                                class="max-w-[12rem] select-none truncate text-ellipsis rounded px-1 py-0.5 sm:max-w-[18rem] lg:max-w-[26rem]"
                            >
                                {{ node.data.title }}
                            </div>
                        </div>
                    </template>
```

Tambahkan `draggedKey` ke destructure di Step 1 jika ingin highlight grip aktif (sudah termasuk di list return Task 4). Update destructure Step 1 menjadi menyertakan `draggedKey`:

```ts
const {
    draggedKey,
    dropTargetKey,
    dropMode,
    isDraggingTask,
    isRootDropActive,
    onDragStart,
    onDragEnd,
    onRowDragOver,
    onRowDrop,
    onRootDragOver,
    onRootDrop,
} = useTaskDragDrop({ tree: tasksRef, canMove: canMoveTask, isDescendant, onMove: (payload) => emit('move', payload) });
```

- [ ] **Step 4: Update `List.vue` — onMove optimistic**

Ganti SELURUH `<script setup>` `resources/js/pages/project-lazy/task/List.vue` (baris 1-30) menjadi:

```ts
import type { ListProps, ListTask, SavedTaskPayload, TaskMovePayload } from '@/pages/project-lazy';
import { Deferred, Head } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import ListTableSkeleton from './partials/ListTableSkeleton.vue';
import TaskFormDrawer from '../partials/TaskFormDrawer.vue';
import TaskTable from './TaskTable.vue';
import { useLocalTaskTree } from '../composables/useLocalTaskTree';
import { buildListTaskNode, patchListTaskNode } from '../utils/listTaskNode';

const props = defineProps<ListProps>();

const toast = useToast();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const localProgress = ref(props.project.progress);

const { tasks, applySaved, moveNode } = useLocalTaskTree<ListTask>(
    computed(() => props.tasks),
    {
        onProjectProgress: (value) => {
            localProgress.value = value;
        },
    },
);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: ListTask) => drawer.value?.openEdit(task);
const onSaved = (payload: SavedTaskPayload) => applySaved(payload, { build: buildListTaskNode, patch: patchListTaskNode });

const onMove = async ({ taskId, parentId, position }: TaskMovePayload) => {
    const previous = moveNode(taskId, parentId, position);
    try {
        await axios.put(route('project.tasks.move', { projectEncoded: props.project.id, task: taskId }), {
            parent_id: parentId,
            position,
        });
    } catch {
        if (previous) {
            moveNode(taskId, previous.parentId, previous.index);
        }
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to move task.', life: 3000 });
    }
};
```

Lalu di template `List.vue`, tambahkan listener `@move` pada `<TaskTable ...>` (setelah `@edit="onEdit"`):

```html
                @edit="onEdit"
                @move="onMove"
```

- [ ] **Step 5: Build untuk verifikasi kompilasi**

Run: `npm run build`
Expected: build sukses tanpa error TypeScript/Vue baru pada `useTaskDragDrop.ts`, `TaskTable.vue`, `List.vue`, `computeMoveTarget.ts`, `useLocalTaskTree.ts`. (Catatan: project punya ~31 type error pre-existing yang tidak terkait — pastikan tidak ada error BARU pada file di atas.)

- [ ] **Step 6: Jalankan seluruh unit test frontend**

Run: `npx vitest run`
Expected: PASS, termasuk `computeMoveTarget.test.ts` dan `useLocalTaskTree.move.test.ts`.

- [ ] **Step 7: Verifikasi manual di browser**

Buka halaman List project (Herd: gunakan `get-absolute-url`), lalu cek:
1. Tarik grip ☰ sebuah task ke **atas** baris lain → muncul garis biru atas, lepas → urutan berubah (task pindah sebelum target), tanpa reload.
2. Tarik ke **tengah** baris → highlight biru, lepas → task jadi anak target (ter-nest).
3. Tarik ke **bawah** baris → garis biru bawah, lepas → task pindah sesudah target.
4. Tarik ke area kosong root → border emerald, lepas → task pindah ke level akar.
5. Coba nest task ke dalam keturunannya sendiri → toast "Invalid move", urutan tidak berubah.
6. Matikan jaringan (DevTools offline) lalu drag → UI sempat berubah lalu **rollback** + toast error.
7. Refresh halaman → urutan hasil drag tetap (persisten dari server).

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/project-lazy/index.d.ts \
    resources/js/pages/project-lazy/task/composables/useTaskDragDrop.ts \
    resources/js/pages/project-lazy/task/TaskTable.vue \
    resources/js/pages/project-lazy/task/List.vue
git commit -m "feat(task-list): native drag grip with zone-based reorder + re-parent and optimistic update"
```

---

## Catatan integrasi

- Endpoint lama `project.tasks.parent.update` tetap utuh; Backlog tidak terpengaruh.
- Tidak ada `router.reload()` pada flow drag; konsistensi server dijamin oleh normalisasi `sequence_number` di `TaskService::move` (Task 1) dan ordering List `sequence_number NULLS LAST` (Task 1B) sehingga reorder persisten saat halaman dimuat ulang.
- Tidak perlu regenerasi Ziggy: `@routes` (`resources/views/app.blade.php:38`) menyuntik route saat runtime.
