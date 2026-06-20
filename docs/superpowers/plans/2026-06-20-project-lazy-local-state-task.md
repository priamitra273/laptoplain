# Local State + New Task Create/Update Endpoint (project-lazy) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the project-lazy List (and Kanban) task form submit via axios to a new JSON endpoint that returns only `{success, id}`, then update a client-side task tree locally — inserting new children, recomputing each task's progress recursively, and pushing the project's recomputed progress live to the shell header.

**Architecture:** A new `LazyTaskController` reuses the existing Form Requests, Actions, and policy but returns JSON instead of an Inertia redirect (the existing `project.tasks.store/update` endpoints are left untouched so the classic `project/` flow and Backlog are unaffected). On create it also runs a new `RecalculateProgressAction` so persisted progress matches the frontend's local recomputation. On the frontend, a generic `useLocalTaskTree` composable holds a cloned, reactive copy of the task tree and recomputes progress (leaf = `status.score`, parent = recursive average of children, project = average of roots). The shared task form (`useTaskForm`) switches its transport to axios and emits a structured payload that List/Kanban feed to the composable.

**Tech Stack:** Laravel 12, Inertia v2, Vue 3 (`<script setup>`), TypeScript, PrimeVue, axios, Ziggy (`route()`), Pest v3 (backend), Vitest v4 (frontend).

## Global Constraints

- PHP control structures always use curly braces; explicit return types on all methods; constructor property promotion; no empty constructors.
- Prefer `Model::query()` over `DB::`; type-hinted Eloquent relationships; avoid N+1.
- Run `vendor/bin/pint` (no `--test`) before finalizing PHP changes.
- All Vue components have a single root element; navigation via `<Link>`/`router`.
- Frontend tests live next to source as `*.test.ts`; run with `npm test` (`vitest run`).
- All ids are sqid **strings** at runtime; backend route params are sqid strings decoded via `App\Facades\Sqids`.
- ⚠️ **DB SAFETY (project rule):** Backend tests use `RefreshDatabase` (`migrate:fresh`). Before running ANY backend test, STOP and confirm the resolved DB is the disposable test DB via `.env.testing`, not dev/prod. Verify with: `APP_ENV=testing php artisan tinker --execute="echo config('database.connections.pgsql.database');"` and only proceed after explicit confirmation.
- Do NOT modify: `app/Actions/Task/CreateTaskAction.php`, `app/Actions/Task/UpdateTaskAction.php`, `app/Http/Controllers/TaskController.php`, routes `project.tasks.store`/`project.tasks.update`, or anything under `resources/js/pages/project/`.

---

## File Structure

**Create:**
- `app/Actions/Task/RecalculateProgressAction.php` — walk up ancestors + project, persist recomputed progress.
- `app/Http/Controllers/LazyTaskController.php` — JSON create/update endpoints.
- `resources/js/pages/project-lazy/task/composables/useLocalTaskTree.ts` — generic reactive tree + progress recompute.
- `resources/js/pages/project-lazy/task/composables/useLocalTaskTree.test.ts`
- `resources/js/pages/project-lazy/task/composables/taskFormData.ts` — `FormData` serializer.
- `resources/js/pages/project-lazy/task/composables/taskFormData.test.ts`
- `resources/js/pages/project-lazy/task/nodes/listTaskNode.ts` — build/patch a `ListTask`.
- `resources/js/pages/project-lazy/task/nodes/listTaskNode.test.ts`
- `resources/js/pages/project-lazy/task/nodes/kanbanCardNode.ts` — build/patch a `KanbanCard`.
- `resources/js/pages/project-lazy/task/nodes/kanbanCardNode.test.ts`
- `tests/Feature/ProjectLazy/LazyTaskWriteTest.php` — backend feature tests.

**Modify:**
- `routes/web.php` — add two routes in the `project/{projectEncoded}` group (name prefix `project.`).
- `resources/js/types/type.ts` — add `LiveProjectProgressKey`.
- `resources/js/pages/project-lazy/index.d.ts` — add `SavedTaskPayload`.
- `resources/js/pages/project-lazy/task/composables/useTaskForm.ts` — axios submit, `processing`, error mapping, emit payload.
- `resources/js/pages/project-lazy/task/TaskForm.vue` — use `processing`.
- `resources/js/pages/project-lazy/task/TaskFormDrawer.vue` — forward `saved` payload.
- `resources/js/pages/project-lazy/task/composables/useTaskFormDrawer.ts` — `onSaved` forwards payload.
- `resources/js/pages/project-lazy/layouts/ProjectShellLayout.vue` — provide live progress, pass merged project to `ProjectStats`.
- `resources/js/pages/project-lazy/List.vue` — wire local state.
- `resources/js/pages/project-lazy/Kanban.vue` — wire local state.

---

## Task B1: RecalculateProgressAction

**Files:**
- Create: `app/Actions/Task/RecalculateProgressAction.php`
- Test: `tests/Feature/ProjectLazy/LazyTaskWriteTest.php`

**Interfaces:**
- Produces: `RecalculateProgressAction::execute(Task $task): void` — for each ancestor of `$task` (walking up `parent`), sets `progress = $ancestor->calculateProgress()`; then sets `$task->project->progress = $task->project->calculateProgress()`.
- Consumes: existing `Task::calculateProgress()` (`app/Models/Task.php:216`), `Task::parent()` (`:96`), `Task::project()` (`:136`), `Project::calculateProgress()` (`app/Models/Project.php:169`).

- [ ] **Step 1: Write the failing test** (creates the new test file with the shared setup, mirroring `tests/Feature/ProjectLazy/ProjectTabTest.php`)

```php
<?php

use App\Actions\Task\RecalculateProgressAction;
use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsSprintStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    // Pin user id 1 so the master seeders' hardcoded FKs resolve (see ProjectTabTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
        MsSprintStatusSeeder::class,
    ]);

    // Super admin bypasses route.permission and gets a full-access policy.
    Role::create([
        'name' => 'super-admin-test',
        'guard_name' => 'web',
        'label' => 'Super Admin Test',
        'team_id' => 1,
        'is_active' => true,
    ]);
    $this->user->assignRole('super-admin-test');

    $this->status = MsTaskStatus::query()->where('score', '>', 0)->orderBy('score')->firstOrFail();
    $this->priority = MsTaskPriority::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->project = Project::create([
        'status_id' => MsProjectStatus::query()->firstOrFail()->id,
        'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
        'owner_id' => $this->user->id,
        'owned_id' => $this->user->id,
        'title' => 'Lazy Write Project',
        'emoji' => '🚀',
        'progress' => 0,
    ]);

    $this->encoded = Sqids::encode($this->project->id);
});

function lazyWriteTask(int $projectId, array $attrs = []): Task
{
    return Task::create(array_merge([
        'project_id' => $projectId,
        'title' => 'Task',
        'progress' => 0,
        'sequence_number' => 1,
    ], $attrs));
}

it('recalculates ancestor and project progress as the recursive average of children', function () {
    $parent = lazyWriteTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $childA = lazyWriteTask($this->project->id, ['title' => 'A', 'parent_id' => $parent->id, 'progress' => 40]);
    lazyWriteTask($this->project->id, ['title' => 'B', 'parent_id' => $parent->id, 'progress' => 60]);

    (new RecalculateProgressAction)->execute($childA);

    expect((float) $parent->fresh()->progress)->toBe(50.0)
        ->and((float) $this->project->fresh()->progress)->toBe(50.0);
});
```

