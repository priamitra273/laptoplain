# Detail.vue Composable Extraction Implementation Plan (TDD)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memindahkan logika bisnis (update project + orchestration dialog task) dari `pages/project/Detail.vue` ke dua composable yang teruji unit (Vitest), sehingga `Detail.vue` menjadi tipis dan hanya berperan sebagai composition root.

**Architecture:** Ekstrak dua unit logika ke `resources/js/composables/`: (1) `useProjectUpdate` membungkus build-payload + format tanggal + `router.put` + toast; (2) `useTaskDialog` membungkus seluruh state drawer task (`visible`, `selectedTask`, flag) plus computed flag yang error-prone (`excludeEpicCategory`, `onlyEpicCategory`, `hideParentTaskField`). Keduanya murni logika Composition API (`ref`/`computed`) tanpa render komponen, sehingga bisa diuji dengan Vitest `environment: 'node'` — tanpa `@vue/test-utils`/jsdom. `Detail.vue` mendestrukturisasi return composable di top-level `<script setup>` agar ref otomatis ter-unwrap di template. Refactoring ini **behavior-preserving**.

**Tech Stack:** Vue 3 (`<script setup lang="ts">`), Composition API, Inertia.js (`router`), PrimeVue (`useToast`), moment, Ziggy (`route()` global, dideklarasikan di `resources/js/types/globals.d.ts`). Test: **Vitest 4** (baru diinstall). Verifikasi tambahan: `vue-tsc` (type-check) + `vite build` + `eslint`.

---

## File Structure

| File | Tanggung jawab |
|------|----------------|
| `vitest.config.ts` (Create) | Konfigurasi Vitest: alias `@` → `resources/js`, `environment: 'node'`, pola include test. |
| `package.json` (Modify) | Tambah script `"test": "vitest run"` dan `"test:watch": "vitest"`. |
| `resources/js/composables/useProjectUpdate.ts` (Create) | Fungsi `update(newValue, field)`: guard izin → build payload → format tanggal → `router.put` + toast. |
| `resources/js/composables/useProjectUpdate.test.ts` (Create) | Unit test untuk guard izin, payload, format tanggal, callback sukses/gagal. |
| `resources/js/composables/useTaskDialog.ts` (Create) | State + orchestration drawer task. Expose `visible`, `selectedTask`, `parentTaskId`, `selectedSprintId`, `header`, `formFlags`, `openAdd`, `openEdit`, `close`. |
| `resources/js/composables/useTaskDialog.test.ts` (Create) | Unit test untuk guard izin, header, flag drawer, dan reset state. |
| `resources/js/pages/project/Detail.vue` (Modify) | Hapus `updateProject`, hapus state dialog task + handler. Pakai kedua composable. |

**Catatan ruang lingkup:** Dialog member (`MemberAddForm`/`MemberEditForm`, `visibleAdd`/`visibleEdit`/`selectedMember`) TIDAK diekstrak. `activeSprintTaskIds`/`activeSprintTasks`/`findTaskById` tetap di `Detail.vue`. Lazy-render tab (saran C) & ekstrak grup dialog (saran D) di luar plan ini.

---

## Task 1: Setup Vitest

**Files:**
- Create: `vitest.config.ts`
- Modify: `package.json` (blok `scripts`)

- [ ] **Step 1: Buat konfigurasi Vitest**

Create `vitest.config.ts` (project ESM, jadi pakai `fileURLToPath`):

```ts
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.{test,spec}.ts'],
    },
});
```

- [ ] **Step 2: Tambah script test di package.json**

Di `package.json`, dalam blok `"scripts"`, tambahkan dua entri (setelah `"lint"`):

```json
        "test": "vitest run",
        "test:watch": "vitest"
```

- [ ] **Step 3: Buat sanity test sementara untuk memverifikasi runner**

Create `resources/js/composables/__sanity.test.ts`:

```ts
import { describe, expect, it } from 'vitest';

describe('vitest sanity', () => {
    it('runner berjalan', () => {
        expect(1 + 1).toBe(2);
    });
});
```

- [ ] **Step 4: Jalankan test untuk memverifikasi setup**

