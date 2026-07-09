# MCP Dynamic Query Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan MCP tool query dinamis read-only (SELECT + join + relasi) ke model Task/Project, dengan scoping visibilitas per-user dan id ter-encode Sqids.

**Architecture:** Satu security boundary deklaratif (`config/mcp_query.php`) dibaca oleh `QueryRegistry`. `DynamicQueryService` memvalidasi input terhadap registry, membangun query (mode relasi via Eloquent, mode join/agregasi via `DB::table`), menyuntikkan scope visibilitas ke setiap tabel project-scoped, lalu meng-encode id output. Dua MCP tool (`query-data`, `list-queryable-models`) membungkusnya dan didaftarkan di `ProjectManagementServer`.

**Tech Stack:** Laravel 13, laravel/mcp v0, Pest v4, PostgreSQL, Sqids (via `App\Facades\Sqids`), Passport (`auth:api`).

## Global Constraints

- **Read-only**: hanya SELECT. Service tidak boleh memanggil insert/update/delete/raw-mutation. Kedua tool memakai `#[IsReadOnly]` + `#[IsIdempotent]`.
- **Allowlist**: apa pun (model/kolom/relasi/join target/operator/fungsi) yang tidak ada di `config/mcp_query.php` DITOLAK.
- **Scope visibilitas**: tiru `Project::scopeVisibleFor` — role di `super-admin-admin`/`watcher-admin` melihat semua; selain itu dibatasi ke project tempat user jadi member. Constraint disuntikkan ke SETIAP tabel project-scoped (base, join, dan closure `with`).
- **Sqids simetris**: kolom bernama `id` atau berakhiran `_id` → input di-decode ke int, output di-encode via `Sqids::rec_encode_ids_in_list`.
- **Default select = allowlist kolom** (bukan `*`) — menjamin kolom sensitif (`users.password`, `users.remember_token`, `uuid`) tak pernah terpilih.
- **Limit**: default 50, hard-max 200.
- **Soft delete**: default `whereNull(deleted_at)` untuk tabel ber-`deleted_at`; hanya disertakan saat `with_trashed: true`.
- **PHP style**: curly braces selalu, constructor property promotion, return type & type hints eksplisit, PHPDoc array-shape. Jalankan `vendor/bin/pint --dirty --format agent` setelah mengubah PHP.

### ⚠️ Keamanan database test (WAJIB dibaca sebelum menjalankan test apa pun)

Test memakai `RefreshDatabase` (drop + `migrate:fresh`). Suite ini **pgsql-only** dan mengambil koneksi dari `.env.testing`. **Sebelum menjalankan `php artisan test` pertama kali**, verifikasi target adalah database test disposable, BUKAN dev/prod:

```bash
APP_ENV=testing php artisan tinker --execute="echo config('database.connections.pgsql.database');"
```

Konfirmasikan hasilnya ke user bahwa itu database test sebelum lanjut. Aturan ini berlaku untuk setiap langkah "Run test" di plan ini.

---

## File Structure

**Dibuat:**
- `config/mcp_query.php` — registry/whitelist (data murni, aman untuk `config:cache`).
- `app/Services/DynamicQuery/QueryRegistry.php` — accessor tipenya ke config.
- `app/Services/DynamicQuery/QueryException.php` — exception validasi (pesan actionable untuk AI).
- `app/Services/DynamicQuery/DynamicQueryService.php` — validasi + build + eksekusi + encode.
- `app/Mcp/Tools/QueryableSchemaTool.php` — tool introspeksi (`list-queryable-models`).
- `app/Mcp/Tools/QueryDataTool.php` — tool utama (`query-data`).
- `tests/Unit/DynamicQuery/QueryRegistryTest.php`
- `tests/Unit/DynamicQuery/DynamicQueryValidationTest.php`
- `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
- `tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php`
- `tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php`

**Diubah:**
- `app/Mcp/Servers/ProjectManagementServer.php` — daftarkan 2 tool.

---

## Task 1: Registry config + QueryRegistry accessor

**Files:**
- Create: `config/mcp_query.php`
- Create: `app/Services/DynamicQuery/QueryRegistry.php`
- Create: `app/Services/DynamicQuery/QueryException.php`
- Test: `tests/Unit/DynamicQuery/QueryRegistryTest.php`

**Interfaces:**
- Produces:
  - `QueryException extends \RuntimeException` (namespace `App\Services\DynamicQuery`).
  - `QueryRegistry` methods:
    - `has(string $alias): bool`
    - `get(string $alias): array` — throws `QueryException` if unknown. Returns the model definition array.
    - `models(): array<string, array<string, mixed>>` — all model definitions keyed by alias.
    - `defaults(): array{limit:int, max_limit:int, allowed_operators:list<string>, allowed_aggregates:list<string>, super_admin_roles:list<string>}`
  - Model definition array shape:
    ```
    [
      'table' => string,
      'model' => class-string,
      'scope' => 'project'|'global',
      'project_key' => array|null,   // null for global
      'soft_delete' => bool,
      'columns' => list<string>,
      'relations' => array<string,string>,  // relationName => targetAlias
      'joinable' => list<string>,            // target aliases allowed to join
    ]
    ```
    `project_key` variants:
    - `['type' => 'column', 'column' => 'project_id']` (direct FK; `'id'` for the `project` model itself)
    - `['type' => 'subquery', 'column' => 'sprint_id', 'via_table' => 'project_sprints', 'via_select' => 'id', 'via_where' => 'project_id', 'via_soft_delete' => true]`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/DynamicQuery/QueryRegistryTest.php`:

```php
<?php

use App\Services\DynamicQuery\QueryException;
use App\Services\DynamicQuery\QueryRegistry;

it('returns a known model definition', function () {
    $registry = app(QueryRegistry::class);

    expect($registry->has('task'))->toBeTrue();

    $task = $registry->get('task');

    expect($task['table'])->toBe('tasks')
        ->and($task['model'])->toBe(\App\Models\Task::class)
        ->and($task['scope'])->toBe('project')
        ->and($task['columns'])->toContain('title')
        ->and($task['relations'])->toHaveKey('status');
});

it('throws an actionable error for an unknown model', function () {
    $registry = app(QueryRegistry::class);

    expect(fn () => $registry->get('nope'))
        ->toThrow(QueryException::class, "Unknown model 'nope'");
});

it('never exposes sensitive user columns', function () {
    $registry = app(QueryRegistry::class);

    $columns = $registry->get('user')['columns'];

    expect($columns)->not->toContain('password')
        ->and($columns)->not->toContain('remember_token')
        ->and($columns)->toContain('email');
});

it('exposes defaults with a hard limit cap', function () {
    $defaults = app(QueryRegistry::class)->defaults();

    expect($defaults['limit'])->toBe(50)
        ->and($defaults['max_limit'])->toBe(200)
        ->and($defaults['allowed_operators'])->toContain('like')
        ->and($defaults['super_admin_roles'])->toContain('super-admin-admin');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/DynamicQuery/QueryRegistryTest.php`
Expected: FAIL — `Class "App\Services\DynamicQuery\QueryRegistry" not found`.

- [ ] **Step 3: Create the QueryException**

Create `app/Services/DynamicQuery/QueryException.php`:

```php
<?php

namespace App\Services\DynamicQuery;

use RuntimeException;

/**
 * Thrown when a dynamic query request violates the registry allowlist or
 * structural rules. The message is surfaced verbatim to the AI client.
 */
class QueryException extends RuntimeException
{
}
```

- [ ] **Step 4: Create the config registry**

Create `config/mcp_query.php`:

```php
<?php

use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsSprintStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;
use App\Models\SprintTask;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskUser;
use App\Models\User;

return [

    'defaults' => [
        'limit' => 50,
        'max_limit' => 200,
        'allowed_operators' => ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'not in', 'is null', 'is not null'],
        'allowed_aggregates' => ['count', 'sum', 'avg', 'min', 'max'],
        // Mirrors App\Models\Project::scopeVisibleFor().
        'super_admin_roles' => ['super-admin-admin', 'watcher-admin'],
    ],

    'models' => [

        'project' => [
            'table' => 'projects',
            'model' => Project::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_no', 'code', 'status_id', 'priority_id', 'owner_id', 'owned_id', 'emoji', 'title', 'description', 'start_date', 'due_date', 'progress', 'sequence_number', 'created_at', 'updated_at'],
            'relations' => [
                'status' => 'ms_project_status',
                'priority' => 'ms_project_priority',
                'owner' => 'user',
                'owned' => 'user',
                'projectMembers' => 'project_member',
                'sprints' => 'project_sprint',
                'tasks' => 'task',
            ],
            'joinable' => ['ms_project_status', 'ms_project_priority', 'user', 'project_member', 'project_sprint', 'task'],
        ],

        'task' => [
            'table' => 'tasks',
            'model' => Task::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'owned_id', 'parent_id', 'status_id', 'priority_id', 'type_id', 'project_id', 'task_category_id', 'created_by', 'updated_by', 'emoji', 'title', 'description', 'progress', 'story_points', 'sequence_number', 'is_archived', 'start_date', 'due_date', 'completed_at', 'created_at', 'updated_at'],
            'relations' => [
                'owner' => 'user',
                'parent' => 'task',
                'children' => 'task',
                'status' => 'ms_task_status',
                'priority' => 'ms_task_priority',
                'type' => 'ms_task_type',
                'category' => 'task_category',
                'project' => 'project',
                'creator' => 'user',
                'users' => 'user',
                'sprints' => 'project_sprint',
                'tags' => 'tag',
            ],
            'joinable' => ['ms_task_status', 'ms_task_priority', 'ms_task_type', 'task_category', 'project', 'user', 'project_sprint', 'sprint_task'],
        ],

        'project_sprint' => [
            'table' => 'project_sprints',
            'model' => ProjectSprint::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_id', 'sprint_status_id', 'name', 'goal', 'duration', 'start_date', 'end_date', 'order', 'retrospective', 'created_at', 'updated_at'],
            'relations' => [
                'project' => 'project',
                'status' => 'ms_sprint_status',
                'tasks' => 'task',
            ],
            'joinable' => ['project', 'ms_sprint_status', 'sprint_task', 'task'],
        ],

        'sprint_task' => [
            'table' => 'sprint_task',
            'model' => SprintTask::class,
            'scope' => 'project',
            'project_key' => ['type' => 'subquery', 'column' => 'sprint_id', 'via_table' => 'project_sprints', 'via_select' => 'id', 'via_where' => 'project_id', 'via_soft_delete' => true],
            'soft_delete' => false,
            'columns' => ['id', 'sprint_id', 'task_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_sprint', 'task'],
        ],

        'project_member' => [
            'table' => 'project_members',
            'model' => ProjectMember::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_id', 'user_id', 'project_role_id', 'owned_id', 'is_active', 'created_at', 'updated_at'],
            'relations' => [
                'project' => 'project',
                'user' => 'user',
                'role' => 'ms_project_role',
                'owned' => 'user',
            ],
            'joinable' => ['project', 'user', 'ms_project_role'],
        ],

        'task_user' => [
            'table' => 'task_users',
            'model' => TaskUser::class,
            'scope' => 'project',
            'project_key' => ['type' => 'subquery', 'column' => 'task_id', 'via_table' => 'tasks', 'via_select' => 'id', 'via_where' => 'project_id', 'via_soft_delete' => true],
            'soft_delete' => true,
            'columns' => ['id', 'task_id', 'user_id', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task', 'user'],
        ],

        'user' => [
            'table' => 'users',
            'model' => User::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            // Deliberately excludes password, remember_token, uuid.
            'columns' => ['id', 'name', 'email', 'is_active', 'created_at', 'updated_at'],
            'relations' => [
                'projects' => 'project',
                'projectMembers' => 'project_member',
                'createdTasks' => 'task',
            ],
            'joinable' => ['project_member', 'task_user'],
        ],

        'ms_project_status' => [
            'table' => 'ms_project_statuses',
            'model' => MsProjectStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project'],
        ],

        'ms_project_priority' => [
            'table' => 'ms_project_priority',
            'model' => MsProjectPriority::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project'],
        ],

        'ms_task_status' => [
            'table' => 'ms_task_statuses',
            'model' => MsTaskStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'score', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'ms_task_priority' => [
            'table' => 'ms_task_priorities',
            'model' => MsTaskPriority::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'ms_task_type' => [
            'table' => 'ms_task_types',
            'model' => MsTaskType::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'task_category' => [
            'table' => 'task_categories',
            'model' => TaskCategory::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'icon', 'severity', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'tag' => [
            'table' => 'tags',
            'model' => Tag::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => [],
        ],

        'ms_sprint_status' => [
            'table' => 'ms_sprint_statuses',
            'model' => MsSprintStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => false,
            'columns' => ['id', 'name', 'severity', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_sprint'],
        ],

        'ms_project_role' => [
            'table' => 'ms_project_roles',
            'model' => MsProjectRole::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_member'],
        ],

    ],
];
```

> Note: verifikasi kelas `App\Models\MsSprintStatus`, `App\Models\Tag`, `App\Models\TaskUser` benar-benar ada (mereka direferensikan oleh relasi model yang sudah dibaca saat brainstorming). Jika nama berbeda, sesuaikan `use` di atas.

- [ ] **Step 5: Create the QueryRegistry**

Create `app/Services/DynamicQuery/QueryRegistry.php`:

```php
<?php

namespace App\Services\DynamicQuery;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Typed accessor over config/mcp_query.php — the single source of truth for
 * which models, columns, relations, joins and operators are queryable.
 */
class QueryRegistry
{
    public function __construct(protected Config $config) {}

    public function has(string $alias): bool
    {
        return $this->config->has("mcp_query.models.{$alias}");
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $alias): array
    {
        if (! $this->has($alias)) {
            throw new QueryException("Unknown model '{$alias}'. Call the list-queryable-models tool to see available models.");
        }

        return $this->config->get("mcp_query.models.{$alias}");
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function models(): array
    {
        return $this->config->get('mcp_query.models', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return $this->config->get('mcp_query.defaults', []);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/DynamicQuery/QueryRegistryTest.php`
Expected: PASS (4 passed).

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/mcp_query.php app/Services/DynamicQuery/QueryRegistry.php app/Services/DynamicQuery/QueryException.php tests/Unit/DynamicQuery/QueryRegistryTest.php
git commit -m "feat(mcp): add dynamic query registry and config allowlist"
```

---

## Task 2: Query validation & normalization (pure, no DB)

**Files:**
- Create: `app/Services/DynamicQuery/DynamicQueryService.php`
- Test: `tests/Unit/DynamicQuery/DynamicQueryValidationTest.php`

**Interfaces:**
- Consumes: `QueryRegistry` (Task 1), `QueryException` (Task 1).
- Produces: `DynamicQueryService::validate(array $query): array` — validates the raw query against the registry and returns a normalized query with this shape:
  ```
  [
    'mode' => 'relation'|'join',
    'alias' => string, 'table' => string, 'model' => class-string,
    'soft_delete' => bool,
    'select' => list<string>,          // relation mode: plain "col"; join mode: "table.col"
    'distinct' => bool,
    'joins' => list<array{type:'inner'|'left', alias:string, table:string, on:list<array{left:string,operator:string,right:string}>}>,
    'with' => list<string>,
    'with_targets' => array<string,string>,   // relationName => targetAlias
    'filters' => list<array{boolean:'and'|'or', column:string, operator:string, value:mixed, values:array|null, is_id:bool}>,
    'group_by' => list<string>,
    'aggregates' => list<array{function:string, column:string, alias:string}>,
    'order_by' => list<array{column:string, direction:'asc'|'desc'}>,
    'limit' => int, 'offset' => int, 'with_trashed' => bool,
    'tables' => list<string>,          // base + joined tables (for scope/soft-delete)
    'table_scope' => array<string,string>,  // table => alias (to resolve scope def per table)
  ]
  ```
  Throws `QueryException` on any allowlist/structural violation. Id filter values are decoded to int here.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/DynamicQuery/DynamicQueryValidationTest.php`:

```php
<?php

use App\Facades\Sqids;
use App\Services\DynamicQuery\DynamicQueryService;
use App\Services\DynamicQuery\QueryException;

beforeEach(function () {
    $this->service = app(DynamicQueryService::class);
});

it('defaults select to the allowlist and applies limit/mode defaults', function () {
    $n = $this->service->validate(['model' => 'task']);

    expect($n['mode'])->toBe('relation')
        ->and($n['table'])->toBe('tasks')
        ->and($n['limit'])->toBe(50)
        ->and($n['offset'])->toBe(0)
        ->and($n['select'])->toContain('title')      // allowlist-derived
        ->and($n['tables'])->toBe(['tasks']);
});

it('rejects an unknown column', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'select' => ['no_such_column'],
    ]))->toThrow(QueryException::class, 'no_such_column');
});

it('rejects an operator outside the allowlist', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'filters' => [['column' => 'progress', 'operator' => 'BETWEEN', 'value' => 1]],
    ]))->toThrow(QueryException::class, 'operator');
});

it('rejects combining with and aggregates', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'with' => ['status'],
        'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
    ]))->toThrow(QueryException::class, 'cannot be combined');
});

it('caps the limit at the hard maximum', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'limit' => 5000,
    ]))->toThrow(QueryException::class, 'limit');
});

it('rejects a join to a non-joinable model', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'joins' => [[
            'model' => 'ms_project_role',
            'on' => [['left' => 'tasks.id', 'right' => 'ms_project_roles.id']],
        ]],
    ]))->toThrow(QueryException::class, 'join');
});

it('decodes sqid id filter values to integers', function () {
    $encoded = Sqids::encode(123);

    $n = $this->service->validate([
        'model' => 'task',
        'filters' => [['column' => 'project_id', 'operator' => '=', 'value' => $encoded]],
    ]);

    expect($n['filters'][0]['value'])->toBe(123)
        ->and($n['filters'][0]['is_id'])->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/DynamicQuery/DynamicQueryValidationTest.php`
Expected: FAIL — `Class "App\Services\DynamicQuery\DynamicQueryService" not found`.

- [ ] **Step 3: Create DynamicQueryService with validate() and its private helpers**

Create `app/Services/DynamicQuery/DynamicQueryService.php`:

```php
<?php

namespace App\Services\DynamicQuery;

use App\Facades\Sqids;

class DynamicQueryService
{
    public function __construct(protected QueryRegistry $registry) {}

    /**
     * Validate a raw query against the registry and return a normalized query.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validate(array $query): array
    {
        $defaults = $this->registry->defaults();

        $alias = $query['model'] ?? null;

        if (! is_string($alias) || $alias === '') {
            throw new QueryException('You must provide a `model`. Call the list-queryable-models tool to see available models.');
        }

        $def = $this->registry->get($alias);

        $joins = $this->normalizeJoins($query['joins'] ?? [], $def, $defaults);
        $aggregates = $this->normalizeAggregates($query['aggregates'] ?? [], $defaults);
        $groupBy = $query['group_by'] ?? [];
        $with = array_values($query['with'] ?? []);

        $mode = (! empty($joins) || ! empty($aggregates) || ! empty($groupBy)) ? 'join' : 'relation';

        if (! empty($with) && $mode === 'join') {
            throw new QueryException('`with` (relation loading) cannot be combined with joins/aggregates/group_by. Use either relation mode or join mode.');
        }

        // Tables available for column/scope resolution: base + joined.
        $tables = array_merge([$def['table']], array_map(fn ($j) => $j['table'], $joins));
        $tableScope = [$def['table'] => $alias];
        foreach ($joins as $join) {
            $tableScope[$join['table']] = $join['alias'];
        }

        $select = $this->normalizeSelect($query['select'] ?? [], $def, $mode, $tables, $tableScope);
        $withTargets = $this->normalizeWith($with, $def);
        $filters = $this->normalizeFilters($query['filters'] ?? [], $def, $mode, $tables, $tableScope, $defaults);
        $orderBy = $this->normalizeOrderBy($query['order_by'] ?? [], $def, $mode, $tables, $tableScope, $aggregates);
        $groupBy = $this->normalizeColumnList($groupBy, $def, $mode, $tables, $tableScope, 'group_by');

        if (! empty($aggregates)) {
            foreach ($select as $col) {
                if (! in_array($col, $groupBy, true)) {
                    throw new QueryException("When using aggregates, every selected column must appear in group_by. '{$col}' does not.");
                }
            }
        }

        $limit = (int) ($query['limit'] ?? $defaults['limit']);

        if ($limit < 1 || $limit > $defaults['max_limit']) {
            throw new QueryException("`limit` must be between 1 and {$defaults['max_limit']}.");
        }

        return [
            'mode' => $mode,
            'alias' => $alias,
            'table' => $def['table'],
            'model' => $def['model'],
            'soft_delete' => $def['soft_delete'],
            'select' => $select,
            'distinct' => (bool) ($query['distinct'] ?? false),
            'joins' => $joins,
            'with' => $with,
            'with_targets' => $withTargets,
            'filters' => $filters,
            'group_by' => $groupBy,
            'aggregates' => $aggregates,
            'order_by' => $orderBy,
            'limit' => $limit,
            'offset' => max(0, (int) ($query['offset'] ?? 0)),
            'with_trashed' => (bool) ($query['with_trashed'] ?? false),
            'tables' => $tables,
            'table_scope' => $tableScope,
        ];
    }

    /**
     * @param  array<int, mixed>  $joins
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    protected function normalizeJoins(array $joins, array $def, array $defaults): array
    {
        $available = [$def['table']];
        $availableAliases = [$def['alias'] ?? null];
        $joinableFrom = $def['joinable'];
        $normalized = [];

        foreach ($joins as $join) {
            $targetAlias = $join['model'] ?? null;

            if (! is_string($targetAlias) || ! in_array($targetAlias, $joinableFrom, true)) {
                throw new QueryException("Cannot join model '".($targetAlias ?? '?')."' from '{$def['table']}'. Allowed join targets: ".implode(', ', $joinableFrom).'.');
            }

            $targetDef = $this->registry->get($targetAlias);
            $type = ($join['type'] ?? 'inner') === 'left' ? 'left' : 'inner';

            $on = [];
            foreach (($join['on'] ?? []) as $cond) {
                $left = $this->assertQualifiedColumn($cond['left'] ?? '', array_merge($available, [$targetDef['table']]));
                $right = $this->assertQualifiedColumn($cond['right'] ?? '', array_merge($available, [$targetDef['table']]));
                $op = $cond['operator'] ?? '=';

                if (! in_array($op, ['=', '!=', '>', '>=', '<', '<='], true)) {
                    throw new QueryException("Invalid join operator '{$op}'.");
                }

                $on[] = ['left' => $left, 'operator' => $op, 'right' => $right];
            }

            if (empty($on)) {
                throw new QueryException("Join to '{$targetAlias}' requires at least one `on` condition.");
            }

            $normalized[] = ['type' => $type, 'alias' => $targetAlias, 'table' => $targetDef['table'], 'on' => $on];
            $available[] = $targetDef['table'];
            $joinableFrom = array_merge($joinableFrom, $targetDef['joinable']);
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $aggregates
     * @param  array<string, mixed>  $defaults
     * @return list<array{function:string, column:string, alias:string}>
     */
    protected function normalizeAggregates(array $aggregates, array $defaults): array
    {
        $normalized = [];

        foreach ($aggregates as $agg) {
            $fn = strtolower((string) ($agg['function'] ?? ''));

            if (! in_array($fn, $defaults['allowed_aggregates'], true)) {
                throw new QueryException("Invalid aggregate function '{$fn}'. Allowed: ".implode(', ', $defaults['allowed_aggregates']).'.');
            }

            $column = (string) ($agg['column'] ?? '');

            if ($column !== '*' && ! preg_match('/^[a-z_][a-z0-9_]*\.[a-z_][a-z0-9_]*$/i', $column)) {
                throw new QueryException("Aggregate column must be '*' or a qualified `table.column`. Got '{$column}'.");
            }

            $alias = (string) ($agg['alias'] ?? '');

            if (! preg_match('/^[a-z_][a-z0-9_]*$/i', $alias)) {
                throw new QueryException("Aggregate alias '{$alias}' must match [a-z_][a-z0-9_]*.");
            }

            $normalized[] = ['function' => $fn, 'column' => $column, 'alias' => $alias];
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $select
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @return list<string>
     */
    protected function normalizeSelect(array $select, array $def, string $mode, array $tables, array $tableScope): array
    {
        if (empty($select)) {
            // Default: the base model's allowlisted columns (never '*' — protects sensitive columns).
            return $mode === 'join'
                ? array_map(fn ($c) => "{$def['table']}.{$c}", $def['columns'])
                : $def['columns'];
        }

        return array_map(function (string $col) use ($def, $mode, $tables, $tableScope) {
            if ($mode === 'join') {
                return $this->assertQualifiedColumn($col, $tables, $tableScope);
            }

            if (! in_array($col, $def['columns'], true)) {
                throw new QueryException("Column '{$col}' is not selectable on '{$def['table']}'.");
            }

            return $col;
        }, array_values($select));
    }

    /**
     * @param  array<int, string>  $with
     * @param  array<string, mixed>  $def
     * @return array<string, string>
     */
    protected function normalizeWith(array $with, array $def): array
    {
        $targets = [];

        foreach ($with as $relation) {
            if (! array_key_exists($relation, $def['relations'])) {
                throw new QueryException("Relation '{$relation}' is not loadable on '{$def['table']}'. Allowed: ".implode(', ', array_keys($def['relations'])).'.');
            }

            $targets[$relation] = $def['relations'][$relation];
        }

        return $targets;
    }

    /**
     * @param  array<int, mixed>  $filters
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @param  array<string, mixed>  $defaults
     * @return list<array<string, mixed>>
     */
    protected function normalizeFilters(array $filters, array $def, string $mode, array $tables, array $tableScope, array $defaults): array
    {
        $normalized = [];

        foreach ($filters as $filter) {
            $op = strtolower((string) ($filter['operator'] ?? ''));

            if (! in_array($op, $defaults['allowed_operators'], true)) {
                throw new QueryException("Invalid filter operator '{$op}'. Allowed: ".implode(', ', $defaults['allowed_operators']).'.');
            }

            $column = (string) ($filter['column'] ?? '');
            $column = $mode === 'join'
                ? $this->assertQualifiedColumn($column, $tables, $tableScope)
                : $this->assertBaseColumn($column, $def);

            $isId = $this->isIdColumn($column);

            $entry = [
                'boolean' => ($filter['boolean'] ?? 'and') === 'or' ? 'or' : 'and',
                'column' => $column,
                'operator' => $op,
                'value' => null,
                'values' => null,
                'is_id' => $isId,
            ];

            if (in_array($op, ['in', 'not in'], true)) {
                $values = $filter['values'] ?? [];

                if (! is_array($values) || empty($values)) {
                    throw new QueryException("Operator '{$op}' requires a non-empty `values` array.");
                }

                $entry['values'] = $isId
                    ? array_map(fn ($v) => $this->decodeId($v), $values)
                    : array_values($values);
            } elseif (! in_array($op, ['is null', 'is not null'], true)) {
                $value = $filter['value'] ?? null;
                $entry['value'] = $isId ? $this->decodeId($value) : $value;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $orderBy
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @param  list<array{function:string, column:string, alias:string}>  $aggregates
     * @return list<array{column:string, direction:'asc'|'desc'}>
     */
    protected function normalizeOrderBy(array $orderBy, array $def, string $mode, array $tables, array $tableScope, array $aggregates): array
    {
        $aggregateAliases = array_map(fn ($a) => $a['alias'], $aggregates);
        $normalized = [];

        foreach ($orderBy as $order) {
            $column = (string) ($order['column'] ?? '');
            $direction = ($order['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if (in_array($column, $aggregateAliases, true)) {
                $normalized[] = ['column' => $column, 'direction' => $direction];

                continue;
            }

            $column = $mode === 'join'
                ? $this->assertQualifiedColumn($column, $tables, $tableScope)
                : $this->assertBaseColumn($column, $def);

            $normalized[] = ['column' => $column, 'direction' => $direction];
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $def
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope
     * @return list<string>
     */
    protected function normalizeColumnList(array $columns, array $def, string $mode, array $tables, array $tableScope, string $context): array
    {
        return array_map(function (string $col) use ($def, $mode, $tables, $tableScope) {
            return $mode === 'join'
                ? $this->assertQualifiedColumn($col, $tables, $tableScope)
                : $this->assertBaseColumn($col, $def);
        }, array_values($columns));
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function assertBaseColumn(string $column, array $def): string
    {
        if (! in_array($column, $def['columns'], true)) {
            throw new QueryException("Column '{$column}' is not available on '{$def['table']}'.");
        }

        return $column;
    }

    /**
     * Validate a "table.column" reference against the set of tables present in the query.
     *
     * @param  array<int, string>  $tables
     * @param  array<string, string>  $tableScope  table => alias
     */
    protected function assertQualifiedColumn(string $column, array $tables, ?array $tableScope = null): string
    {
        if (! str_contains($column, '.')) {
            throw new QueryException("Column '{$column}' must be qualified as `table.column` in join mode.");
        }

        [$table, $col] = explode('.', $column, 2);

        if (! in_array($table, $tables, true)) {
            throw new QueryException("Table '{$table}' is not part of this query. Present tables: ".implode(', ', $tables).'.');
        }

        // Resolve the owning model definition to check the column allowlist.
        $alias = $tableScope[$table] ?? null;

        if ($alias !== null) {
            $def = $this->registry->get($alias);

            if (! in_array($col, $def['columns'], true)) {
                throw new QueryException("Column '{$col}' is not available on '{$table}'.");
            }
        }

        return "{$table}.{$col}";
    }

    protected function isIdColumn(string $column): bool
    {
        $name = str_contains($column, '.') ? explode('.', $column, 2)[1] : $column;

        return $name === 'id' || str_ends_with($name, '_id');
    }

    /**
     * Decode a sqid id value to an integer. Accepts an already-integer value.
     */
    protected function decodeId(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        try {
            return Sqids::decode((string) $value);
        } catch (\Throwable $e) {
            throw new QueryException("Invalid encoded id '{$value}'. Pass the sqid-encoded id returned by other tools.");
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/DynamicQuery/DynamicQueryValidationTest.php`
Expected: PASS (7 passed).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/DynamicQuery/DynamicQueryService.php tests/Unit/DynamicQuery/DynamicQueryValidationTest.php
git commit -m "feat(mcp): add dynamic query validation and normalization"
```

---

## Task 3: Relation-mode execution + visibility scoping

**Files:**
- Modify: `app/Services/DynamicQuery/DynamicQueryService.php` (add `run`, `visibilityContext`, relation builder, scope + soft-delete helpers, encode)
- Test: `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`

**Interfaces:**
- Consumes: `validate()` (Task 2).
- Produces:
  - `run(array $query, \App\Models\User $user): array` — validated + executed; returns encoded rows (array of associative arrays).
  - `visibilityContext(\App\Models\User $user): array{seesAll:bool, projectIds:list<int>}`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`:

```php
<?php

use App\Facades\Sqids;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Services\DynamicQuery\DynamicQueryService;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    // Pin user id 1 so the seeders' hardcoded FKs resolve (see TaskProjectToolsTest).
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
    ]);

    $this->status = MsTaskStatus::query()->firstOrFail();
    $this->type = MsTaskType::query()->firstOrFail();
    $this->category = TaskCategory::query()->firstOrFail();

    $this->makeProject = function (User $owner, string $title): Project {
        $project = Project::create([
            'status_id' => MsProjectStatus::query()->firstOrFail()->id,
            'priority_id' => MsProjectPriority::query()->firstOrFail()->id,
            'owner_id' => $owner->id,
            'owned_id' => $owner->id,
            'title' => $title,
            'emoji' => '🚀',
            'progress' => 0,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
            'owned_id' => $owner->id,
            'is_active' => true,
        ]);

        return $project;
    };

    $this->service = app(DynamicQueryService::class);
});

it('returns base rows for a member with encoded ids', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'Alpha',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => $this->status->id,
        'type_id' => $this->type->id,
        'task_category_id' => $this->category->id,
    ]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['id', 'title', 'project_id'],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['title'])->toBe('Alpha')
        ->and($rows[0]['id'])->toBe(Sqids::encode($task->id))          // encoded, not raw int
        ->and($rows[0]['project_id'])->toBe(Sqids::encode($project->id));
});

it('scopes rows to the visible projects of the acting user', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    $theirs = ($this->makeProject)($outsider, 'Theirs');

    Task::create(['project_id' => $mine->id, 'title' => 'Mine task', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $theirs->id, 'title' => 'Their task', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run(['model' => 'task', 'select' => ['title']], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Mine task']);
});

it('filters with a decoded sqid id and honors the limit', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'Keep', 'progress' => 80, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'Drop', 'progress' => 10, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['title', 'progress'],
        'filters' => [
            ['column' => 'project_id', 'operator' => '=', 'value' => Sqids::encode($project->id)],
            ['column' => 'progress', 'operator' => '>=', 'value' => 50],
        ],
        'order_by' => [['column' => 'progress', 'direction' => 'desc']],
    ], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Keep']);
});
```

- [ ] **Step 2: Run test to verify it fails**

⚠️ First confirm the testing DB target (see the safety box at the top). Then:

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: FAIL — `Call to undefined method ...::run()`.

- [ ] **Step 3: Add run(), visibility, relation builder, scope/soft-delete, encode**

Add these methods to `app/Services/DynamicQuery/DynamicQueryService.php` (add `use` imports at the top of the file: `use App\Models\User;`, `use Illuminate\Database\Eloquent\SoftDeletes;`, `use Illuminate\Support\Facades\DB;`):

```php
    /**
     * Validate and execute a dynamic query for the given user.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function run(array $query, User $user): array
    {
        $normalized = $this->validate($query);
        $ctx = $this->visibilityContext($user);

        $rows = $this->buildRelationQuery($normalized, $ctx);

        return $this->encode($rows);
    }

    /**
     * @return array{seesAll: bool, projectIds: list<int>}
     */
    public function visibilityContext(User $user): array
    {
        $superRoles = $this->registry->defaults()['super_admin_roles'];
        $seesAll = $user->getRoleNames()->intersect($superRoles)->isNotEmpty();

        return [
            'seesAll' => $seesAll,
            'projectIds' => $seesAll ? [] : $user->projects()->pluck('projects.id')->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $q
     * @param  array{seesAll: bool, projectIds: list<int>}  $ctx
     * @return array<int, mixed>
     */
    protected function buildRelationQuery(array $q, array $ctx): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $q['model']::query();

        $query->select(array_map(fn ($c) => "{$q['table']}.{$c}", $q['select']));

        if ($q['distinct']) {
            $query->distinct();
        }

        if ($q['with_trashed'] && $this->usesSoftDeletes($q['model'])) {
            $query->withTrashed();
        }

        $this->applyProjectScope($query, $q['alias'], $ctx);
        $this->applyFilters($query, $q['filters']);
        $this->applyRelations($query, $q, $ctx);

        foreach ($q['order_by'] as $order) {
            $query->orderBy($order['column'], $order['direction']);
        }

        $query->limit($q['limit'])->offset($q['offset']);

        return $query->get()->toArray();
    }

    /**
     * Apply `with` relations, scoping any relation whose target is project-scoped.
     *
     * @param  array<string, mixed>  $q
     * @param  array{seesAll: bool, projectIds: list<int>}  $ctx
     */
    protected function applyRelations(\Illuminate\Database\Eloquent\Builder $query, array $q, array $ctx): void
    {
        $eager = [];

        foreach ($q['with_targets'] as $relation => $targetAlias) {
            $targetDef = $this->registry->get($targetAlias);

            if ($targetDef['scope'] === 'project') {
                $eager[$relation] = fn ($related) => $this->applyProjectScope($related->getQuery(), $targetAlias, $ctx);
            } else {
                $eager[] = $relation;
            }
        }

        if (! empty($eager)) {
            $query->with($eager);
        }
    }

    /**
     * Inject the visibility constraint for a project-scoped table. No-op for
     * global tables or when the user sees everything.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array{seesAll: bool, projectIds: list<int>}  $ctx
     */
    protected function applyProjectScope($query, string $alias, array $ctx): void
    {
        if ($ctx['seesAll']) {
            return;
        }

        $def = $this->registry->get($alias);

        if ($def['scope'] !== 'project') {
            return;
        }

        $key = $def['project_key'];
        $ids = $ctx['projectIds'];

        if ($key['type'] === 'column') {
            $query->whereIn("{$def['table']}.{$key['column']}", $ids);

            return;
        }

        // subquery: column IN (SELECT via_select FROM via_table WHERE via_where IN ids [AND deleted_at IS NULL])
        $query->whereIn("{$def['table']}.{$key['column']}", function ($sub) use ($key, $ids) {
            $sub->select($key['via_select'])
                ->from($key['via_table'])
                ->whereIn("{$key['via_table']}.{$key['via_where']}", $ids);

            if (! empty($key['via_soft_delete'])) {
                $sub->whereNull("{$key['via_table']}.deleted_at");
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  list<array<string, mixed>>  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        foreach ($filters as $filter) {
            $boolean = $filter['boolean'];

            match ($filter['operator']) {
                'is null' => $query->whereNull($filter['column'], $boolean),
                'is not null' => $query->whereNotNull($filter['column'], $boolean),
                'in' => $query->whereIn($filter['column'], $filter['values'], $boolean),
                'not in' => $query->whereNotIn($filter['column'], $filter['values'], $boolean, true),
                default => $query->where($filter['column'], $filter['operator'], $filter['value'], $boolean),
            };
        }
    }

    /**
     * @param  class-string  $modelClass
     */
    protected function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }

    /**
     * Encode all id / *_id columns in the result set (nested or flat).
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function encode(array $rows): array
    {
        return Sqids::rec_encode_ids_in_list($rows);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: PASS (3 passed).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/DynamicQuery/DynamicQueryService.php tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php
git commit -m "feat(mcp): execute relation-mode dynamic queries with visibility scoping"
```

---

## Task 4: Relation loading (`with`) with scoped closures

**Files:**
- Modify: (none — `applyRelations` was added in Task 3)
- Test: `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php` (append)

This task verifies the `with` path added in Task 3 produces nested output and that a project-scoped relation loaded from a global base (e.g. `user` → `projects`) is still scoped.

- [ ] **Step 1: Write the failing test (append to the file)**

Append to `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`:

```php
it('loads whitelisted relations as nested data', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'With status', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['id', 'title', 'status_id'],
        'with' => ['status'],
    ], $this->user);

    expect($rows[0]['status'])->toBeArray()
        ->and($rows[0]['status']['name'])->toBe($this->status->name)
        ->and($rows[0]['status']['id'])->toBe(Sqids::encode($this->status->id));
});

it('scopes a project-scoped relation loaded from a global base model', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    ($this->makeProject)($outsider, 'Theirs');

    $rows = $this->service->run([
        'model' => 'user',
        'select' => ['id', 'name'],
        'with' => ['projects'],
        'filters' => [['column' => 'id', 'operator' => '=', 'value' => Sqids::encode($this->user->id)]],
    ], $this->user);

    // The acting user only sees their own project through the relation.
    expect(collect($rows[0]['projects'])->pluck('title')->all())->toBe(['Mine']);
});
```

