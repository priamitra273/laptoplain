# Local State + Endpoint Baru untuk Create/Update Task (project-lazy)

**Tanggal:** 2026-06-20
**Branch:** refactor/project-detail
**Scope:** Tab List (utama) & Kanban pada `resources/js/pages/project-lazy/`, plus endpoint backend baru.

## Tujuan

Saat ini tab List/Kanban memuat ulang seluruh data (`router.reload({ only: ['tasks'] })`)
setiap kali sebuah task dibuat/diperbarui. Kita ingin:

1. Submit form task mengirim **axios** ke **endpoint baru** yang hanya mengembalikan
   status sukses (+ id untuk create).
2. Frontend memelihara **local state** pohon task: menyisipkan child baru ke tree table,
   dan menghitung ulang progress tiap task.
3. Progress **project** juga dihitung di frontend dan tercermin live di header shell.
4. **Tidak** menyentuh endpoint/flow lama agar `resources/js/pages/project/` (klasik) &
   tab Backlog tetap aman.

## Model Progress (sumber kebenaran tunggal)

Aturan identik di frontend & backend:

- **Leaf task** (tanpa anak): `progress = status.score` (Todo=0, In Progress=50, Completed=100, dst).
- **Parent task** (punya anak): `progress = round(avg(progress semua anak), 2)` — rekursif.
- **Project**: `progress = round(avg(progress task root), 2)`.

Ini sudah sesuai `Task::calculateProgress()` (`app/Models/Task.php:216`) dan
`Project::calculateProgress()` (`app/Models/Project.php:169`). Karena leaf progress murni
turunan `status.score`, frontend dapat menghitung seluruh pohon sendiri (props sudah memuat
`taskStatuses` lengkap dengan `score`).

### Gap backend yang ditambal

`CreateTaskAction` (`app/Actions/Task/CreateTaskAction.php`) **tidak** me-recalc progress
ancestor/project saat membuat task — hanya men-set progress awal dari `status.score`. Karena
payload List/Kanban membaca kolom `progress` **tersimpan** (bukan `calculateProgress()` live),
membuat subtask tanpa recalc akan membuat progress parent/project basi sampai ada update.

Endpoint **create baru** akan menambahkan recalc ancestor+project setelah create, **tanpa**
mengubah `CreateTaskAction` lama (dipakai flow klasik). `UpdateTaskAction` sudah me-recalc
sendiri (`recalculateParentProgress` + `recalculateProjectProgress`), jadi endpoint update baru
cukup memanggilnya.

## Backend

### Routes (routes/web.php, dalam grup `project/{projectEncoded}`, name prefix `project.`)

| Name | Verb + URI | Controller |
|---|---|---|
| `project.tasks.lazy-store` | `POST project/{projectEncoded}/tasks/lazy` | `LazyTaskController@store` |
| `project.tasks.lazy-update` | `PUT project/{projectEncoded}/tasks/{taskEncoded}/lazy` | `LazyTaskController@update` |

Endpoint lama `project.tasks.store` / `project.tasks.update` **tidak diubah**.

### Controller baru — `app/Http/Controllers/LazyTaskController.php`

Reuse Form Request, Action, dan authorization yang sudah ada; yang berbeda **hanya bentuk respons**
(JSON, bukan Inertia redirect).

- `store(TaskStoreRequest $request, string $encoded, CreateTaskAction $create, RecalculateProgressAction $recalc): JsonResponse`
  - Resolve project dari `{encoded}` (sama seperti `TaskController::store`).
  - `$task = $create->execute($project, $request->validated(), Auth::id());`
  - `$recalc->execute($task);` (walk-up ancestor + project).
  - `return response()->json(['success' => true, 'id' => Sqids::encode($task->id)]);`
- `update(TaskUpdateRequest $request, string $encoded, string $taskEncoded, UpdateTaskAction $update): JsonResponse`
  - Resolve task + authorization (mirror logika `TaskController::update`, termasuk Sqids decode `{taskEncoded}`).
  - `$update->execute($task, $request->validated());`
  - `return response()->json(['success' => true]);`
- Gagal validasi: `TaskStoreRequest`/`TaskUpdateRequest` otomatis melempar `422` JSON karena
  axios mengirim `Accept: application/json` (`{ errors: { field: [..] } }`).
- Authorization mengikuti policy/gate yang sudah dipakai `TaskController` (akan dipastikan saat implementasi).

### Action baru — `app/Actions/Task/RecalculateProgressAction.php`

```php
public function execute(Task $task): void
{
    $parent = $task->parent;
    while ($parent) {
        $parent->update(['progress' => $parent->calculateProgress()]);
        $parent = $parent->parent;
    }
    $task->project->update(['progress' => $task->project->calculateProgress()]);
}
```

Hanya dipakai oleh `LazyTaskController@store`. Duplikasi kecil dengan logika privat
`UpdateTaskAction` diterima demi menjaga isolasi flow klasik (refactor `UpdateTaskAction` untuk
memakai action ini bersifat opsional dan di luar scope).

## Frontend

### Composable baru — `resources/js/pages/project-lazy/task/composables/useLocalTaskTree.ts`

