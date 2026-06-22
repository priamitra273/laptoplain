# Drag-Reorder + Re-parent pada Task TreeTable (project-lazy)

**Tanggal:** 2026-06-23
**Branch:** refactor/project-detail
**Scope:** Tab List `resources/js/pages/project-lazy/task/` (TreeTable) + endpoint backend baru. Tidak menyentuh flow lama (`resources/js/pages/project/`) maupun tab Backlog.

## Masalah

Drag-and-drop pada kolom Title di TreeTable terasa "tidak berpengaruh" dan tidak bisa menyusun ulang urutan:

1. **Pemicu drag rapuh.** `useTaskDragDrop.ts` memakai pola hold-to-drag 900ms (`DRAG_HOLD_MS`) + `@mouseleave="cancelPointerHold"` pada sel judul yang kecil. Gerakan natural (tekan lalu langsung geser) membuat kursor keluar sel sebelum 900ms tercapai → hold dibatalkan → elemen tidak pernah `draggable` → tidak ada request, tidak ada efek. Ada pula dua mekanisme paralel (pointer-based + native HTML5 DnD) yang saling tumpang tindih.
2. **Tidak ada reorder.** Drag yang ada hanya **re-parent** (`PUT tasks/{task}/parent` → set `parent_id`). Tidak ada endpoint untuk mengubah urutan sibling. `sequence_number` (`app/Models/Task.php:37`) nullable dan **tidak pernah di-assign otomatis** di mana pun; ordering jatuh ke `id` (`app/Models/Task.php:210`, `app/Repositories/ProjectRepository.php:214,370`).
3. **PrimeVue TreeTable v4.3.6 tidak punya reorder native** (hanya `resizableColumns`). Reorder harus diimplementasikan manual — beda dengan DataTable yang punya `reorderableRows`/`@row-reorder`.

## Tujuan

- Memperbaiki pemicu drag: **drag native HTML5 dari handle grip** (☰), tanpa timer, tanpa mekanisme pointer ganda.
- Menambah **reorder urutan sibling** sekaligus **re-parent** dalam satu gerakan drag, dibedakan oleh **zona** kursor pada baris tujuan.
- **Optimistic update** lokal (mengikuti pola `onSaved`/`applySaved` di `List.vue`), **tanpa `router.reload()`**. Rollback saat server gagal.

## Model Interaksi

Satu gerakan drag dari grip. Saat `dragover` baris tujuan, zona ditentukan dari `clientY` relatif `getBoundingClientRect()` baris:

| Zona (tinggi baris) | `dropMode` | Aksi | Indikator |
|---|---|---|---|
| atas ~30% | `before` | sisip sebagai sibling sebelum target | garis sisip `border-t-2 border-blue-500` |
| tengah ~40% | `inside` | jadikan anak target (append) | highlight ring/bg biru (seperti sekarang) |
| bawah ~30% | `after` | sisip sebagai sibling sesudah target | garis sisip `border-b-2 border-blue-500` |

Root dropzone (existing, area di luar baris) → pindah ke level akar (append paling akhir).

Guard cycle tetap: `isDescendant(sourceId, targetId)` mencegah memindahkan task ke bawah keturunannya sendiri (toast warn, batal). Drop pada diri sendiri → diabaikan.

## Backend

### Route (routes/web.php, grup `project/{projectEncoded}`, name prefix `project.`)

| Name | Verb + URI | Controller |
|---|---|---|
| `project.tasks.move` | `PUT tasks/{task}/move` | `TaskController@move` |

Mengikuti pola `tasks/{task}/priority` (`routes/web.php:105`) dan `tasks/{task}/parent` (`routes/web.php:106`). Endpoint lama `tasks.parent.update` **tidak diubah** karena masih dipakai Backlog (`useBacklogBoard.ts:222`, `project/task/Backlog.vue:293`, `project/task/Table.vue:347`).