- [ ] **Step 2: Run test to verify it fails or passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: The first new test PASSES (relation loading already implemented). The second (`scopes a project-scoped relation...`) must PASS — if it FAILS, the scoped closure in `applyRelations` is not being applied; fix `applyRelations` so project-scoped relation targets get `applyProjectScope` on `$related->getQuery()`.

- [ ] **Step 3: (If needed) fix applyRelations**

If the second test failed, ensure `applyRelations` matches the Task 3 implementation exactly (scoped closure for `scope === 'project'`). No new code beyond Task 3 should be required.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: PASS (5 passed).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php app/Services/DynamicQuery/DynamicQueryService.php
git commit -m "test(mcp): verify scoped relation loading in dynamic queries"
```

---

## Task 5: Join-mode execution (flat rows via query builder)

**Files:**
- Modify: `app/Services/DynamicQuery/DynamicQueryService.php` (add join dispatch in `run`, add `buildJoinQuery`, `applySoftDeleteFilter`)
- Test: `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php` (append)

**Interfaces:**
- Consumes: `validate()`, `applyProjectScope()`, `applyFilters()`.
- Produces: `buildJoinQuery(array $q, array $ctx): array<int, mixed>` — flat rows.

- [ ] **Step 1: Write the failing test (append)**

Append to `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`:

```php
it('runs an explicit join and returns flat rows', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    Task::create(['project_id' => $project->id, 'title' => 'Joined', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['tasks.title', 'ms_task_statuses.name'],
        'joins' => [[
            'type' => 'left',
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
    ], $this->user);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['title'])->toBe('Joined')
        ->and($rows[0]['name'])->toBe($this->status->name);
});