- [ ] **Step 2: Run test to verify it fails** (confirm DB safety first — see Global Constraints)

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php --filter="recalculates ancestor"`
Expected: FAIL with `Class "App\Actions\Task\RecalculateProgressAction" not found`.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace App\Actions\Task;

use App\Models\Task;

class RecalculateProgressAction
{
    public function execute(Task $task): void
    {
        $parent = $task->parent;

        while ($parent) {
            $parent->update(['progress' => $parent->calculateProgress()]);
            $parent = $parent->parent;
        }

        $task->project->update(['progress' => $task->project->calculateProgress()]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php --filter="recalculates ancestor"`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint app/Actions/Task/RecalculateProgressAction.php tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git add app/Actions/Task/RecalculateProgressAction.php tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git commit -m "feat(task): add RecalculateProgressAction for ancestor + project progress"
```

---

## Task B2: LazyTaskController@store + route

**Files:**
- Create: `app/Http/Controllers/LazyTaskController.php`
- Modify: `routes/web.php` (inside `Route::prefix('project/{projectEncoded}')->name('project.')` group, after line 97)
- Test: `tests/Feature/ProjectLazy/LazyTaskWriteTest.php`

**Interfaces:**
- Consumes: `App\Http\Requests\Task\TaskStoreRequest`, `App\Actions\Task\CreateTaskAction::execute(Project, array, int): Task`, `App\Actions\Task\RecalculateProgressAction::execute(Task): void`, `App\Services\ProjectService::findByEncodedId(string): Project`, `App\Facades\Sqids::encode(int): string`.
- Produces: route `project.tasks.lazy-store` → `POST project/{projectEncoded}/tasks/lazy`; JSON `{success:true, id:"<sqid>"}` on success, `422` on validation failure, `403` when unauthorized.

- [ ] **Step 1: Write the failing tests** (append to `tests/Feature/ProjectLazy/LazyTaskWriteTest.php`)

```php
it('creates a task via the lazy endpoint and returns success with an encoded id', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'title' => 'Created via lazy',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'task_category_id' => Sqids::encode($this->category->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    );

    $response->assertSuccessful();
    $response->assertJsonPath('success', true);
    expect($response->json('id'))->toBeString();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $this->project->id,
        'title' => 'Created via lazy',
    ]);
});

it('recalculates parent and project progress when a subtask is created via the lazy endpoint', function () {
    $parent = lazyWriteTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $score = (float) $this->status->score;

    $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'parent_id' => Sqids::encode($parent->id),
            'title' => 'Child via lazy',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    )->assertSuccessful();

    expect((float) $parent->fresh()->progress)->toBe($score)
        ->and((float) $this->project->fresh()->progress)->toBe($score);
});

it('returns 422 when creating a task with invalid data via the lazy endpoint', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        ['project_id' => $this->encoded],
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'status_id', 'priority_id', 'type_id']);
});