### Request: `App\Http\Requests\Task\TaskMoveRequest`

```php
public function rules(): array
{
    return [
        'parent_id' => ['nullable', /* reuse cek decode + cycle/descendant dari TaskUpdateParentRequest */],
        'position'  => ['required', 'integer', 'min:0'],
    ];
}
```

Validasi `parent_id` menyalin logika `TaskUpdateParentRequest` (`app/Http/Requests/Task/TaskUpdateParentRequest.php`): decode Sqids, pastikan parent ada & satu project, bukan diri sendiri, dan bukan keturunan task (anti-cycle).

### Controller: `TaskController@move`

Mirror `updatePriority`/`updateParent` (`app/Http/Controllers/TaskController.php:220`):

```php
public function move(TaskMoveRequest $request, string $projectEncoded, Task $task)
{
    $this->authorize('update', $projectEncoded, $task);
    $parentId = $request->parent_id ? Sqids::decode($request->parent_id) : null;
    $this->service->move($task, $parentId, (int) $request->integer('position'));
    return response()->json(['success' => true, 'message' => 'Task moved successfully.']);
}
```

### Service: `TaskService::move(Task $task, ?int $parentId, int $position): void`

Dalam transaksi (`DB::transaction`):

1. Catat `$oldParent` untuk recalc progress (pola sama dgn `updateParent`, `app/Services/TaskService.php:277`).
2. `$task->update(['parent_id' => $parentId])`.
3. Ambil sibling group baru: semua task project dengan `parent_id = $parentId`, urut `COALESCE(sequence_number, <big>), id`. Keluarkan `$task` dari koleksi, sisipkan kembali di indeks `position` (clamp `0..count`).
4. **Tulis ulang `sequence_number = 0..n`** untuk seluruh anggota group → normalisasi (hanya group ini yang tersentuh).
5. `calculateParentProgress` untuk rantai parent baru & parent lama (method existing, `app/Services/TaskService.php:299`).

Group lain tak tersentuh (tetap `sequence_number` null → urut `id`). Ordering existing `orderBy('sequence_number')->orderBy('id')` tetap konsisten (di Postgres, dalam satu group tersentuh tidak ada null tersisa).

## Frontend

Kepemilikan state tetap di `List.vue` (`useLocalTaskTree`). TaskTable hanya melapor intent lewat emit `move` — paralel dgn `@saved="onSaved"`. Urutan tampil mengikuti urutan array `sub_task_recursive` apa adanya (`formatTasks` di `useTaskTree.ts:9` tidak menyortir; `sortByRecency` nonaktif).

### `useLocalTaskTree.ts` — method baru `moveNode`

```ts
moveNode(id: string, parentId: string | null, position: number): { parentId: string | null; index: number } | null
```

- Catat lokasi lama node (`{ parentId, index }`) untuk rollback (cari array induk + indeks sebelum hapus).
- `removeFrom(tasks.value, id)` → set `node.parent_id = parentId` → `insertAt(node, parentId, position)`.
- `insertAt`: jika `parentId` null → splice ke `tasks.value` pada `position` (clamp); selain itu `findIn(parentId)` → splice ke `parent.sub_task_recursive` pada `position` (clamp).
- `recalc()` lalu `tasks.value = [...tasks.value]`.
- Return snapshot lama; `null` jika node tidak ditemukan.

### `utils/listTaskNode.ts` (atau util baru) — `computeMoveTarget` (murni, unit-testable)

```ts
computeMoveTarget(
  tree: ListTask[], draggedKey: string, targetKey: string, mode: 'before' | 'inside' | 'after'
): { parentId: string | null; position: number } | null
```

- `inside` → `parentId = targetKey`; `position = jumlah anak target yang BUKAN draggedKey` (append).
- `before`/`after` → `parentId = parent_id target`; ambil array sibling target **tanpa** draggedKey, `idx = indexOf(targetKey)`; `position = mode === 'before' ? idx : idx + 1`.