it('scopes join-mode queries to visible projects', function () {
    $mine = ($this->makeProject)($this->user, 'Mine');
    $outsider = User::factory()->create(['id' => 2]);
    $theirs = ($this->makeProject)($outsider, 'Theirs');

    Task::create(['project_id' => $mine->id, 'title' => 'Mine', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $theirs->id, 'title' => 'Theirs', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['tasks.title'],
        'joins' => [[
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
    ], $this->user);

    expect(collect($rows)->pluck('title')->all())->toBe(['Mine']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: FAIL — join-mode rows come back wrong/empty because `run()` still always uses the relation builder.

- [ ] **Step 3: Add the join dispatch and builder**

In `run()`, replace the single `buildRelationQuery` call so the mode is honored:

```php
        $rows = $normalized['mode'] === 'join'
            ? $this->buildJoinQuery($normalized, $ctx)
            : $this->buildRelationQuery($normalized, $ctx);
```

Then add these methods to `app/Services/DynamicQuery/DynamicQueryService.php`:

```php
    /**
     * Join/aggregate mode: build on the query builder for clean flat rows,
     * bypassing Eloquent's default eager-loads and appended accessors.
     *
     * @param  array<string, mixed>  $q
     * @param  array{seesAll: bool, projectIds: list<int>}  $ctx
     * @return array<int, mixed>
     */
    protected function buildJoinQuery(array $q, array $ctx): array
    {
        $query = DB::table($q['table']);

        foreach ($q['joins'] as $join) {
            $method = $join['type'] === 'left' ? 'leftJoin' : 'join';
            $query->{$method}($join['table'], function ($j) use ($join) {
                foreach ($join['on'] as $on) {
                    $j->on($on['left'], $on['operator'], $on['right']);
                }
            });
        }

        // Soft-delete + visibility scope for every table present in the query.
        foreach ($q['tables'] as $table) {
            $alias = $q['table_scope'][$table];
            $this->applySoftDeleteFilter($query, $alias, $q['with_trashed']);
            $this->applyProjectScope($query, $alias, $ctx);
        }

        // Nest user filters in a group so a caller-supplied `or` boolean cannot
        // break out of the AND-ed visibility scope (see Task 3 security fix).
        if (! empty($q['filters'])) {
            $query->where(fn ($nested) => $this->applyFilters($nested, $q['filters']));
        }

        if (empty($q['aggregates'])) {
            $query->select($q['select']);

            if ($q['distinct']) {
                $query->distinct();
            }
        } else {
            $query->select($q['select']);

            foreach ($q['aggregates'] as $agg) {
                $query->selectRaw("{$agg['function']}({$agg['column']}) as {$agg['alias']}");
            }

            $query->groupBy($q['group_by']);
        }

        foreach ($q['order_by'] as $order) {
            $query->orderBy($order['column'], $order['direction']);
        }

        $query->limit($q['limit'])->offset($q['offset']);

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    protected function applySoftDeleteFilter($query, string $alias, bool $withTrashed): void
    {
        if ($withTrashed) {
            return;
        }

        $def = $this->registry->get($alias);

        if ($def['soft_delete']) {
            $query->whereNull("{$def['table']}.deleted_at");
        }
    }
```

> Note: the aggregate `selectRaw` uses only registry-validated identifiers (`function`, `column`, `alias` were checked in `normalizeAggregates`), so no user-controlled string reaches raw SQL.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: PASS (7 passed).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/DynamicQuery/DynamicQueryService.php tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php
git commit -m "feat(mcp): execute join-mode dynamic queries with per-table scoping"
```

---

## Task 6: Aggregates + group_by

**Files:**
- Modify: (none — aggregate handling was added in Task 5's `buildJoinQuery`)
- Test: `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php` (append)

- [ ] **Step 1: Write the failing/passing test (append)**

Append to `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`:

```php
it('aggregates task counts grouped by a joined column', function () {
    $project = ($this->makeProject)($this->user, 'Mine');
    $other = MsTaskStatus::query()->where('id', '!=', $this->status->id)->firstOrFail();

    Task::create(['project_id' => $project->id, 'title' => 'A', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'B', 'progress' => 0, 'sequence_number' => 2, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    Task::create(['project_id' => $project->id, 'title' => 'C', 'progress' => 0, 'sequence_number' => 3, 'status_id' => $other->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);

    $rows = $this->service->run([
        'model' => 'task',
        'select' => ['ms_task_statuses.name'],
        'joins' => [[
            'model' => 'ms_task_status',
            'on' => [['left' => 'tasks.status_id', 'operator' => '=', 'right' => 'ms_task_statuses.id']],
        ]],
        'group_by' => ['ms_task_statuses.name'],
        'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
        'order_by' => [['column' => 'total', 'direction' => 'desc']],
    ], $this->user);

    expect($rows[0]['name'])->toBe($this->status->name)
        ->and((int) $rows[0]['total'])->toBe(2);
});
```

- [ ] **Step 2: Run test**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`
Expected: PASS (8 passed). If the aggregate row count is wrong, verify `normalizeOrderBy` allows ordering by the aggregate alias `total` (it should, per Task 2).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php
git commit -m "test(mcp): verify aggregate + group_by dynamic queries"
```

---

## Task 7: `list-queryable-models` introspection tool

**Files:**
- Create: `app/Mcp/Tools/QueryableSchemaTool.php`
- Modify: `app/Mcp/Servers/ProjectManagementServer.php`
- Test: `tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php`

**Interfaces:**
- Consumes: `QueryRegistry::models()`, `QueryRegistry::defaults()`.
- Produces: MCP tool named `list-queryable-models` returning `{ data: { models: [...], operators: [...], aggregates: [...], max_limit: int } }`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php`:

```php
<?php

use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\QueryableSchemaTool;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);
});

it('lists queryable models with their columns and relations', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryableSchemaTool::class);

    $response->assertOk();
    $response->assertSee('task');
    $response->assertSee('ms_task_statuses');
    $response->assertSee('like');   // allowed operator surfaced

    // Sensitive columns must never be advertised.
    expect($response->content ?? '')->not->toContain('password');
});
```

> If `$response->content` is not the correct accessor for raw text in this laravel/mcp version, replace the final `expect(...)` with `$response->assertSee('email')` and add a negative check by dumping via `$response->dump()` during development. Keep at least the positive `assertSee` assertions.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php`
Expected: FAIL — `Class "App\Mcp\Tools\QueryableSchemaTool" not found`.

- [ ] **Step 3: Create the tool**

Create `app/Mcp/Tools/QueryableSchemaTool.php`:

```php
<?php

namespace App\Mcp\Tools;

use App\Services\DynamicQuery\QueryRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-queryable-models')]
#[Title('List Queryable Models')]
#[Description('Lists every model the query-data tool can read: model alias, table, selectable columns, loadable relations (with their target model), joinable models, scope type, and the allowed filter operators and aggregate functions. Call this before building a query-data request.')]
#[IsReadOnly]
#[IsIdempotent]
class QueryableSchemaTool extends Tool
{
    public function __construct(protected QueryRegistry $registry) {}

    public function handle(Request $request): Response
    {
        $models = [];

        foreach ($this->registry->models() as $alias => $def) {
            $models[] = [
                'model' => $alias,
                'table' => $def['table'],
                'scope' => $def['scope'],
                'columns' => $def['columns'],
                'relations' => $def['relations'],
                'joinable' => $def['joinable'],
            ];
        }

        $defaults = $this->registry->defaults();

        return Response::json([
            'data' => [
                'models' => $models,
                'operators' => $defaults['allowed_operators'],
                'aggregates' => $defaults['allowed_aggregates'],
                'default_limit' => $defaults['limit'],
                'max_limit' => $defaults['max_limit'],
            ],
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->object([
                'models' => $schema->array()->items($schema->object([
                    'model' => $schema->string(),
                    'table' => $schema->string(),
                    'scope' => $schema->string(),
                    'columns' => $schema->array()->items($schema->string()),
                    'joinable' => $schema->array()->items($schema->string()),
                ]))->description('Queryable models and their metadata.'),
                'operators' => $schema->array()->items($schema->string()),
                'aggregates' => $schema->array()->items($schema->string()),
                'default_limit' => $schema->integer(),
                'max_limit' => $schema->integer(),
            ])->description('Catalog of everything the query-data tool can read.')->required(),
        ];
    }
}
```

- [ ] **Step 4: Register the tool**

In `app/Mcp/Servers/ProjectManagementServer.php`, add the import and append to `$tools`:

```php
use App\Mcp\Tools\QueryableSchemaTool;
```

```php
    protected array $tools = [
        MyProjectTool::class,
        TaskProjectTools::class,
        TaskOptionsTool::class,
        ProjectDetailTool::class,
        ProjectOptionsTool::class,
        CreateProjectTool::class,
        UpdateProjectTool::class,
        UpdateTaskStatusTool::class,
        QueryableSchemaTool::class,
    ];
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php`
Expected: PASS.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Mcp/Tools/QueryableSchemaTool.php app/Mcp/Servers/ProjectManagementServer.php tests/Feature/Mcp/DynamicQuery/QueryableSchemaToolTest.php
git commit -m "feat(mcp): add list-queryable-models introspection tool"
```

---

## Task 8: `query-data` tool (end-to-end)

**Files:**
- Create: `app/Mcp/Tools/QueryDataTool.php`
- Modify: `app/Mcp/Servers/ProjectManagementServer.php`
- Test: `tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php`

**Interfaces:**
- Consumes: `DynamicQueryService::run()`, `QueryException`.
- Produces: MCP tool `query-data` returning `{ data: [...] }` or an error via `Response::error`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php`:

```php
<?php

use App\Facades\Sqids;
use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\QueryDataTool;
use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Database\Seeders\MsProjectPrioritySeeder;
use Database\Seeders\MsProjectRoleSeeder;
use Database\Seeders\MsProjectStatusSeeder;
use Database\Seeders\MsTaskPrioritySeeder;
use Database\Seeders\MsTaskStatusSeeder;
use Database\Seeders\MsTaskTypeSeeder;
use Database\Seeders\TaskCategorySeeder;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);

    $this->seed([
        MsProjectStatusSeeder::class,
        MsProjectPrioritySeeder::class,
        MsProjectRoleSeeder::class,
        MsTaskStatusSeeder::class,
        MsTaskPrioritySeeder::class,
        MsTaskTypeSeeder::class,
        TaskCategorySeeder::class,
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

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'project_role_id' => MsProjectRole::query()->firstOrFail()->id,
        'owned_id' => $this->user->id,
        'is_active' => true,
    ]);

    Task::create([
        'project_id' => $this->project->id,
        'title' => 'Findable Task',
        'progress' => 0,
        'sequence_number' => 1,
        'status_id' => MsTaskStatus::query()->firstOrFail()->id,
        'type_id' => MsTaskType::query()->firstOrFail()->id,
        'task_category_id' => TaskCategory::query()->firstOrFail()->id,
    ]);
});

it('returns query results for a member', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, [
            'model' => 'task',
            'select' => ['id', 'title'],
            'filters' => [
                ['column' => 'project_id', 'operator' => '=', 'value' => Sqids::encode($this->project->id)],
            ],
        ]);

    $response->assertOk();
    $response->assertSee('Findable Task');
});

it('returns an actionable error for an unknown model', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, ['model' => 'secrets']);

    $response->assertHasErrors();
});

it('returns an actionable error when combining with and aggregates', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryDataTool::class, [
            'model' => 'task',
            'with' => ['status'],
            'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
        ]);

    $response->assertHasErrors();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php`
Expected: FAIL — `Class "App\Mcp\Tools\QueryDataTool" not found`.

- [ ] **Step 3: Create the tool**

Create `app/Mcp/Tools/QueryDataTool.php`:

```php
<?php

namespace App\Mcp\Tools;

use App\Services\DynamicQuery\DynamicQueryService;
use App\Services\DynamicQuery\QueryException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('query-data')]
#[Title('Query Data')]
#[Description('Run a read-only (SELECT) dynamic query against project/task models. Supports column selection, explicit joins (flat results) OR relation loading via `with` (nested results), filters, group_by + aggregates, ordering, and pagination. Results are automatically scoped to the projects you can see, and all ids are sqid-encoded. Call list-queryable-models first to discover valid models, columns, relations, and operators.')]
#[IsReadOnly]
#[IsIdempotent]
class QueryDataTool extends Tool
{
    public function __construct(protected DynamicQueryService $service) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'model' => ['required', 'string'],
            'select' => ['sometimes', 'array'],
            'select.*' => ['string'],
            'distinct' => ['sometimes', 'boolean'],
            'joins' => ['sometimes', 'array'],
            'joins.*.type' => ['sometimes', 'in:inner,left'],
            'joins.*.model' => ['required_with:joins', 'string'],
            'joins.*.on' => ['required_with:joins', 'array', 'min:1'],
            'joins.*.on.*.left' => ['required', 'string'],
            'joins.*.on.*.operator' => ['sometimes', 'string'],
            'joins.*.on.*.right' => ['required', 'string'],
            'with' => ['sometimes', 'array'],
            'with.*' => ['string'],
            'filters' => ['sometimes', 'array'],
            'filters.*.boolean' => ['sometimes', 'in:and,or'],
            'filters.*.column' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string'],
            'filters.*.values' => ['sometimes', 'array'],
            'group_by' => ['sometimes', 'array'],
            'group_by.*' => ['string'],
            'aggregates' => ['sometimes', 'array'],
            'aggregates.*.function' => ['required_with:aggregates', 'string'],
            'aggregates.*.column' => ['required_with:aggregates', 'string'],
            'aggregates.*.alias' => ['required_with:aggregates', 'string'],
            'order_by' => ['sometimes', 'array'],
            'order_by.*.column' => ['required_with:order_by', 'string'],
            'order_by.*.direction' => ['sometimes', 'in:asc,desc'],
            'limit' => ['sometimes', 'integer', 'min:1'],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'with_trashed' => ['sometimes', 'boolean'],
        ], [
            'model.required' => 'You must provide a `model`. Call the list-queryable-models tool to see available models.',
        ]);

        // `value` is intentionally unconstrained (string/number/bool), so it is read from the raw request.
        $raw = $request->all();
        foreach (($raw['filters'] ?? []) as $index => $filter) {
            if (array_key_exists('value', $filter)) {
                $validated['filters'][$index]['value'] = $filter['value'];
            }
        }

        try {
            $data = $this->service->run($validated, $request->user());
        } catch (QueryException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(['data' => $data]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'model' => $schema->string()
                ->description('Model alias to query (see list-queryable-models). Example: "task".')
                ->required(),
            'select' => $schema->array()->items($schema->string())
                ->description('Columns to return. Relation mode: plain column names. Join mode: qualified "table.column". Defaults to the safe column list.'),
            'distinct' => $schema->boolean()->description('Return distinct rows.'),
            'joins' => $schema->array()->items($schema->object([
                'type' => $schema->string()->enum(['inner', 'left'])->description('Join type (default inner).'),
                'model' => $schema->string()->description('Model alias to join (must be joinable from the base model).'),
                'on' => $schema->array()->items($schema->object([
                    'left' => $schema->string()->description('Qualified column "table.column".'),
                    'operator' => $schema->string()->description('Comparison operator, default "=".'),
                    'right' => $schema->string()->description('Qualified column "table.column".'),
                ])),
            ]))->description('Explicit table joins (flat results). Do not combine with `with`.'),
            'with' => $schema->array()->items($schema->string())
                ->description('Relations to eager-load (nested results). Do not combine with joins/aggregates/group_by.'),
            'filters' => $schema->array()->items($schema->object([
                'boolean' => $schema->string()->enum(['and', 'or'])->description('How this filter combines (default and).'),
                'column' => $schema->string()->description('Column to filter. Join mode: "table.column".'),
                'operator' => $schema->string()->description('One of =, !=, >, >=, <, <=, like, in, not in, is null, is not null.'),
                'value' => $schema->string()->description('Value for scalar operators. For id/_id columns pass the sqid-encoded id.'),
                'values' => $schema->array()->items($schema->string())->description('Values for the in / not in operators.'),
            ]))->description('Filter conditions (parametrized).'),
            'group_by' => $schema->array()->items($schema->string())
                ->description('Group-by columns (qualified in join mode). Required alongside aggregates.'),
            'aggregates' => $schema->array()->items($schema->object([
                'function' => $schema->string()->enum(['count', 'sum', 'avg', 'min', 'max']),
                'column' => $schema->string()->description('Qualified "table.column" or "*".'),
                'alias' => $schema->string()->description('Result key for this aggregate.'),
            ]))->description('Aggregate expressions. Do not combine with `with`.'),
            'order_by' => $schema->array()->items($schema->object([
                'column' => $schema->string(),
                'direction' => $schema->string()->enum(['asc', 'desc']),
            ]))->description('Ordering.'),
            'limit' => $schema->integer()->description('Max rows (default 50, hard max 200).'),
            'offset' => $schema->integer()->description('Rows to skip.'),
            'with_trashed' => $schema->boolean()->description('Include soft-deleted rows.'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()
                ->description('Result rows. Nested objects for relation mode, flat rows for join/aggregate mode. All ids are sqid-encoded.')
                ->required(),
        ];
    }
}
```

> **Verification note (nested input schema):** laravel/mcp passes the JSON Schema to the client as advisory guidance; the authoritative check is `$request->validate()`. If, when running the test, nested arguments do not arrive intact (e.g. `joins`/`filters` come through empty), fall back to a single `query` string argument: `'query' => $schema->string()->required()` carrying JSON, then `$validated = json_decode($request->get('query'), true)` and validate the decoded array with the same rules via `Validator::make`. Keep the `run()`/service contract unchanged. Confirm which path works by running Step 5 before committing.

- [ ] **Step 4: Register the tool**

In `app/Mcp/Servers/ProjectManagementServer.php`, add the import and append `QueryDataTool::class` to `$tools`:

```php
use App\Mcp\Tools\QueryDataTool;
```

```php
        QueryableSchemaTool::class,
        QueryDataTool::class,
    ];
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php`
Expected: PASS (3 passed). If the first test fails because `filters` did not reach the service, apply the fallback in the verification note above and re-run.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Mcp/Tools/QueryDataTool.php app/Mcp/Servers/ProjectManagementServer.php tests/Feature/Mcp/DynamicQuery/QueryDataToolTest.php
git commit -m "feat(mcp): add query-data dynamic query tool"
```