Run: `npm run test`
Expected: PASS — 1 file, 1 test passed.

- [ ] **Step 5: Hapus sanity test**

```bash
rm resources/js/composables/__sanity.test.ts
```

- [ ] **Step 6: Commit**

```bash
git add vitest.config.ts package.json
git commit -m "chore: configure vitest for unit tests"
```

---

## Task 2: `useProjectUpdate` (TDD)

**Files:**
- Create: `resources/js/composables/useProjectUpdate.test.ts`
- Create: `resources/js/composables/useProjectUpdate.ts`

Composable memindahkan `updateProject` (Detail.vue:92-124). `project` dan `canEdit` diteruskan sebagai getter agar reaktif terhadap props/computed pemanggil. `route` tersedia global (tidak perlu import; di test di-stub via `vi.stubGlobal`).

- [ ] **Step 1: Tulis test yang gagal**

Create `resources/js/composables/useProjectUpdate.test.ts`:

```ts
import type { Project } from '@/pages/project/index';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useProjectUpdate } from './useProjectUpdate';

vi.mock('@inertiajs/vue3', () => ({
    router: { put: vi.fn() },
}));

const putMock = router.put as unknown as ReturnType<typeof vi.fn>;

const makeProject = (): Project =>
    ({
        id: 'proj-1',
        project_no: 'P-1',
        title: 'My Project',
        description: 'desc',
        emoji: '🚀',
        progress: 0,
        start_date: '2026-01-01',
        due_date: '2026-02-01',
        status_id: 'status-1',
        priority_id: 'prio-1',
        project_members: [],
    }) as Project;

const makeToast = () => ({ add: vi.fn() });

beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('route', vi.fn((name: string, id: string) => `/${name}/${id}`));
});

describe('useProjectUpdate', () => {
    it('menolak update saat user tidak punya izin edit', () => {
        const toast = makeToast();
        const { update } = useProjectUpdate(makeProject, () => false, toast as any);

        update('New Title', 'title');

        expect(putMock).not.toHaveBeenCalled();
        expect(toast.add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'warn' }));
    });

    it('mengirim payload lengkap dengan field yang diubah', () => {
        const toast = makeToast();
        const { update } = useProjectUpdate(makeProject, () => true, toast as any);

        update('New Title', 'title');

        expect(putMock).toHaveBeenCalledTimes(1);
        const [url, payload] = putMock.mock.calls[0];
        expect(url).toBe('/project.update/proj-1');
        expect(payload).toMatchObject({
            title: 'New Title',
            status_id: 'status-1',
            priority_id: 'prio-1',
        });
    });

    it('memformat field tanggal ke YYYY-MM-DD', () => {
        const toast = makeToast();
        const { update } = useProjectUpdate(makeProject, () => true, toast as any);

        update('2026-03-15', 'due_date');

        const [, payload] = putMock.mock.calls[0];
        expect(payload.due_date).toBe('2026-03-15');
    });

    it('menampilkan toast sukses pada onSuccess', () => {
        const toast = makeToast();
        const { update } = useProjectUpdate(makeProject, () => true, toast as any);

        update('New Title', 'title');
        const options = putMock.mock.calls[0][2];
        options.onSuccess();

        expect(toast.add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
    });

    it('menampilkan pesan error pertama pada onError', () => {
        const toast = makeToast();
        const { update } = useProjectUpdate(makeProject, () => true, toast as any);

        update('New Title', 'title');
        const options = putMock.mock.calls[0][2];
        options.onError({ title: 'Judul wajib diisi' });

        expect(toast.add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error', detail: 'Judul wajib diisi' }));
    });
});
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `npm run test -- useProjectUpdate`
Expected: FAIL — gagal me-resolve `./useProjectUpdate` (module belum ada).

- [ ] **Step 3: Implementasi composable**

Create `resources/js/composables/useProjectUpdate.ts`:

```ts
import type { Project } from '@/pages/project/index';
import { router } from '@inertiajs/vue3';
import moment from 'moment';
import type { useToast } from 'primevue/usetoast';

type Toast = ReturnType<typeof useToast>;