Indeks ini = indeks insert pasca-penghapusan dragged → identik dipakai `moveNode` lokal & `TaskService::move` server.

### `useTaskDragDrop.ts` — dirombak jadi murni interaksi

- Hapus: `DRAG_HOLD_MS`, hold timer, jalur pointer-based (`onPointerHoldStart`, `onPointerRowEnter`, `onPointerContainerMove`, global `mouseup`, dll). **Native HTML5 DnD saja.**
- State: `dropTargetKey: Ref<string|null>`, `dropMode: Ref<'before'|'inside'|'after'|null>`, plus state root dropzone existing.
- `onRowDragOver(event, targetNode)`: hitung zona dari `clientY` vs `event.currentTarget.getBoundingClientRect()` (atas 30% / tengah 40% / bawah 30%) → set `dropTargetKey` + `dropMode`; `preventDefault` + `dropEffect = 'move'`. Lewati bila target = dragged.
- `onRowDrop`: cek cycle (`isDescendant`), lalu panggil callback `onMove({ taskId, parentId, position })` (payload dihitung via `computeMoveTarget`). **Tidak ada** axios/toast/`router.reload()` di composable.
- Context menerima callback `onMove: (p: TaskMovePayload) => void`.

### `List.vue` — `onMove` (paralel `onSaved`)

```ts
const { tasks, applySaved, moveNode } = useLocalTaskTree<ListTask>(...);

const onMove = async ({ taskId, parentId, position }: TaskMovePayload) => {
    const prev = moveNode(taskId, parentId, position);   // optimistic, instan
    try {
        await axios.put(route('project.tasks.move', { projectEncoded: props.project.id, task: taskId }),
            { parent_id: parentId, position });
    } catch (e) {
        if (prev) moveNode(taskId, prev.parentId, prev.index);  // rollback
        toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memindahkan task.', life: 3000 });
    }
};
```

Sukses senyap (UI sudah pindah, `sequence_number` server authoritative & tidak ditampilkan). Toast hanya saat error. `<TaskTable ... @move="onMove" />`.

### `TaskTable.vue` & tipe

- `LazyTaskTableEmits` (`index.d.ts:274`) + `(e: 'move', payload: TaskMovePayload): void`; tambah tipe `TaskMovePayload { taskId: string; parentId: string | null; position: number }`.
- Sel Title: tambah ikon grip `pi pi-bars` (`cursor-grab`), `:draggable="canMoveTask"`, `@dragstart`/`@dragend`. Klik judul biasa tetap normal (tidak draggable lagi).
- Visual drop: `before` → `border-t-2 border-blue-500`; `after` → `border-b-2 border-blue-500`; `inside` → ring/bg biru existing. Bind dari `dropTargetKey`/`dropMode`.

## Testing

### Feature test — `tests/Feature/.../TaskMoveTest.php` (Pest)

> RefreshDatabase → `migrate:fresh`. **Wajib `.env.testing`**; konfirmasi target DB test (disposable) sebelum jalan, sesuai aturan proyek.

- reorder sibling `before` & `after` (urutan & `sequence_number` ter-normalisasi 0..n).
- `inside`: jadikan anak (append), `parent_id` berubah, progress parent baru terhitung ulang.
- pindah ke akar (`parent_id` null).
- clamp `position` di luar rentang.
- tolak cycle: jadikan anak descendant sendiri → validasi gagal (422).
- otorisasi: user tanpa izin update → forbidden.

### Unit test — `computeMoveTarget` (Vitest, pola `listTaskNode.test.ts`)

- `inside`/`before`/`after` menghasilkan `{ parentId, position }` benar, termasuk saat dragged adalah sibling target (indeks pasca-penghapusan).

### Verifikasi format

- `vendor/bin/pint --dirty` (PHP), dan jalankan test terfilter terkait.