Generik untuk node berbentuk `{ id, parent_id, progress, status?, sub_task_recursive }`
(cocok `ListTask` & `KanbanCard`).

- `tasks: Ref<T[]>` — di-seed dari sumber (props.tasks); **re-seed** otomatis saat sumber berubah
  (reload/navigasi/`@statusUpdate` Kanban).
- `recalc()` — hitung ulang progress tiap node bottom-up:
  `computeProgress(node) = node.children.length ? round(avg(children.map(computeProgress)),2) : (node.status?.score ?? 0)`.
  Set `node.progress`, lalu push progress project (`avg root`) ke header via inject (Bagian shell).
- `applySaved(payload, { build, patch })`:
  - **create**: `build(payload)` → node baru; sisipkan ke root (jika `parent_id` null) atau ke
    `sub_task_recursive` parent; lalu `recalc()`.
  - **edit**: cari node by id; `patch(node, payload)`; jika `parent_id` berubah, pindahkan subtree;
    lalu `recalc()`.
- Helper internal: `findNode`, `insertNode`, `removeNode`, `moveNode`.

`build`/`patch` spesifik per-tab (resolve `status_id/type_id/category_id` → objek dari daftar opsi
di props; `users` dari `selectedMembers`). Node create diberi `created_at/updated_at = now` (urutan
`sortByRecency`), `is_overdue`/`completed_at` diturunkan dari due_date & status.

### Form submit pindah ke axios — `…/task/composables/useTaskForm.ts`

`submit()`:
- Bangun payload seperti sekarang (assign/unassign user, tag add/remove, transform tanggal) →
  kirim sebagai **FormData** (dukung lampiran) via **axios**:
  - create: `POST route('project.tasks.lazy-store', { projectEncoded })`.
  - update: `POST route('project.tasks.lazy-update', { projectEncoded, taskEncoded })` dengan
    `_method=PUT` (pola spoofing existing).
- Header `Accept: application/json`.
- 200 → `emit('saved', payload)` di mana `payload = { mode, id, parentId, title, statusId, typeId, categoryId, startDate, dueDate, users, isArchived }`; tutup drawer; reset form.
- 422 → map ke `form.errors`/`validationErrors` (tampil per-field seperti sekarang).
- Error lain → toast.
- Tambah `processing: Ref<boolean>` (axios tidak mengisi `form.processing`); diekspos untuk tombol.

`TaskForm.vue`: tombol pakai `processing` (bukan `form.processing`).
`TaskFormDrawer.vue`: signature emit jadi `(e:'saved', payload: SavedTaskPayload)`, teruskan payload ke parent.

### Shell + halaman

- `resources/js/types/type.ts`: tambah injection key `LiveProjectProgressKey`.
- `layouts/ProjectShellLayout.vue`: `liveProgress = ref(project.progress)`,
  `provide(LiveProjectProgressKey, liveProgress)`; teruskan project dengan `progress = liveProgress`
  ke `ProjectHeader`/`ProjectStats`; watch page-props `project` untuk reset `liveProgress` saat
  navigasi/reload.
- `List.vue`: `useLocalTaskTree` (seed `props.tasks`), kirim `tasks` lokal ke `TaskTable`, tangani
  `@saved` → `applySaved` dengan build/patch `ListTask`. Hapus `router.reload`.
- `Kanban.vue`: pola sama dengan build/patch `KanbanCard`.

### Catatan scoping

- Hanya jalur **create/edit form** yang menjadi local-state untuk List & Kanban.
- Drag-pindah-status Kanban (`@statusUpdate`) **tetap** server + reload→re-seed (tidak mengubah
  board klasik `project/task/partials/TaskKanbanBoard.vue`).
- Delete & drag-reorder task di List **tetap** jalur lama (server) → re-seed lewat watcher.

## Testing

### Frontend (vitest)

- `useLocalTaskTree.test.ts`: avg rekursif (leaf=score, parent=avg, bertingkat); create menyisipkan
  child & recompute ancestor+project; update status leaf mengubah progress & merambat naik; re-seed
  saat source berubah.
- `useTaskForm` submit (mock axios): route & payload benar; emit `saved` dengan payload saat 200;
  map error saat 422.

### Backend (Pest feature)

- `LazyTaskController` store: `200 {success:true,id}`; task tersimpan; progress ancestor+project ter-recalc; `422` saat invalid; authorization ditegakkan.
- update: `200 {success:true}`; perubahan diterapkan + recalc; `422` saat invalid; authorization ditegakkan.

> ⚠️ Tes backend memakai `RefreshDatabase` (`migrate:fresh`). Sesuai aturan proyek: sebelum menjalankan,
> **berhenti & konfirmasi** target DB adalah database test (`.env.testing`), bukan dev/prod.

## Alternatif yang tidak diambil

- **Duplikat form khusus List**: isolasi maksimal tapi duplikasi kode dan dua sumber kebenaran form.
- **Ubah endpoint lama**: paling sedikit kode baru, tapi mengganggu flow klasik & Backlog.

Pendekatan terpilih menambah jalur baru tanpa menyentuh yang lama, dan reuse Action/Request/policy
sehingga validasi & efek samping (notifikasi, event, media) tetap konsisten.