---

## Task 9: Security hardening tests

**Files:**
- Test: `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php` (append)

Explicit tests locking down the security guarantees so a future refactor can't silently break them.

- [ ] **Step 1: Write the tests (append)**

Append to `tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php`:

```php
it('never selects sensitive user columns even with default select', function () {
    ($this->makeProject)($this->user, 'Mine');

    $rows = $this->service->run(['model' => 'user'], $this->user);

    expect($rows[0])->not->toHaveKey('password')
        ->and($rows[0])->not->toHaveKey('remember_token')
        ->and($rows[0])->toHaveKey('email');
});

it('excludes soft-deleted rows by default and includes them with with_trashed', function () {
    $project = ($this->makeProject)($this->user, 'Mine');

    $task = Task::create(['project_id' => $project->id, 'title' => 'Gone', 'progress' => 0, 'sequence_number' => 1, 'status_id' => $this->status->id, 'type_id' => $this->type->id, 'task_category_id' => $this->category->id]);
    $task->delete();

    $without = $this->service->run(['model' => 'task', 'select' => ['title']], $this->user);
    expect($without)->toHaveCount(0);

    $with = $this->service->run(['model' => 'task', 'select' => ['title'], 'with_trashed' => true], $this->user);
    expect(collect($with)->pluck('title')->all())->toBe(['Gone']);
});

it('returns nothing for a user who is a member of no projects', function () {
    ($this->makeProject)($this->user, 'Mine');
    $stranger = User::factory()->create(['id' => 99]);

    $rows = $this->service->run(['model' => 'task', 'select' => ['title']], $stranger);

    expect($rows)->toHaveCount(0);
});
```

- [ ] **Step 2: Run the full dynamic-query suite**

⚠️ Confirm testing DB target first. Then:

Run: `php artisan test --compact tests/Feature/Mcp/DynamicQuery tests/Unit/DynamicQuery`
Expected: PASS (all dynamic-query tests green).

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Mcp/DynamicQuery/DynamicQueryServiceTest.php
git commit -m "test(mcp): lock down dynamic query security guarantees"
```

---

## Task 10: Full suite + lint gate

**Files:** (none — verification only)

- [ ] **Step 1: Run Pint across the whole change**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No style violations remain.

- [ ] **Step 2: Run the MCP feature suite to confirm no regressions**

⚠️ Confirm testing DB target first.

Run: `php artisan test --compact tests/Feature/Mcp tests/Unit/DynamicQuery`
Expected: PASS — new dynamic-query tests plus the existing MCP tool tests all green.

- [ ] **Step 3: Final commit (if Pint changed anything)**

```bash
git add -A
git commit -m "chore(mcp): format dynamic query implementation"
```

---

## Self-Review Notes (author checklist, already applied)

- **Spec coverage:** scope enforcement (Tasks 3/5/9), structured DSL (Tasks 2/8), joins + relations (Tasks 4/5), Sqids in/out (Tasks 2/3), SELECT-only by construction (service never mutates; Task 8 tool is `IsReadOnly`), soft-delete default (Task 9), limit cap (Task 2), introspection tool (Task 7), YAGNI exclusions honored (registry omits comments/notifications/taggables).
- **Type consistency:** `run(array,User):array`, `validate(array):array` (normalized shape defined once in Task 2 and consumed unchanged in Tasks 3–6), `applyProjectScope($query,string,array)`, `visibilityContext(User):array{seesAll,projectIds}` — names identical across tasks.
- **Open verification points flagged for the implementer:** (a) ~~existence of `MsSprintStatus`/`Tag`/`TaskUser` model classes~~ — **verified present** (tables `ms_sprint_statuses`, `tags`, `task_users`); (b) nested MCP input-schema delivery vs. the JSON-string fallback (Task 8 note); (c) `$response->content` accessor in the introspection test (Task 7 note). (b) and (c) each have a concrete fallback.