it('forbids creating a task for a user without permission via the lazy endpoint', function () {
    $stranger = User::factory()->create(['id' => 777]);

    $response = $this->actingAs($stranger)->postJson(
        route('project.tasks.lazy-store', ['projectEncoded' => $this->encoded]),
        [
            'project_id' => $this->encoded,
            'title' => 'Nope',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    );

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php --filter="lazy endpoint"`
Expected: FAIL — route `project.tasks.lazy-store` not defined (`Route [project.tasks.lazy-store] not defined.`).

- [ ] **Step 3a: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\RecalculateProgressAction;
use App\Actions\Task\UpdateTaskAction;
use App\Facades\Sqids;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Models\Task;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LazyTaskController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function store(
        TaskStoreRequest $request,
        string $encoded,
        CreateTaskAction $createTaskAction,
        RecalculateProgressAction $recalculateProgressAction
    ): JsonResponse {
        $project = $this->projectService->findByEncodedId($encoded);

        abort_if($request->user()->cannot('create', [Task::class, $project]), 403);

        $task = $createTaskAction->execute($project, $request->validated(), Auth::id());

        $recalculateProgressAction->execute($task);

        return response()->json([
            'success' => true,
            'id' => Sqids::encode($task->id),
        ]);
    }

    public function update(
        TaskUpdateRequest $request,
        string $encoded,
        string $taskEncoded,
        UpdateTaskAction $updateTaskAction
    ): JsonResponse {
        $taskId = Sqids::decode($taskEncoded);

        $task = Task::query()->findOrFail($taskId);

        $project = $this->projectService->findByEncodedId($encoded);
        abort_if($project->id !== $task->project_id, 404);
        abort_if($request->user()->cannot('update', $task), 403);

        $updateTaskAction->execute($task, $request->validated());

        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 3b: Register the routes** in `routes/web.php` immediately after the `tasks.update` route (line 97), inside the `Route::prefix('project/{projectEncoded}')->name('project.')` group:

```php
        // Lazy detail (project-lazy) write flow — JSON responses for the axios/local-state UI.
        Route::post('tasks/lazy', [LazyTaskController::class, 'store'])->name('tasks.lazy-store');
        Route::put('tasks/{taskEncoded}/lazy', [LazyTaskController::class, 'update'])->name('tasks.lazy-update');
```

Add the import at the top of `routes/web.php` (next to the other controller `use` statements):

```php
use App\Http\Controllers\LazyTaskController;
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php --filter="lazy endpoint"`
Expected: PASS (4 tests).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint app/Http/Controllers/LazyTaskController.php routes/web.php tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git add app/Http/Controllers/LazyTaskController.php routes/web.php tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git commit -m "feat(task): add lazy task store endpoint returning JSON success+id"
```

---

## Task B3: LazyTaskController@update tests

**Files:**
- Test: `tests/Feature/ProjectLazy/LazyTaskWriteTest.php` (controller + route already added in B2)

**Interfaces:**
- Consumes: route `project.tasks.lazy-update` → `PUT project/{projectEncoded}/tasks/{taskEncoded}/lazy`; `App\Actions\Task\UpdateTaskAction` (already recalculates ancestor + project progress).
- Produces: JSON `{success:true}` on success, `422` on invalid, `403`/`404` on auth/ownership failure.

- [ ] **Step 1: Write the failing tests** (append to `tests/Feature/ProjectLazy/LazyTaskWriteTest.php`)

```php
it('updates a task via the lazy endpoint and returns success', function () {
    $task = lazyWriteTask($this->project->id, [
        'title' => 'Before',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->user)->putJson(
        route('project.tasks.lazy-update', [
            'projectEncoded' => $this->encoded,
            'taskEncoded' => Sqids::encode($task->id),
        ]),
        [
            'title' => 'After',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    );

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);
    expect($task->fresh()->title)->toBe('After');
});

it('recalculates parent progress when a leaf status changes via the lazy update endpoint', function () {
    $parent = lazyWriteTask($this->project->id, ['title' => 'Parent', 'progress' => 0]);
    $child = lazyWriteTask($this->project->id, [
        'title' => 'Child',
        'parent_id' => $parent->id,
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
        'progress' => 0,
    ]);
    $score = (float) $this->status->score;

    $this->actingAs($this->user)->putJson(
        route('project.tasks.lazy-update', [
            'projectEncoded' => $this->encoded,
            'taskEncoded' => Sqids::encode($child->id),
        ]),
        [
            'title' => 'Child',
            'status_id' => Sqids::encode($this->status->id),
            'priority_id' => Sqids::encode($this->priority->id),
            'type_id' => Sqids::encode($this->type->id),
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-10',
        ],
    )->assertSuccessful();

    expect((float) $parent->fresh()->progress)->toBe($score)
        ->and((float) $this->project->fresh()->progress)->toBe($score);
});

it('returns 422 when updating a task with invalid data via the lazy endpoint', function () {
    $task = lazyWriteTask($this->project->id, [
        'title' => 'X',
        'status_id' => $this->status->id,
        'priority_id' => $this->priority->id,
        'type_id' => $this->type->id,
    ]);

    $response = $this->actingAs($this->user)->putJson(
        route('project.tasks.lazy-update', [
            'projectEncoded' => $this->encoded,
            'taskEncoded' => Sqids::encode($task->id),
        ]),
        ['status_id' => 'not-a-valid-sqid'],
    );

    $response->assertStatus(422);
});
```

- [ ] **Step 2: Run tests to verify they pass** (controller + route exist from B2)

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php --filter="lazy update endpoint|lazy endpoint"`
Expected: PASS. If `Step 1` tests fail, fix `LazyTaskController@update` until green.

- [ ] **Step 3: Run the whole file**

Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php`
Expected: PASS (all B1–B3 tests).

- [ ] **Step 4: Commit**

```bash
vendor/bin/pint tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git add tests/Feature/ProjectLazy/LazyTaskWriteTest.php
git commit -m "test(task): cover lazy task update endpoint + progress recalc"
```

---

## Task F1: useLocalTaskTree composable

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/useLocalTaskTree.ts`
- Test: `resources/js/pages/project-lazy/task/composables/useLocalTaskTree.test.ts`

**Interfaces:**
- Produces:
  - `type TaskTreeNode<T> = { id: string; parent_id: string | null; progress: number; status?: { score?: number } | null; sub_task_recursive: T[] }`
  - `interface NodeAdapter<T> { build: (payload: SavedTaskPayload) => T; patch: (node: T, payload: SavedTaskPayload) => void }`
  - `useLocalTaskTree<T extends TaskTreeNode<T>>(source: Ref<T[]> | ComputedRef<T[]>, options?: { onProjectProgress?: (value: number) => void }): { tasks: Ref<T[]>; applySaved: (payload: SavedTaskPayload, adapter: NodeAdapter<T>) => void; recalc: () => void; findNode: (id: string) => T | null }`
- Consumes: `SavedTaskPayload` from `@/pages/project-lazy` (added in Task F3 — for F1 the composable only reads `payload.mode`, `payload.id`, `payload.parentId`, so the test defines a minimal inline payload literal).

- [ ] **Step 1: Write the failing test**

```ts
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { useLocalTaskTree } from './useLocalTaskTree';

interface Node {
    id: string;
    parent_id: string | null;
    progress: number;
    status?: { score?: number } | null;
    sub_task_recursive: Node[];
}

const node = (over: Partial<Node> & { id: string }): Node => ({
    parent_id: null,
    progress: 0,
    status: null,
    sub_task_recursive: [],
    ...over,
});

const leafAdapter = {
    build: (p: { id: string; parentId: string | null; status?: { score?: number } | null }): Node =>
        node({ id: p.id, parent_id: p.parentId, status: p.status ?? null, progress: p.status?.score ?? 0 }),
    patch: (n: Node, p: { parentId: string | null; status?: { score?: number } | null }): void => {
        n.parent_id = p.parentId;
        n.status = p.status ?? null;
    },
};

describe('useLocalTaskTree.recalc', () => {
    it('sets a leaf progress from its status score and a parent to the average of children', () => {
        const source = ref<Node[]>([
            node({
                id: 'p',
                sub_task_recursive: [
                    node({ id: 'a', parent_id: 'p', status: { score: 40 }, progress: 40 }),
                    node({ id: 'b', parent_id: 'p', status: { score: 60 }, progress: 60 }),
                ],
            }),
        ]);
        const onProjectProgress = vi.fn();
        const { tasks, recalc } = useLocalTaskTree(source, { onProjectProgress });

        recalc();

        expect(tasks.value[0].progress).toBe(50);
        expect(onProjectProgress).toHaveBeenLastCalledWith(50);
    });
});

describe('useLocalTaskTree.applySaved', () => {
    it('inserts a created child under its parent and recomputes ancestor + project progress', () => {
        const source = ref<Node[]>([node({ id: 'p', status: { score: 0 }, progress: 0 })]);
        const onProjectProgress = vi.fn();
        const { tasks, applySaved } = useLocalTaskTree(source, { onProjectProgress });

        applySaved({ mode: 'create', id: 'c', parentId: 'p', status: { score: 80 } } as never, leafAdapter as never);

        expect(tasks.value[0].sub_task_recursive).toHaveLength(1);
        expect(tasks.value[0].sub_task_recursive[0].id).toBe('c');
        expect(tasks.value[0].progress).toBe(80);
        expect(onProjectProgress).toHaveBeenLastCalledWith(80);
    });

    it('patches an existing node and re-parents it when parentId changes', () => {
        const source = ref<Node[]>([
            node({ id: 'p1', sub_task_recursive: [node({ id: 'c', parent_id: 'p1', status: { score: 30 }, progress: 30 })] }),
            node({ id: 'p2', status: { score: 0 }, progress: 0 }),
        ]);
        const { tasks, applySaved, findNode } = useLocalTaskTree(source, {});

        applySaved({ mode: 'edit', id: 'c', parentId: 'p2', status: { score: 90 } } as never, leafAdapter as never);

        expect(tasks.value[0].sub_task_recursive).toHaveLength(0);
        expect(findNode('p2')?.sub_task_recursive[0].id).toBe('c');
        expect(findNode('p2')?.progress).toBe(90);
    });

    it('re-seeds local tasks when the source reference changes', () => {
        const source = ref<Node[]>([node({ id: 'old' })]);
        const { tasks } = useLocalTaskTree(source, {});
        expect(tasks.value.map((t) => t.id)).toEqual(['old']);

        source.value = [node({ id: 'new' })];
        expect(tasks.value.map((t) => t.id)).toEqual(['new']);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- useLocalTaskTree`
Expected: FAIL — cannot resolve `./useLocalTaskTree`.

- [ ] **Step 3: Write the implementation**

```ts
import type { SavedTaskPayload } from '@/pages/project-lazy';
import { ref, watch, type ComputedRef, type Ref } from 'vue';

export type TaskTreeNode<T> = {
    id: string;
    parent_id: string | null;
    progress: number;
    status?: { score?: number } | null;
    sub_task_recursive: T[];
};

export interface NodeAdapter<T> {
    build: (payload: SavedTaskPayload) => T;
    patch: (node: T, payload: SavedTaskPayload) => void;
}

const round2 = (value: number): number => Math.round(value * 100) / 100;

const cloneTree = <T,>(list: T[]): T[] => JSON.parse(JSON.stringify(list ?? []));

export const useLocalTaskTree = <T extends TaskTreeNode<T>>(
    source: Ref<T[]> | ComputedRef<T[]>,
    options: { onProjectProgress?: (value: number) => void } = {},
) => {
    const tasks = ref<T[]>([]) as Ref<T[]>;

    watch(
        source,
        (list) => {
            tasks.value = cloneTree(list ?? []);
        },
        { immediate: true },
    );

    const findIn = (list: T[], id: string): T | null => {
        for (const item of list) {
            if (item.id === id) {
                return item;
            }
            const found = findIn(item.sub_task_recursive ?? [], id);
            if (found) {
                return found;
            }
        }
        return null;
    };

    const removeFrom = (list: T[], id: string): T | null => {
        for (let i = 0; i < list.length; i++) {
            if (list[i].id === id) {
                return list.splice(i, 1)[0];
            }
            const removed = removeFrom(list[i].sub_task_recursive ?? [], id);
            if (removed) {
                return removed;
            }
        }
        return null;
    };

    const insert = (item: T, parentId: string | null): void => {
        if (!parentId) {
            tasks.value.push(item);
            return;
        }
        const parent = findIn(tasks.value, parentId);
        if (parent) {
            parent.sub_task_recursive.push(item);
        } else {
            tasks.value.push(item);
        }
    };

    const recalcNode = (item: T): number => {
        const children = item.sub_task_recursive ?? [];
        const value =
            children.length === 0
                ? (item.status?.score ?? item.progress ?? 0)
                : round2(children.reduce((sum, child) => sum + recalcNode(child), 0) / children.length);
        item.progress = value;
        return value;
    };

    const recalc = (): void => {
        const rootScores = tasks.value.map((item) => recalcNode(item));
        if (options.onProjectProgress) {
            const projectProgress = rootScores.length === 0 ? 0 : round2(rootScores.reduce((s, v) => s + v, 0) / rootScores.length);
            options.onProjectProgress(projectProgress);
        }
    };

    const applySaved = (payload: SavedTaskPayload, adapter: NodeAdapter<T>): void => {
        if (payload.mode === 'create') {
            insert(adapter.build(payload), payload.parentId);
        } else {
            const node = findIn(tasks.value, payload.id);
            if (!node) {
                return;
            }
            const previousParentId = node.parent_id;
            adapter.patch(node, payload);
            const nextParentId = payload.parentId ?? null;
            if ((previousParentId ?? null) !== nextParentId) {
                const detached = removeFrom(tasks.value, payload.id);
                if (detached) {
                    insert(detached, nextParentId);
                }
            }
        }
        recalc();
    };

    return {
        tasks,
        applySaved,
        recalc,
        findNode: (id: string): T | null => findIn(tasks.value, id),
    };
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- useLocalTaskTree`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/project-lazy/task/composables/useLocalTaskTree.ts resources/js/pages/project-lazy/task/composables/useLocalTaskTree.test.ts
git commit -m "feat(project-lazy): add useLocalTaskTree for client-side progress recompute"
```

---

## Task F2: taskFormData serializer

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/taskFormData.ts`
- Test: `resources/js/pages/project-lazy/task/composables/taskFormData.test.ts`

**Interfaces:**
- Produces: `buildTaskFormData(data: Record<string, unknown>): FormData` — serializes nested objects/arrays into Laravel bracket notation (`add_tag[new][0][name]`), booleans as `'1'`/`'0'`, `File` instances appended directly, and `null`/`undefined` omitted.

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it } from 'vitest';
import { buildTaskFormData } from './taskFormData';

const entries = (fd: FormData): Array<[string, FormDataEntryValue]> => [...fd.entries()];

describe('buildTaskFormData', () => {
    it('serializes scalars, booleans, arrays and nested objects in bracket notation', () => {
        const fd = buildTaskFormData({
            _method: 'PUT',
            title: 'A',
            is_archived: true,
            assign_users: ['x', 'y'],
            add_tag: { new: [{ name: 't', severity: 'info' }], exists: ['z'] },
            parent_id: null,
        });

        expect(entries(fd)).toEqual([
            ['_method', 'PUT'],
            ['title', 'A'],
            ['is_archived', '1'],
            ['assign_users[0]', 'x'],
            ['assign_users[1]', 'y'],
            ['add_tag[new][0][name]', 't'],
            ['add_tag[new][0][severity]', 'info'],
            ['add_tag[exists][0]', 'z'],
        ]);
    });

    it('appends File instances directly', () => {
        const file = new File(['data'], 'a.png', { type: 'image/png' });
        const fd = buildTaskFormData({ attachments: [file] });
        const value = fd.get('attachments[0]');
        expect(value).toBeInstanceOf(File);
        expect((value as File).name).toBe('a.png');
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- taskFormData`
Expected: FAIL — cannot resolve `./taskFormData`.

- [ ] **Step 3: Write the implementation**

```ts
const appendValue = (fd: FormData, key: string, value: unknown): void => {
    if (value === null || value === undefined) {
        return;
    }
    if (value instanceof File || value instanceof Blob) {
        fd.append(key, value as Blob);
        return;
    }
    if (Array.isArray(value)) {
        value.forEach((item, index) => appendValue(fd, `${key}[${index}]`, item));
        return;
    }
    if (typeof value === 'object') {
        Object.entries(value as Record<string, unknown>).forEach(([childKey, childValue]) => appendValue(fd, `${key}[${childKey}]`, childValue));
        return;
    }
    if (typeof value === 'boolean') {
        fd.append(key, value ? '1' : '0');
        return;
    }
    fd.append(key, String(value));
};

export const buildTaskFormData = (data: Record<string, unknown>): FormData => {
    const fd = new FormData();
    Object.entries(data).forEach(([key, value]) => appendValue(fd, key, value));
    return fd;
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- taskFormData`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/project-lazy/task/composables/taskFormData.ts resources/js/pages/project-lazy/task/composables/taskFormData.test.ts
git commit -m "feat(project-lazy): add taskFormData serializer for axios multipart submit"
```

---

## Task F3: SavedTaskPayload type + useTaskForm axios submit

**Files:**
- Modify: `resources/js/pages/project-lazy/index.d.ts` (add `SavedTaskPayload` near the other form types, ~line 343)
- Modify: `resources/js/pages/project-lazy/task/composables/useTaskForm.ts`
- Modify: `resources/js/pages/project-lazy/task/TaskForm.vue`
- Modify: `resources/js/pages/project-lazy/task/TaskFormDrawer.vue`
- Modify: `resources/js/pages/project-lazy/task/composables/useTaskFormDrawer.ts`
- Test: `resources/js/pages/project-lazy/task/composables/useTaskForm.test.ts` (create)

**Interfaces:**
- Produces:
  - `interface SavedTaskPayload { mode: 'create' | 'edit'; id: string; parentId: string | null; title: string; startDate: string | null; dueDate: string | null; isArchived: boolean; status: TaskStatusOption | null; type: TaskTypeOption | null; category: TaskCategoryOption | null; priority: TaskPriorityOption | null; users: SlimUser[] }`
  - `useTaskForm(...)` now also returns `processing: Ref<boolean>`.
  - `useTaskForm`'s emit is `{ (e: 'saved', payload: SavedTaskPayload): void; (e: 'close'): void }`.
- Consumes: `buildTaskFormData` (Task F2); `route('project.tasks.lazy-store' | 'project.tasks.lazy-update', ...)` (Task B2).

- [ ] **Step 1: Add `SavedTaskPayload` to `resources/js/pages/project-lazy/index.d.ts`** (append after `LazyTaskFormData`):

```ts
export interface SavedTaskPayload {
    mode: 'create' | 'edit';
    id: string;
    parentId: string | null;
    title: string;
    startDate: string | null;
    dueDate: string | null;
    isArchived: boolean;
    status: TaskStatusOption | null;
    type: TaskTypeOption | null;
    category: TaskCategoryOption | null;
    priority: TaskPriorityOption | null;
    users: SlimUser[];
}
```

- [ ] **Step 2: Write the failing test** `resources/js/pages/project-lazy/task/composables/useTaskForm.test.ts`

```ts
import type { LazyTaskFormProps } from '@/pages/project-lazy';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { postMock } = vi.hoisted(() => ({ postMock: vi.fn() }));

vi.mock('axios', () => ({
    default: { post: postMock, isAxiosError: (e: unknown): boolean => !!(e as { isAxiosError?: boolean })?.isAxiosError },
}));
vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.mock('@/composables/useProjectPermissions', () => ({
    useProjectPermissions: () => ({ canUpdateTaskField: () => true, canUpdateTaskStatus: () => true }),
}));
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: null } } }),
    useForm: (initial: Record<string, unknown>) => {
        const form: Record<string, unknown> = {
            ...initial,
            errors: {} as Record<string, string>,
            transform(fn: (d: Record<string, unknown>) => Record<string, unknown>) {
                form.__transform = fn;
                return form;
            },
            reset: vi.fn(),
            clearErrors: vi.fn(() => {
                form.errors = {};
            }),
            setError: vi.fn((key: string, message: string) => {
                (form.errors as Record<string, string>)[key] = message;
            }),
        };
        return form;
    },
}));

import { useTaskForm } from './useTaskForm';

const baseProps = (): LazyTaskFormProps => ({
    parentId: null,
    projectId: 'PROJ',
    task: null,
    tasks: [],
    taskTypes: [{ id: 'T1', name: 'Bug', severity: 'danger' }],
    taskStatuses: [{ id: 'S1', name: 'In Progress', severity: 'info', score: 50 }],
    taskPriorities: [{ id: 'P1', name: 'High', severity: 'warning' }],
    taskCategories: [{ id: 'C1', name: 'Task' }],
    tags: [],
    members: [],
});

beforeEach(() => {
    postMock.mockReset();
    (globalThis as unknown as { route: unknown }).route = vi.fn(() => '/lazy-store');
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('useTaskForm.submit (create)', () => {
    it('posts to the lazy-store route and emits a saved payload built from the returned id', async () => {
        postMock.mockResolvedValue({ data: { success: true, id: 'NEWID' } });
        const emit = vi.fn();
        const props = baseProps();
        const { form, submit } = useTaskForm(props, emit);
        form.title = 'Created';
        form.status_id = 'S1';
        form.priority_id = 'P1';
        form.type_id = 'T1';
        form.task_category_id = 'C1';

        await submit();

        expect((globalThis as unknown as { route: ReturnType<typeof vi.fn> }).route).toHaveBeenCalledWith(
            'project.tasks.lazy-store',
            { projectEncoded: 'PROJ' },
        );
        expect(postMock).toHaveBeenCalledTimes(1);
        const savedCall = emit.mock.calls.find((c) => c[0] === 'saved');
        expect(savedCall).toBeTruthy();
        const payload = savedCall![1];
        expect(payload.mode).toBe('create');
        expect(payload.id).toBe('NEWID');
        expect(payload.title).toBe('Created');
        expect(payload.status?.id).toBe('S1');
        expect(payload.status?.score).toBe(50);
    });

    it('maps a 422 response into form errors and does not emit saved', async () => {
        postMock.mockRejectedValue({ isAxiosError: true, response: { status: 422, data: { errors: { title: ['Required'] } } } });
        const emit = vi.fn();
        const { form, submit } = useTaskForm(baseProps(), emit);

        await submit();

        expect((form.errors as Record<string, string>).title).toBe('Required');
        expect(emit.mock.calls.find((c) => c[0] === 'saved')).toBeFalsy();
    });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `npm test -- useTaskForm`
Expected: FAIL — `submit` still posts via Inertia (`route` called with `project.tasks.store`, no `saved` payload).

- [ ] **Step 4: Rewrite `submit` and add `processing` in `useTaskForm.ts`**

Add imports at the top (keep existing imports):

```ts
import axios from 'axios';
import { buildTaskFormData } from './taskFormData';
import type { SavedTaskPayload, TaskCategoryOption, TaskPriorityOption, TaskStatusOption, TaskTypeOption } from '@/pages/project-lazy';
```

Change the emit interface:

```ts
interface TaskFormEmit {
    (e: 'close'): void;
    (e: 'saved', payload: SavedTaskPayload): void;
}
```

Add a `processing` ref next to the other refs (after `const validationErrors = ref<...>`):

```ts
    const processing = ref(false);
```

Replace the entire `submit` function body with:

```ts
    const submit = async (): Promise<void> => {
        if (props.onlyEpicCategory) {
            form.parent_id = null;
        }

        const existed = existedMembers.value.map((u) => u.id);
        const selected = selectedMembers.value.map((u) => u.id);

        form.assign_users = selected.filter((id) => !existed.includes(id));
        form.unassign_users = existed.filter((id) => !selected.includes(id));

        const oldTags = props?.task?.tags?.map((t) => String(t.id)) ?? [];
        const tagExist = selectedTags.value.filter((t) => t.id);
        const tagExistIds = tagExist.map((t) => t.id);
        const addTagExist = tagExistIds.filter((id) => !oldTags.includes(id));
        const addTagNew = selectedTags.value.filter((t) => !t.id);
        const removeTags = oldTags.filter((id) => !tagExistIds.includes(id));

        form.add_tag.new = addTagNew;
        form.add_tag.exists = addTagExist;
        form.remove_tag = removeTags;

        const requestData: Record<string, unknown> = {
            ...form.data(),
            start_date: form.start_date ? moment(form.start_date).format('YYYY-MM-DD') : null,
            due_date: form.due_date ? moment(form.due_date).format('YYYY-MM-DD') : null,
        };

        const url = isEdit.value
            ? route('project.tasks.lazy-update', { projectEncoded: props.projectId, taskEncoded: props.task!.id })
            : route('project.tasks.lazy-store', { projectEncoded: props.projectId });

        processing.value = true;
        form.clearErrors();

        try {
            const response = await axios.post(url, buildTaskFormData(requestData), {
                headers: { Accept: 'application/json' },
            });

            const id = isEdit.value ? props.task!.id : String(response.data.id);
            emit('saved', buildSavedPayload(id));
            emit('close');
            form.reset();
            validationErrors.value = {};
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const errors = (error.response.data?.errors ?? {}) as Record<string, string[] | string>;
                Object.entries(errors).forEach(([key, value]) => {
                    form.setError(key, Array.isArray(value) ? value[0] : String(value));
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: isEdit.value ? 'Failed to update task' : 'Failed to store task',
                    life: 3000,
                });
            }
        } finally {
            processing.value = false;
        }
    };

    const findOption = <T extends { id: string }>(list: T[], id: string | null): T | null =>
        id ? (list.find((option) => option.id === id) ?? null) : null;

    const buildSavedPayload = (id: string): SavedTaskPayload => ({
        mode: isEdit.value ? 'edit' : 'create',
        id,
        parentId: form.parent_id ?? null,
        title: form.title,
        startDate: form.start_date ? moment(form.start_date).format('YYYY-MM-DD') : null,
        dueDate: form.due_date ? moment(form.due_date).format('YYYY-MM-DD') : null,
        isArchived: form.is_archived,
        status: findOption<TaskStatusOption>(props.taskStatuses, form.status_id),
        type: findOption<TaskTypeOption>(props.taskTypes, form.type_id),
        category: findOption<TaskCategoryOption>(props.taskCategories ?? [], form.task_category_id),
        priority: findOption<TaskPriorityOption>(props.taskPriorities, form.priority_id),
        users: [...selectedMembers.value],
    });
```

> Note: `form.data()` is Inertia useForm's accessor for the current field values. The test's `useForm` mock spreads `initial` onto the form object; add a `data()` method to the mock if missing — but the simplest path is to read fields explicitly. If `form.data` is unavailable in the mock, replace `...form.data()` with an explicit object of the fields already present on `form` (`_method`, `project_id`, `title`, `description`, `type_id`, `status_id`, `priority_id`, `task_category_id`, `sprint_id`, `parent_id`, `is_archived`, `progress_value`, `assign_users`, `unassign_users`, `add_tag`, `remove_tag`, `attachments`). To avoid the mock gap, use the explicit object form in both code and ensure the test passes.

Finally, add `processing` to the returned object:

```ts
    return {
        form,
        processing,
        selectedMembers,
        // ...existing returns unchanged...
        onStatusChange,
        submit,
    };
```

- [ ] **Step 5: Update `TaskForm.vue`** — destructure `processing` and use it for the buttons.

Change the destructure to include `processing`:

```ts
const {
    form,
    processing,
    selectedMembers,
    // ...rest unchanged...
    submit,
} = useTaskForm(props, emit);
```

Change the footer buttons to use `processing` instead of `form.processing`:

```vue
            <Button label="Cancel" severity="secondary" @click="emit('close')" :disabled="processing" />
            <Button v-if="!isEdit" label="Create Task" @click="submit" icon="pi pi-save" :loading="processing" :disabled="processing" />
            <Button
                v-else
                label="Update Task"
                severity="warning"
                @click="submit"
                :loading="processing"
                :disabled="processing"
                icon="pi pi-save"
            />
```

- [ ] **Step 6: Update `useTaskFormDrawer.ts`** — forward the payload.

Change the `emit` parameter type and `onSaved`:

```ts
export const useTaskFormDrawer = (projectId: string, emit: (e: 'saved', payload: SavedTaskPayload) => void) => {
```

```ts
    const onSaved = (payload: SavedTaskPayload): void => {
        emit('saved', payload);
        close();
    };
```

Add the import at the top:

```ts
import type { ParentTaskNode, ParentTaskOption, SavedTaskPayload, TaskFormPayload } from '@/pages/project-lazy';
```

- [ ] **Step 7: Update `TaskFormDrawer.vue`** — change the emit type and pass-through.

```ts
import type { LazyMember, SavedTaskPayload, SlimUser, TagOption, TaskCategoryOption, TaskPriorityOption, TaskStatusOption, TaskTypeOption } from '@/pages/project-lazy';
```

```ts
const emit = defineEmits<{ (e: 'saved', payload: SavedTaskPayload): void }>();
```

(The template already binds `@saved="onSaved"` on `<TaskForm>`, and `onSaved` now carries the payload — no template change needed beyond what `useTaskFormDrawer` returns.)

- [ ] **Step 8: Run the test to verify it passes**

Run: `npm test -- useTaskForm`
Expected: PASS (2 tests). If `form.data()` is undefined in the mock, switch to the explicit-object variant described in the Step 4 note, then re-run.

- [ ] **Step 9: Commit**

```bash
git add resources/js/pages/project-lazy/index.d.ts resources/js/pages/project-lazy/task/composables/useTaskForm.ts resources/js/pages/project-lazy/task/composables/useTaskForm.test.ts resources/js/pages/project-lazy/task/TaskForm.vue resources/js/pages/project-lazy/task/TaskFormDrawer.vue resources/js/pages/project-lazy/task/composables/useTaskFormDrawer.ts
git commit -m "feat(project-lazy): submit task form via axios and emit a saved payload"
```

---

## Task F4: Live project progress (types + shell)

**Files:**
- Modify: `resources/js/types/type.ts`
- Modify: `resources/js/pages/project-lazy/layouts/ProjectShellLayout.vue`

**Interfaces:**
- Produces: `LiveProjectProgressKey: InjectionKey<Ref<number>>` (provided by the shell, injected by List). The shell passes a project object whose `progress` reflects the injected ref to `ProjectStats`.

- [ ] **Step 1: Add the injection key** to `resources/js/types/type.ts`:

```ts
import { InjectionKey, Ref } from 'vue';

export const ProjectPolicyKey: InjectionKey<App.Data.ProjectRole.ConfigData | null> = Symbol('project-policy');

export const LiveProjectProgressKey: InjectionKey<Ref<number>> = Symbol('live-project-progress');
```

- [ ] **Step 2: Wire the shell** in `resources/js/pages/project-lazy/layouts/ProjectShellLayout.vue`.

Update the import of `vue` to include `ref` and `watch` (already imports `computed`, `provide`):

```ts
import { computed, provide, ref, watch } from 'vue';
```

Add the key import next to the policy key import:

```ts
import { LiveProjectProgressKey, ProjectPolicyKey } from '@/types/type';
```

(Remove the now-duplicate `import { ProjectPolicyKey } from '@/types/type';` line if it exists separately.)

After `const project = computed(() => shell.value.project);`, add:

```ts
const liveProgress = ref(project.value.progress);
provide(LiveProjectProgressKey, liveProgress);
watch(
    () => project.value.progress,
    (value) => {
        liveProgress.value = value;
    },
);

const projectForStats = computed(() => ({ ...project.value, progress: liveProgress.value }));
```

Change the `ProjectStats` binding in the template to use the merged project:

```vue
            <ProjectStats
                :project="projectForStats"
                :statuses="shell.statuses || []"
                :priorities="shell.priorities || []"
                :canEdit="canEdit"
                @update="updateProject"
            />
```

- [ ] **Step 3: Typecheck**

Run: `npx vue-tsc --noEmit -p tsconfig.json 2>&1 | grep -E "type.ts|ProjectShellLayout" || echo "no new type errors in changed files"`
Expected: `no new type errors in changed files` (the repo has ~31 pre-existing unrelated type errors; do not introduce new ones in these files).

- [ ] **Step 4: Commit**

```bash
git add resources/js/types/type.ts resources/js/pages/project-lazy/layouts/ProjectShellLayout.vue
git commit -m "feat(project-lazy): provide live project progress to the shell header"
```

---

## Task F5: listTaskNode adapter + List.vue wiring

**Files:**
- Create: `resources/js/pages/project-lazy/task/nodes/listTaskNode.ts`
- Test: `resources/js/pages/project-lazy/task/nodes/listTaskNode.test.ts`
- Modify: `resources/js/pages/project-lazy/List.vue`

**Interfaces:**
- Produces: `buildListTaskNode(payload: SavedTaskPayload): ListTask`, `patchListTaskNode(node: ListTask, payload: SavedTaskPayload): void`.
- Consumes: `useLocalTaskTree` (F1), `LiveProjectProgressKey` (F4), `SavedTaskPayload` (F3).

- [ ] **Step 1: Write the failing test**

```ts
import type { SavedTaskPayload } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { buildListTaskNode, patchListTaskNode } from './listTaskNode';

const payload = (over: Partial<SavedTaskPayload> = {}): SavedTaskPayload => ({
    mode: 'create',
    id: 'X',
    parentId: null,
    title: 'T',
    startDate: null,
    dueDate: null,
    isArchived: false,
    status: { id: 'S1', name: 'In Progress', severity: 'info', score: 50 },
    type: { id: 'T1', name: 'Bug', severity: 'danger' },
    category: { id: 'C1', name: 'Task' },
    priority: { id: 'P1', name: 'High', severity: 'warning' },
    users: [],
    ...over,
});

describe('buildListTaskNode', () => {
    it('builds a leaf with progress from the status score and an empty children array', () => {
        const node = buildListTaskNode(payload({ id: 'N', parentId: 'P', title: 'New' }));
        expect(node.id).toBe('N');
        expect(node.parent_id).toBe('P');
        expect(node.progress).toBe(50);
        expect(node.sub_task_recursive).toEqual([]);
        expect(node.status?.id).toBe('S1');
    });

    it('sets completed_at and clears overdue when the status is Completed', () => {
        const node = buildListTaskNode(
            payload({ status: { id: 'S2', name: 'Completed', severity: 'success', score: 100 }, dueDate: '2000-01-01' }),
        );
        expect(node.completed_at).not.toBeNull();
        expect(node.is_overdue).toBe(false);
    });

    it('flags overdue for a past due date on a non-completed status', () => {
        const node = buildListTaskNode(payload({ dueDate: '2000-01-01' }));
        expect(node.is_overdue).toBe(true);
    });
});

describe('patchListTaskNode', () => {
    it('updates fields in place and keeps the existing children', () => {
        const node = buildListTaskNode(payload({ id: 'N' }));
        node.sub_task_recursive = [buildListTaskNode(payload({ id: 'child', parentId: 'N' }))];
        patchListTaskNode(node, payload({ id: 'N', title: 'Renamed', parentId: 'P2' }));
        expect(node.title).toBe('Renamed');
        expect(node.parent_id).toBe('P2');
        expect(node.sub_task_recursive).toHaveLength(1);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- listTaskNode`
Expected: FAIL — cannot resolve `./listTaskNode`.

- [ ] **Step 3: Write the implementation**

```ts
import type { ListTask, SavedTaskPayload, TaskStatusOption } from '@/pages/project-lazy';

const isCompleted = (status: TaskStatusOption | null): boolean => (status?.name ?? '').toLowerCase() === 'completed';

const computeOverdue = (dueDate: string | null, status: TaskStatusOption | null): boolean => {
    if (!dueDate || isCompleted(status)) {
        return false;
    }
    const today = new Date(new Date().toDateString());
    return new Date(dueDate) < today;
};

export const buildListTaskNode = (payload: SavedTaskPayload): ListTask => {
    const now = new Date().toISOString();
    return {
        id: payload.id,
        parent_id: payload.parentId,
        title: payload.title,
        progress: payload.status?.score ?? 0,
        start_date: payload.startDate,
        due_date: payload.dueDate,
        completed_at: isCompleted(payload.status) ? now : null,
        is_overdue: computeOverdue(payload.dueDate, payload.status),
        created_at: now,
        updated_at: now,
        status: payload.status,
        type: payload.type,
        category: payload.category,
        users: payload.users,
        sub_task_recursive: [],
    };
};

export const patchListTaskNode = (node: ListTask, payload: SavedTaskPayload): void => {
    node.title = payload.title;
    node.parent_id = payload.parentId;
    node.start_date = payload.startDate;
    node.due_date = payload.dueDate;
    node.status = payload.status;
    node.type = payload.type;
    node.category = payload.category;
    node.users = payload.users;
    node.completed_at = isCompleted(payload.status) ? (node.completed_at ?? new Date().toISOString()) : null;
    node.is_overdue = computeOverdue(payload.dueDate, payload.status);
    node.updated_at = new Date().toISOString();
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- listTaskNode`
Expected: PASS (4 tests).

- [ ] **Step 5: Wire `List.vue`** — replace the script and the `TaskTable` binding.

Replace the `<script setup>` block with:

```ts
import type { ListProps, ListTask, SavedTaskPayload } from './index';
import { LiveProjectProgressKey } from '@/types/type';
import { Deferred, Head } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';
import ListTableSkeleton from './partials/ListTableSkeleton.vue';
import TaskFormDrawer from './task/TaskFormDrawer.vue';
import TaskTable from './task/TaskTable.vue';
import { useLocalTaskTree } from './task/composables/useLocalTaskTree';
import { buildListTaskNode, patchListTaskNode } from './task/nodes/listTaskNode';

const props = defineProps<ListProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const liveProgress = inject(LiveProjectProgressKey, null);

const { tasks, applySaved } = useLocalTaskTree<ListTask>(
    computed(() => props.tasks),
    {
        onProjectProgress: (value) => {
            if (liveProgress) {
                liveProgress.value = value;
            }
        },
    },
);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: ListTask) => drawer.value?.openEdit(task);
const onSaved = (payload: SavedTaskPayload) => applySaved(payload, { build: buildListTaskNode, patch: patchListTaskNode });
```

Change the `TaskTable` `:tasks` binding in the template from `:tasks="props.tasks"` to `:tasks="tasks"`. Leave everything else in the template unchanged.

- [ ] **Step 6: Run the node test + typecheck the page**

Run: `npm test -- listTaskNode`
Expected: PASS.
Run: `npx vue-tsc --noEmit -p tsconfig.json 2>&1 | grep -E "List.vue|listTaskNode" || echo "no new type errors in changed files"`
Expected: `no new type errors in changed files`.

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/project-lazy/task/nodes/listTaskNode.ts resources/js/pages/project-lazy/task/nodes/listTaskNode.test.ts resources/js/pages/project-lazy/List.vue
git commit -m "feat(project-lazy): wire List tab to local task state + live progress"
```

---

## Task F6: kanbanCardNode adapter + Kanban.vue wiring

**Files:**
- Create: `resources/js/pages/project-lazy/task/nodes/kanbanCardNode.ts`
- Test: `resources/js/pages/project-lazy/task/nodes/kanbanCardNode.test.ts`
- Modify: `resources/js/pages/project-lazy/Kanban.vue`

**Interfaces:**
- Produces: `buildKanbanCardNode(payload: SavedTaskPayload): KanbanCard`, `patchKanbanCardNode(node: KanbanCard, payload: SavedTaskPayload): void`.
- Note: Kanban holds only the active-sprint task set, so it does NOT push project progress to the header (the header would otherwise show an average of a partial set). Kanban only recomputes its own visible cards' progress and keeps `@statusUpdate` on the existing server reload.

- [ ] **Step 1: Write the failing test**

```ts
import type { SavedTaskPayload } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { buildKanbanCardNode, patchKanbanCardNode } from './kanbanCardNode';

const payload = (over: Partial<SavedTaskPayload> = {}): SavedTaskPayload => ({
    mode: 'create',
    id: 'X',
    parentId: null,
    title: 'T',
    startDate: null,
    dueDate: null,
    isArchived: false,
    status: { id: 'S1', name: 'In Progress', severity: 'info', score: 50 },
    type: { id: 'T1', name: 'Bug', severity: 'danger' },
    category: { id: 'C1', name: 'Task' },
    priority: { id: 'P1', name: 'High', severity: 'warning' },
    users: [],
    ...over,
});

describe('buildKanbanCardNode', () => {
    it('builds a leaf card with progress from the status score and a priority', () => {
        const card = buildKanbanCardNode(payload({ id: 'N', parentId: 'P' }));
        expect(card.id).toBe('N');
        expect(card.parent_id).toBe('P');
        expect(card.progress).toBe(50);
        expect(card.priority?.id).toBe('P1');
        expect(card.sub_task_recursive).toEqual([]);
    });
});

describe('patchKanbanCardNode', () => {
    it('updates fields in place and keeps children', () => {
        const card = buildKanbanCardNode(payload({ id: 'N' }));
        card.sub_task_recursive = [buildKanbanCardNode(payload({ id: 'c', parentId: 'N' }))];
        patchKanbanCardNode(card, payload({ id: 'N', title: 'Renamed', parentId: 'P2' }));
        expect(card.title).toBe('Renamed');
        expect(card.parent_id).toBe('P2');
        expect(card.sub_task_recursive).toHaveLength(1);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- kanbanCardNode`
Expected: FAIL — cannot resolve `./kanbanCardNode`.

- [ ] **Step 3: Write the implementation**

```ts
import type { KanbanCard, SavedTaskPayload, TaskStatusOption } from '@/pages/project-lazy';

const isCompleted = (status: TaskStatusOption | null): boolean => (status?.name ?? '').toLowerCase() === 'completed';

const computeOverdue = (dueDate: string | null, status: TaskStatusOption | null): boolean => {
    if (!dueDate || isCompleted(status)) {
        return false;
    }
    const today = new Date(new Date().toDateString());
    return new Date(dueDate) < today;
};

export const buildKanbanCardNode = (payload: SavedTaskPayload): KanbanCard => ({
    id: payload.id,
    parent_id: payload.parentId,
    title: payload.title,
    description: null,
    start_date: payload.startDate,
    due_date: payload.dueDate,
    progress: payload.status?.score ?? 0,
    is_overdue: computeOverdue(payload.dueDate, payload.status),
    status: payload.status,
    priority: payload.priority,
    type: payload.type,
    users: payload.users,
    sub_task_recursive: [],
});

export const patchKanbanCardNode = (node: KanbanCard, payload: SavedTaskPayload): void => {
    node.title = payload.title;
    node.parent_id = payload.parentId;
    node.start_date = payload.startDate;
    node.due_date = payload.dueDate;
    node.status = payload.status;
    node.priority = payload.priority;
    node.type = payload.type;
    node.users = payload.users;
    node.is_overdue = computeOverdue(payload.dueDate, payload.status);
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- kanbanCardNode`
Expected: PASS (2 tests).

- [ ] **Step 5: Wire `Kanban.vue`** — replace the script and the `KanbanBoard` binding.

Replace the `<script setup>` block with:

```ts
import KanbanBoard from '@/pages/project/task/partials/TaskKanbanBoard.vue';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { KanbanCard, KanbanProps, SavedTaskPayload } from './index';
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';
import KanbanBoardSkeleton from './partials/KanbanBoardSkeleton.vue';
import TaskFormDrawer from './task/TaskFormDrawer.vue';
import { useLocalTaskTree } from './task/composables/useLocalTaskTree';
import { buildKanbanCardNode, patchKanbanCardNode } from './task/nodes/kanbanCardNode';

const props = defineProps<KanbanProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const { tasks, applySaved } = useLocalTaskTree<KanbanCard>(computed(() => props.tasks), {});

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: { id: string; parent_id?: string | null }) => drawer.value?.openEdit(task);
const onStatusUpdate = () => router.reload({ only: ['tasks'] });
const onSaved = (payload: SavedTaskPayload) => applySaved(payload, { build: buildKanbanCardNode, patch: patchKanbanCardNode });
```

Change `<KanbanBoard :tasks="props.tasks" ...>` to `<KanbanBoard :tasks="tasks" ...>` and change the drawer binding from `@saved="onStatusUpdate"` to `@saved="onSaved"`. Leave `@statusUpdate="onStatusUpdate"` as-is.

- [ ] **Step 6: Run the node test + typecheck the page**

Run: `npm test -- kanbanCardNode`
Expected: PASS.
Run: `npx vue-tsc --noEmit -p tsconfig.json 2>&1 | grep -E "Kanban.vue|kanbanCardNode" || echo "no new type errors in changed files"`
Expected: `no new type errors in changed files`.

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/project-lazy/task/nodes/kanbanCardNode.ts resources/js/pages/project-lazy/task/nodes/kanbanCardNode.test.ts resources/js/pages/project-lazy/Kanban.vue
git commit -m "feat(project-lazy): wire Kanban tab to local card state on save"
```

---

## Task V: Integration verification

**Files:** none (verification only).

- [ ] **Step 1: Full frontend test suite**

Run: `npm test`
Expected: PASS (all suites green, including the new ones).

- [ ] **Step 2: Lint + format frontend**

Run: `npm run lint && npm run format:check`
Expected: no errors in changed files. (`npm run lint` runs `eslint . --fix`; if the project's eslint config is broken as noted in memory, fall back to `npx prettier --check resources/js/pages/project-lazy resources/js/types/type.ts`.)

- [ ] **Step 3: Format PHP**

Run: `vendor/bin/pint`
Expected: clean (no style violations remaining).

- [ ] **Step 4: Backend tests (DB SAFETY GATE)**

First confirm target DB (do NOT skip): `APP_ENV=testing php artisan tinker --execute="echo config('database.connections.pgsql.database');"` — confirm it is the disposable test DB. Only then:
Run: `APP_ENV=testing php artisan test tests/Feature/ProjectLazy/LazyTaskWriteTest.php`
Expected: PASS.

- [ ] **Step 5: Verify routes are registered**

Run: `php artisan route:list --name=tasks.lazy`
Expected: lists `project.tasks.lazy-store` (POST) and `project.tasks.lazy-update` (PUT).

- [ ] **Step 6: Manual smoke (optional, recommended)**

Build assets (`npm run build`) or run `composer run dev`, open the project List tab, create a subtask, and confirm: the new row appears under its parent without a full reload, the parent's progress bar updates to the average, and the project Progress card in the header updates live. Repeat an edit (change a leaf's status) and confirm the progress propagates up.

---

## Self-Review Notes (resolved)

- **Spec coverage:** new endpoint (B2/B3), create-time progress recalc gap (B1), local tree + recursive progress (F1), axios submit returning to local state (F3), project header live (F4), List wiring (F5), Kanban wiring (F6), tests throughout. ✓
- **Kanban project-progress caveat:** Kanban's task set is active-sprint-only, so it intentionally does NOT drive the header project progress (F6 note) — this is the one deliberate deviation from "apply to both" and is documented.
- **Type consistency:** `SavedTaskPayload` (F3) is consumed identically by `useLocalTaskTree` (F1), `listTaskNode` (F5), `kanbanCardNode` (F6); `NodeAdapter.build/patch` names match across F1/F5/F6; `LiveProjectProgressKey` defined in F4 and injected in F5. ✓
- **No placeholders:** every code step contains full code; backend test setup is copied from the verified `ProjectTabTest` pattern. ✓