export function useProjectUpdate(getProject: () => Project, getCanEdit: () => boolean, toast: Toast) {
    const update = (newValue: any, field: string) => {
        if (!getCanEdit()) {
            toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You do not have permission to edit this project', life: 3000 });
            return;
        }

        const project = getProject();

        const payload: Record<string, any> = {
            title: project.title,
            description: project.description || '',
            emoji: project.emoji,
            start_date: project.start_date,
            due_date: project.due_date,
            status_id: project.status_id || project.status?.id,
            priority_id: project.priority_id || project.priority?.id,
        };

        if (field === 'start_date' || field === 'due_date') payload[field] = moment(newValue).format('YYYY-MM-DD');
        else payload[field] = newValue;

        if (payload.start_date && typeof payload.start_date !== 'string') payload.start_date = moment(payload.start_date).format('YYYY-MM-DD');
        if (payload.due_date && typeof payload.due_date !== 'string') payload.due_date = moment(payload.due_date).format('YYYY-MM-DD');

        router.put(route('project.update', project.id), payload, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Project updated successfully', life: 3000 });
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Error', detail: errors[Object.keys(errors)[0]] || 'Failed to update project', life: 3000 });
            },
        });
    };

    return { update };
}
```

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `npm run test -- useProjectUpdate`
Expected: PASS — 5 test lulus.

- [ ] **Step 5: Commit**

```bash
git add resources/js/composables/useProjectUpdate.ts resources/js/composables/useProjectUpdate.test.ts
git commit -m "refactor(project): add tested useProjectUpdate composable"
```

---

## Task 3: Wire `useProjectUpdate` ke Detail.vue

**Files:**
- Modify: `resources/js/pages/project/Detail.vue:92-124` (hapus fungsi `updateProject`), tambah import + pemanggilan composable.

- [ ] **Step 1: Tambah import composable**

Di `Detail.vue`, tambahkan di kelompok import `@/composables` (dekat baris 3):

```ts
import { useProjectUpdate } from '@/composables/useProjectUpdate';
```

- [ ] **Step 2: Hapus fungsi `updateProject` lama dan ganti dengan composable**

Hapus seluruh blok `const updateProject = (newValue: any, field: string) => { ... };` (Detail.vue:92-124).

Tambahkan setelah `const canEdit = computed(() => canAction('project_member', 'update'));` (baris 36):

```ts
const { update: updateProject } = useProjectUpdate(
    () => props.project,
    () => canEdit.value,
    toast,
);
```

Nama lokal tetap `updateProject` sehingga `@update="updateProject"` di template (baris 204, 206, 274) TIDAK perlu diubah.

- [ ] **Step 3: Jalankan seluruh test (pastikan tidak ada regresi)**

Run: `npm run test`
Expected: PASS — seluruh test lulus.

- [ ] **Step 4: Type-check**

Run: `npx vue-tsc --noEmit`
Expected: PASS, tanpa error baru.

- [ ] **Step 5: Build**

Run: `npm run build`
Expected: build sukses (exit 0).

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/project/Detail.vue
git commit -m "refactor(project): use useProjectUpdate in Detail.vue"
```

---

## Task 4: `useTaskDialog` (TDD)

**Files:**
- Create: `resources/js/composables/useTaskDialog.test.ts`
- Create: `resources/js/composables/useTaskDialog.ts`

Memindahkan state drawer task (Detail.vue:41-47), `openTaskAdd` (133-145), `openTaskEdit` (147-158), `onDialogClosed` (169-176), `taskDialogHeader` (189-192), serta tiga ekspresi flag inline template (353-355) ke computed `formFlags`.

- [ ] **Step 1: Tulis test yang gagal**

Create `resources/js/composables/useTaskDialog.test.ts`:

```ts
import type { Task } from '@/pages/project/index';
import { describe, expect, it, vi } from 'vitest';
import { useTaskDialog } from './useTaskDialog';

const allow = () => vi.fn(() => true);
const deny = () => vi.fn(() => false);
const makeToast = () => ({ add: vi.fn() });
const makeTask = (): Task => ({ id: 'task-1', title: 'Task 1' }) as Task;

describe('useTaskDialog', () => {
    it('menolak openAdd saat tidak ada izin create', () => {
        const toast = makeToast();
        const d = useTaskDialog(deny(), toast as any);

        d.openAdd(null);

        expect(d.visible.value).toBe(false);
        expect(toast.add).toHaveBeenCalledWith(expect.objectContaining({ severity: 'warn' }));
    });

    it('openAdd biasa membuka drawer dengan header Create Task', () => {
        const d = useTaskDialog(allow(), makeToast() as any);

        d.openAdd(null);

        expect(d.visible.value).toBe(true);
        expect(d.selectedTask.value).toBeNull();
        expect(d.header.value).toBe('Create Task');
    });

    it('openAdd dengan parentId menampilkan header Create Subtask', () => {
        const d = useTaskDialog(allow(), makeToast() as any);

        d.openAdd('parent-1');

        expect(d.header.value).toBe('Create Subtask');
    });

    it('source backlog menyembunyikan parent field dan mengecualikan epic', () => {
        const d = useTaskDialog(allow(), makeToast() as any);

        d.openAdd(null, undefined, 'backlog');

        expect(d.formFlags.value.excludeEpicCategory).toBe(true);
        expect(d.formFlags.value.hideParentTaskField).toBe(true);
        expect(d.formFlags.value.onlyEpicCategory).toBe(false);
    });

    it('source backlog-add-parent mengaktifkan onlyEpicCategory', () => {
        const d = useTaskDialog(allow(), makeToast() as any);

        d.openAdd(null, undefined, 'backlog-add-parent');

        expect(d.formFlags.value.onlyEpicCategory).toBe(true);
        expect(d.formFlags.value.hideParentTaskField).toBe(true);
    });

    it('membuat task dalam sprint menyembunyikan parent field', () => {
        const d = useTaskDialog(allow(), makeToast() as any);

        d.openAdd(null, undefined, undefined, 'sprint-1');

        expect(d.selectedSprintId.value).toBe('sprint-1');
        expect(d.formFlags.value.hideParentTaskField).toBe(true);
        expect(d.formFlags.value.excludeEpicCategory).toBe(true);
    });

    it('openEdit memuat task dan menampilkan header Edit Task', () => {
        const d = useTaskDialog(allow(), makeToast() as any);
        const task = makeTask();

        d.openEdit(task, null);

        expect(d.visible.value).toBe(true);
        expect(d.selectedTask.value).toBe(task);
        expect(d.header.value).toBe('Edit Task');
    });

    it('close mereset seluruh state', () => {
        const d = useTaskDialog(allow(), makeToast() as any);
        d.openAdd('parent-1', undefined, 'backlog', 'sprint-1');

        d.close();

        expect(d.visible.value).toBe(false);
        expect(d.selectedTask.value).toBeNull();
        expect(d.parentTaskId.value).toBeNull();
        expect(d.selectedSprintId.value).toBeNull();
        expect(d.formFlags.value.excludeEpicCategory).toBe(false);
        expect(d.formFlags.value.onlyEpicCategory).toBe(false);
        expect(d.formFlags.value.hideParentTaskField).toBe(false);
    });
});
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `npm run test -- useTaskDialog`
Expected: FAIL — gagal me-resolve `./useTaskDialog` (module belum ada).

- [ ] **Step 3: Implementasi composable**

Create `resources/js/composables/useTaskDialog.ts`:

```ts
import type { Task } from '@/pages/project/index';
import type { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

type Toast = ReturnType<typeof useToast>;
type CanAction = (resource: string, action: string) => boolean;

export function useTaskDialog(canAction: CanAction, toast: Toast) {
    const visible = ref(false);
    const selectedTask = ref<Task | null>(null);
    const parentTaskId = ref<string | null>(null);
    const selectedSprintId = ref<string | null>(null);
    const isBacklogCreate = ref(false);
    const isAddParentCreate = ref(false);

    const header = computed(() => {
        if (selectedTask.value) return 'Edit Task';
        return parentTaskId.value ? 'Create Subtask' : 'Create Task';
    });

    const formFlags = computed(() => ({
        excludeEpicCategory: isBacklogCreate.value || (!selectedTask.value && (!!selectedSprintId.value || !!parentTaskId.value)),
        onlyEpicCategory: !selectedTask.value && isAddParentCreate.value,
        hideParentTaskField: isBacklogCreate.value || isAddParentCreate.value || (!selectedTask.value && !!selectedSprintId.value),
    }));

    const openAdd = (parentId: string | null, _statusId?: string, source?: string, sprintId?: string | null) => {
        if (!canAction('task', 'create')) {
            toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You must be a project member to create tasks', life: 3000 });
            return;
        }
        selectedTask.value = null;
        isBacklogCreate.value = source === 'backlog' || source === 'backlog-add-parent';
        isAddParentCreate.value = source === 'backlog-add-parent' || source === 'sprint-add-parent';
        selectedSprintId.value = sprintId ?? null;
        parentTaskId.value = parentId;
        visible.value = true;
    };

    const openEdit = (task: Task, parentId: string | null, source?: string) => {
        if (!canAction('task', 'update')) {
            toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You must be a project member to edit tasks', life: 3000 });
            return;
        }
        isBacklogCreate.value = source === 'backlog';
        isAddParentCreate.value = false;
        selectedSprintId.value = null;
        parentTaskId.value = parentId;
        selectedTask.value = task;
        visible.value = true;
    };

    const close = () => {
        visible.value = false;
        selectedTask.value = null;
        parentTaskId.value = null;
        isBacklogCreate.value = false;
        isAddParentCreate.value = false;
        selectedSprintId.value = null;
    };

    return { visible, selectedTask, parentTaskId, selectedSprintId, header, formFlags, openAdd, openEdit, close };
}
```

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `npm run test -- useTaskDialog`
Expected: PASS — 8 test lulus.

- [ ] **Step 5: Commit**

```bash
git add resources/js/composables/useTaskDialog.ts resources/js/composables/useTaskDialog.test.ts
git commit -m "refactor(project): add tested useTaskDialog composable"
```

---

## Task 5: Wire `useTaskDialog` ke Detail.vue

**Files:**
- Modify: `resources/js/pages/project/Detail.vue` — hapus state dialog task (41-47), handler (133-158, 169-176), `taskDialogHeader` (189-192); ubah template Drawer (327-360).

State member (`visibleAdd`/`visibleEdit`/`selectedMember`) dan `activeSprintTaskIds` TETAP.

- [ ] **Step 1: Tambah import composable**

Tambahkan di kelompok import `@/composables`:

```ts
import { useTaskDialog } from '@/composables/useTaskDialog';
```

- [ ] **Step 2: Hapus ref dialog task lama, pertahankan ref member & sprint**

Pada blok "Dialog State" (Detail.vue:39-48), HAPUS baris berikut:

```ts
const visibleTaskAdd = ref(false);
const selectedTask = ref<Task | null>(null);
const parentTaskId = ref<string | null>(null);
const isBacklogCreate = ref(false);
const isAddParentCreate = ref(false);
const selectedSprintId = ref<string | null>(null);
```

PERTAHANKAN baris ini:

```ts
const visibleAdd = ref(false);
const visibleEdit = ref(false);
const selectedMember = ref<ProjectMember | null>(null);
const activeSprintTaskIds = ref<string[]>([]);
```

- [ ] **Step 3: Tambah pemanggilan `useTaskDialog` dengan destrukturisasi**

Setelah pemanggilan `useProjectUpdate` (dari Task 3), tambahkan:

```ts
const {
    visible: visibleTaskAdd,
    selectedTask,
    parentTaskId,
    selectedSprintId,
    header: taskDialogHeader,
    formFlags,
    openAdd: openTaskAdd,
    openEdit: openTaskEdit,
    close: onDialogClosed,
} = useTaskDialog(canAction, toast);
```

Destrukturisasi ref di top-level `<script setup>` tetap auto-unwrap di template, sehingga `v-model:visible="visibleTaskAdd"` dll. berfungsi tanpa `.value`.

- [ ] **Step 4: Hapus handler & computed yang sudah dipindah**

HAPUS dari `Detail.vue`:
- `const openTaskAdd = (...) => { ... };` (133-145)
- `const openTaskEdit = (...) => { ... };` (147-158)
- `const onDialogClosed = () => { ... };` (169-176)
- `const taskDialogHeader = computed(() => { ... });` (189-192)

JANGAN hapus: `openAdd`, `openEdit` (member), `onSaved`, `onTaskSaved`, `formattedMembers`, `onKanbanStatusUpdate`, `activeSprintTasks`, `findTaskById`.

- [ ] **Step 5: Ganti ekspresi flag inline di template Drawer dengan `formFlags`**

Pada `<TaskForm>` (Detail.vue:341-359), ganti tiga baris:

```vue
                :excludeEpicCategory="isBacklogCreate || (!selectedTask && (!!selectedSprintId || !!parentTaskId))"
                :onlyEpicCategory="!selectedTask && isAddParentCreate"
                :hideParentTaskField="isBacklogCreate || isAddParentCreate || (!selectedTask && !!selectedSprintId)"
```

menjadi:

```vue
                :excludeEpicCategory="formFlags.excludeEpicCategory"
                :onlyEpicCategory="formFlags.onlyEpicCategory"
                :hideParentTaskField="formFlags.hideParentTaskField"
```

Bagian template lain (`v-model:visible="visibleTaskAdd"`, `:header="taskDialogHeader"`, `@hide="onDialogClosed"`, `:task="selectedTask"`, `:parentId="parentTaskId"`, `:sprintId="selectedSprintId"`, `@close="onDialogClosed"`) TIDAK berubah.

- [ ] **Step 6: Bersihkan import tak terpakai**

`Task` masih dipakai (`findTaskById`/`activeSprintTasks`) → PERTAHANKAN. `computed`/`provide`/`ref` masih dipakai → PERTAHANKAN. Jika `eslint`/`vue-tsc` melaporkan import tak terpakai, hapus hanya yang dilaporkan.

- [ ] **Step 7: Jalankan seluruh test**

Run: `npm run test`
Expected: PASS — seluruh test lulus.

- [ ] **Step 8: Type-check**

Run: `npx vue-tsc --noEmit`
Expected: PASS, tanpa error baru.

- [ ] **Step 9: Build**

Run: `npm run build`
Expected: build sukses (exit 0).

- [ ] **Step 10: Lint**

Run: `npm run lint`
Expected: tidak ada error pada file yang diubah.

- [ ] **Step 11: Commit**

```bash
git add resources/js/pages/project/Detail.vue
git commit -m "refactor(project): use useTaskDialog in Detail.vue"
```

---

## Task 6: Smoke test manual end-to-end

**Files:** (tidak ada perubahan — verifikasi saja)

Unit test mencakup logika composable; smoke test memverifikasi integrasi UI behavior-preserving.

- [ ] **Step 1: Jalankan dev server**

Run: `npm run dev` (+ backend Laravel)

- [ ] **Step 2: Verifikasi update project**

Ubah field di `ProjectHeader`/`ProjectStats`/`Details` (title, status, start_date, due_date). Expected: toast "Project updated successfully"; tanggal tersimpan `YYYY-MM-DD`; user tanpa izin → toast "Access Denied".

- [ ] **Step 3: Verifikasi dialog task (semua jalur)**

- Kanban → Add: header "Create Task".
- Add subtask (parentId): header "Create Subtask".
- Backlog "Add Backlog" (`backlog`): parent field tersembunyi, epic dikecualikan.
- Backlog "add parent" (`backlog-add-parent`): hanya epic.
- Sprint add (`sprintId`): parent field tersembunyi.
- Edit task: header "Edit Task", data ter-load.
- Tutup drawer (@hide/@close): buka lagi → form bersih.

Expected: identik dengan sebelum refactoring.

- [ ] **Step 4: Verifikasi dialog member tidak terdampak**

Tab Team → Add/Edit Member tetap berfungsi.

---

## Catatan untuk plan lanjutan (di luar scope ini)

Kandidat berikutnya: **C** (lazy-render isi tab via `v-if="activeTab === ..."`) dan **D** (ekstrak grup `<Dialog>`/`<Drawer>` ke `<ProjectDialogs>`). Dibuat sebagai plan terpisah.
