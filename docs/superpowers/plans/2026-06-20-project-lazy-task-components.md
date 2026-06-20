# project-lazy Task Components Extraction — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `project-lazy` a self-contained, type-aligned copy of TaskTable + TaskFormDrawer + TaskForm and all child components, with heavy logic extracted into colocated composables; originals untouched.

**Architecture:** Copy the three components and their children into `resources/js/pages/project-lazy/task/`. Move table/drag-drop/selection/delete/form logic into `project-lazy/task/composables/*.ts` (pure functions exported for unit testing, wrapped by composables for reactivity). Retype everything to the slim lazy payloads (`ListTask`, `App.Data.Task.ProjectTaskData`).

**Tech Stack:** Vue 3 `<script setup>`, Inertia v2, PrimeVue, TypeScript, vitest (already installed), vue-tsc, vite.

## Global Constraints

- Do NOT modify any file under `resources/js/pages/project/task/` (originals untouched).
- Do NOT add runtime dependencies. `vitest` already in devDependencies — config + script only.
- Generic shared components stay referenced from `@/components` / `@/composables`: `TaskActivityLogModal`, `Icon`, `Label`, `useProjectPermissions`, `useSeverityColor`.
- All ids are sqid strings at runtime.
- Components must support dark mode the same way as the originals (preserve `dark:` classes verbatim).
- Behavior must be identical to the originals (selection, bulk delete, full drag-drop, filters, activity log, form create/edit/subtask).
- Run `vendor/bin/pint` is PHP-only — N/A here; use `prettier`/`eslint` conventions already in files.
- Type-check target: `vue-tsc --noEmit` adds 0 new errors vs 35-error baseline; should remove 4 mismatches (target ≈ 31).

---

### Task 1: Lazy types + vitest setup

**Files:**
- Modify: `resources/js/pages/project-lazy/index.d.ts` (add table/form types)
- Create: `vitest.config.ts`
- Modify: `package.json` (add `"test": "vitest run"`, `"test:watch": "vitest"`)

**Interfaces produced:** `LazyTaskFormattedData`, `LazyTaskFormatted`, `LazyTaskTableProps`, `LazyTaskTableEmits`, `LazyTaskTableFilter`, `TaskFormPayload`, `LazyMember`, `ParentTaskNode`, `LazyTaskFormProps`, `LazyTaskFormData`, `LazyTagForm`.

- [ ] **Step 1:** Append to `resources/js/pages/project-lazy/index.d.ts` (and add `UploadedFile` to the top import from `@/types`):

```ts
/* ---- Task table (List tab) ---- */
export interface LazyTaskFormattedData {
    id: string;
    parent_id: string | null;
    title: string;
    status?: TaskStatusOption;
    type?: TaskTypeOption;
    category?: TaskCategoryOption;
    users: SlimUser[];
    progress: number;
    start_date: string | null;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    level?: number;
}

export interface LazyTaskFormatted {
    key: string;
    data: LazyTaskFormattedData;
    children: LazyTaskFormatted[];
    original: ListTask;
}

export interface LazyTaskTableProps {
    projectId: string;
    tasks: ListTask[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
}

export interface LazyTaskTableEmits {
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: ListTask, parentId: string | null): void;
}

export interface LazyTaskTableFilter {
    global: string;
    'status.name': string[] | null;
    'type.name': string[] | null;
}

/* ---- Task form (drawer) ---- */
export type TaskFormPayload = App.Data.Task.ProjectTaskData;

export interface LazyMember {
    user: SlimUser;
}

export interface ParentTaskNode {
    id: string;
    title: string;
    category?: { id: string; name: string } | null;
    sub_task_recursive: ParentTaskNode[];
}

export interface LazyTagForm {
    name: string;
    severity: string;
}

export interface LazyTaskFormProps {
    parentId: string | null;
    projectId: string;
    task: TaskFormPayload | null;
    sprintId?: string | null;
    tasks: ParentTaskNode[];
    taskTypes: TaskTypeOption[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskCategories?: TaskCategoryOption[];
    excludeEpicCategory?: boolean;
    onlyEpicCategory?: boolean;
    hideParentTaskField?: boolean;
    tags: TagOption[];
    members: LazyMember[];
}

export interface LazyTaskFormData {
    _method: 'POST' | 'PUT';
    title: string;
    description: string;
    project_id: string;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    task_category_id: string | null;
    sprint_id: string | null;
    parent_id: string | null;
    start_date: Date | null;
    due_date: Date | null;
    is_archived: boolean;
    progress_value: number;
    assign_users: string[];
    unassign_users: string[];
    add_tag: { new: LazyTagForm[]; exists: string[] };
    remove_tag: string[];
    attachments: File[] | UploadedFile[] | null;
    [key: string]: any;
}
```

- [ ] **Step 2:** Create `vitest.config.ts`:

```ts
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.{test,spec}.ts'],
    },
});
```

- [ ] **Step 3:** Add scripts to `package.json` (`"test": "vitest run"`, `"test:watch": "vitest"`).

- [ ] **Step 4:** Verify type-check has no NEW errors: `./node_modules/.bin/vue-tsc --noEmit` (compare count to 35).

- [ ] **Step 5:** Commit: `feat(project-lazy): add lazy task types and vitest setup`.

---

### Task 2: `useTaskTree` composable (TDD)

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/useTaskTree.ts`
- Test: `resources/js/pages/project-lazy/task/composables/useTaskTree.test.ts`

**Interfaces produced:**
- `formatTasks(list?: ListTask[], level?: number): LazyTaskFormatted[]`
- `sortByRecency(rows: LazyTaskFormatted[]): LazyTaskFormatted[]`
- `findTaskById(list: ListTask[], id: string): ListTask | null`
- `isDescendant(rootList: ListTask[], sourceId: string, targetId: string): boolean`
- `useTaskTree(tasks: Ref<ListTask[]> | ComputedRef<ListTask[]>): { formattedTasks: Ref<LazyTaskFormatted[]>, findTaskById, isDescendant }`

- [ ] **Step 1:** Write failing test `useTaskTree.test.ts` covering: formatTasks maps fields + nests children with incremented level; sortByRecency orders by updated_at desc then created_at; findTaskById finds nested; isDescendant true for child, false for unrelated/self.

```ts
import { describe, expect, it } from 'vitest';
import type { ListTask } from '@/pages/project-lazy';
import { findTaskById, formatTasks, isDescendant, sortByRecency } from './useTaskTree';

const task = (over: Partial<ListTask> & { id: string }): ListTask => ({
    parent_id: null, title: 't', progress: 0, is_overdue: false,
    users: [], sub_task_recursive: [], ...over,
} as ListTask);

describe('formatTasks', () => {
    it('maps fields and nests children with level', () => {
        const rows = formatTasks([task({ id: '1', title: 'A', sub_task_recursive: [task({ id: '2', title: 'B' })] })]);
        expect(rows[0].key).toBe('1');
        expect(rows[0].data.level).toBe(0);
        expect(rows[0].children[0].data.level).toBe(1);
        expect(rows[0].original.id).toBe('1');
    });
    it('returns [] for undefined', () => { expect(formatTasks(undefined)).toEqual([]); });
});

describe('sortByRecency', () => {
    it('orders by updated_at desc', () => {
        const rows = formatTasks([
            task({ id: '1', updated_at: '2024-01-01' }),
            task({ id: '2', updated_at: '2024-06-01' }),
        ]);
        expect(sortByRecency(rows).map((r) => r.key)).toEqual(['2', '1']);
    });
});

describe('findTaskById / isDescendant', () => {
    const tree = [task({ id: '1', sub_task_recursive: [task({ id: '2', sub_task_recursive: [task({ id: '3' })] })] })];
    it('finds nested', () => { expect(findTaskById(tree, '3')?.id).toBe('3'); });
    it('detects descendant', () => { expect(isDescendant(tree, '1', '3')).toBe(true); });
    it('rejects non-descendant', () => { expect(isDescendant(tree, '2', '1')).toBe(false); });
});
```

- [ ] **Step 2:** Run `./node_modules/.bin/vitest run resources/js/pages/project-lazy/task/composables/useTaskTree.test.ts` — expect FAIL (module not found).

- [ ] **Step 3:** Implement `useTaskTree.ts` (port `formatTasks`/`findTaskById`/`isDescendant` from `project/task/Table.vue:77-101,114-118,261-281`, drop `priority`/`created_by`, retype to `ListTask`/`LazyTaskFormatted`; `useTaskTree` wraps with `ref` + `watch(immediate, deep:false)` doing sortByRecency, mirroring `Table.vue:104-123`).

- [ ] **Step 4:** Run the test — expect PASS.

- [ ] **Step 5:** Commit: `feat(project-lazy): add useTaskTree composable`.

---

### Task 3: `useTaskCategoryStyle` composable (TDD)

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/useTaskCategoryStyle.ts`
- Test: `resources/js/pages/project-lazy/task/composables/useTaskCategoryStyle.test.ts`

**Interfaces produced:**
- `taskCategoryIcon(category?: TaskCategoryOption | null): string`
- `taskCategoryColorByName(category?: TaskCategoryOption | null): string`
- `useTaskCategoryStyle(): { getCategoryIcon, getCategoryColor }`

- [ ] **Step 1:** Failing test: `taskCategoryIcon` returns explicit icon when present, byName fallback for `Epic`→`pi pi-bolt`, default `pi pi-tag`; `taskCategoryColorByName` returns name-map color and `#64748b` default.

```ts
import { describe, expect, it } from 'vitest';
import { taskCategoryColorByName, taskCategoryIcon } from './useTaskCategoryStyle';

describe('taskCategoryIcon', () => {
    it('prefers explicit icon', () => { expect(taskCategoryIcon({ id: '1', name: 'Epic', icon: 'pi pi-star' })).toBe('pi pi-star'); });
    it('falls back by name', () => { expect(taskCategoryIcon({ id: '1', name: 'Epic' })).toBe('pi pi-bolt'); });
    it('defaults to tag', () => { expect(taskCategoryIcon(null)).toBe('pi pi-tag'); });
});

describe('taskCategoryColorByName', () => {
    it('maps known name', () => { expect(taskCategoryColorByName({ id: '1', name: 'Story' })).toBe('#16a34a'); });
    it('defaults', () => { expect(taskCategoryColorByName(null)).toBe('#64748b'); });
});
```

- [ ] **Step 2:** Run test — expect FAIL.
- [ ] **Step 3:** Implement (port `getCategoryIcon`/`getCategoryColor` from `Table.vue:480-506`; `getCategoryColor` keeps `useSeverityColor().getSeverityColorLight(severity, 0.2)` when severity present, else `taskCategoryColorByName`).
- [ ] **Step 4:** Run test — expect PASS.
- [ ] **Step 5:** Commit: `feat(project-lazy): add useTaskCategoryStyle composable`.

---

### Task 4: `useTaskFormDrawer` composable + `buildParentTree` (TDD)

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/useTaskFormDrawer.ts`
- Test: `resources/js/pages/project-lazy/task/composables/useTaskFormDrawer.test.ts`

**Interfaces produced:**
- `buildParentTree(flat: ParentTaskOption[]): ParentTaskNode[]`
- `useTaskFormDrawer(projectId: Ref<string>|string, emit: (e: 'saved') => void): { visible, loading, task, parentTree, parentId, header, openCreate, openEdit, close }`

- [ ] **Step 1:** Failing test for `buildParentTree`: flat list with parent refs nests; orphans become roots; ids coerced to string.

```ts
import { describe, expect, it } from 'vitest';
import type { ParentTaskOption } from '@/pages/project-lazy';
import { buildParentTree } from './useTaskFormDrawer';

describe('buildParentTree', () => {
    it('nests children under parents', () => {
        const flat: ParentTaskOption[] = [
            { id: '1', parent_id: null, title: 'root' },
            { id: '2', parent_id: '1', title: 'child' },
        ];
        const tree = buildParentTree(flat);
        expect(tree).toHaveLength(1);
        expect(tree[0].id).toBe('1');
        expect(tree[0].sub_task_recursive[0].id).toBe('2');
    });
    it('treats unknown parent as root', () => {
        const tree = buildParentTree([{ id: '5', parent_id: '99', title: 'x' }]);
        expect(tree).toHaveLength(1);
        expect(tree[0].id).toBe('5');
    });
});
```

- [ ] **Step 2:** Run test — expect FAIL.
- [ ] **Step 3:** Implement: `buildParentTree` = current `buildTree` from `TaskFormDrawer.vue:37-55` (typed `ParentTaskNode`); `useTaskFormDrawer` ports `visible/loading/task/parentTree/parentId/header/fetchParentOptions/openCreate/openEdit/onSaved/close` from `TaskFormDrawer.vue:21-101`, with `task` typed `Ref<TaskFormPayload | null>`, `parentTree` typed `Ref<ParentTaskNode[]>`. Uses `axios`, `route`, `useToast`.
- [ ] **Step 4:** Run test — expect PASS.
- [ ] **Step 5:** Commit: `feat(project-lazy): add useTaskFormDrawer composable`.

---

### Task 5: Stateful composables (selection, actions, drag-drop, form)

These wrap reactive/UI logic; verified by type-check + build (no isolated unit test — they require component/Inertia/PrimeVue context).

**Files:**
- Create: `resources/js/pages/project-lazy/task/composables/useTaskSelection.ts`
- Create: `resources/js/pages/project-lazy/task/composables/useTaskActions.ts`
- Create: `resources/js/pages/project-lazy/task/composables/useTaskDragDrop.ts`
- Create: `resources/js/pages/project-lazy/task/composables/useTaskForm.ts`

**Interfaces produced:**
- `useTaskSelection(formattedTasks: Ref<LazyTaskFormatted[]>): { selectedKey, expandedKeys, isAllSelected, hasSelectedTasks, selectedIds, toggleSelectAll, selectAll, clearSelection, setSelected }`
- `useTaskActions(projectId: string): { deleteLoading, remove(task: { id: string; title: string }), removeSelected(ids: string[], onCleared: () => void) }`
- `useTaskDragDrop(ctx: { projectId, tasks: Ref<ListTask[]>, expandedKeys: Ref<Record<string,boolean>>, canMove: Ref<boolean>, findTaskById, isDescendant }): { ...all handlers + isDraggingTask, isRootDropActive, activeDragTaskId, dropTargetTaskId, dragArmedTaskId, canMoveTask }`
- `useTaskForm(props: LazyTaskFormProps, emit): { form, selectedMembers, selectedTags, validationErrors, ...computeds, onStatusChange, submit }`

- [ ] **Step 1:** Implement `useTaskSelection` (port `Table.vue:27-28,125-189` selectedKey/expandedKeys/isAllSelected/hasSelectedTasks/selectAll/clearSelection/toggleSelectAll; add `selectedIds = computed(() => Object.keys(selectedKey.value))` and `setSelected`).
- [ ] **Step 2:** Implement `useTaskActions` (port `Table.vue:141-215` remove/removeSelected/deleteLoading using `useConfirm`/`useToast`/`router`; `removeSelected` takes ids + `onCleared` callback to reset selection).
- [ ] **Step 3:** Implement `useTaskDragDrop` (port the full block `Table.vue:44-477` minus tree/selection: drag state refs, constants, computed flags, all `on*`/`finalize*`/`schedule*`/`reset*`/`updateTaskParent`/`moveTaskWithValidation`; `onMounted`/`onBeforeUnmount` register `onGlobalMouseUp`; receives `findTaskById`/`isDescendant`/`canMove` from caller).
- [ ] **Step 4:** Implement `useTaskForm` (port `Form.vue:32-292` form state/computeds/watchers/submit, retyped `LazyTaskFormProps`/`LazyTaskFormData`; `findTaskById` over `ParentTaskNode[]`; members typed `SlimUser`).
- [ ] **Step 5:** Type-check: `./node_modules/.bin/vue-tsc --noEmit` (0 new errors). Commit: `feat(project-lazy): add task selection/actions/drag-drop/form composables`.

---

### Task 6: Copy form-ui partials (retyped)

**Files (create, copied from `project/task/partials/form-ui/`):**
`resources/js/pages/project-lazy/task/partials/form-ui/{InputAttachment,InputDateRange,InputDescription,InputTags,InputTitle,SelectArchivedProgress,SelectCategory,SelectMembers,SelectParentTask,SelectTypeStatusPriority}.vue`

**Type substitutions:** `import { TaskCategory } from '@/pages/project'` → `TaskCategoryOption` from `@/pages/project-lazy`; `User` → `SlimUser`; `ProjectTask` → `ParentTaskNode`. Keep `@/components/Icon.vue`, `@/components/Label.vue`, `@/types` imports as-is.

- [ ] **Step 1:** Copy all 10 files verbatim into the new dir.
- [ ] **Step 2:** Apply the type substitutions in `SelectCategory.vue`, `SelectMembers.vue`, `SelectParentTask.vue` (others have no project-type imports).
- [ ] **Step 3:** Type-check (0 new errors).
- [ ] **Step 4:** Commit: `feat(project-lazy): copy retyped form-ui partials`.

---

### Task 7: Copy TaskTableFilters + TaskTableToolbar (retyped)

**Files (create):**
- `resources/js/pages/project-lazy/task/partials/TaskTableFilters.vue`
- `resources/js/pages/project-lazy/task/partials/TaskTableToolbar.vue`

**Substitutions:**
- Filters: `ProjectTaskTableFilter`/`TaskStatus`/`TaskType` from `@/pages/project` → `LazyTaskTableFilter`/`TaskStatusOption`/`TaskTypeOption` from `@/pages/project-lazy`.
- Toolbar: fix `usePage<ProjectDetailProps>().props.policy` → `usePage<ShellProps>().props.policy` (uses `ShellProps` from `@/pages/project-lazy`); import `useProjectPermissions` from `@/composables/useProjectPermissions`.

- [ ] **Step 1:** Create both files from originals with substitutions.
- [ ] **Step 2:** Type-check (0 new errors; toolbar `PageProps` error gone in the copy).
- [ ] **Step 3:** Commit: `feat(project-lazy): copy retyped task table filters and toolbar`.

---

### Task 8: `TaskForm.vue`

**Files:** Create `resources/js/pages/project-lazy/task/TaskForm.vue`.

- [ ] **Step 1:** Create from `project/task/Form.vue` template, swapping logic for `const { form, selectedMembers, selectedTags, validationErrors, statusOption, categoryOptions, requiresDates, minDueDate, isInProgressStatus, isEdit, formattedMemberOption, tagOptions, fieldDisabled, onStatusChange, submit } = useTaskForm(props, emit)`; import partials from `./partials/form-ui/*`; props `defineProps<LazyTaskFormProps>()`.
- [ ] **Step 2:** Type-check (0 new errors).
- [ ] **Step 3:** Commit: `feat(project-lazy): add self-contained TaskForm`.

---

### Task 9: `TaskFormDrawer.vue` (rewrite to local Form + composable)

**Files:** Modify `resources/js/pages/project-lazy/task/TaskFormDrawer.vue`.

- [ ] **Step 1:** Replace script with `useTaskFormDrawer`; import `TaskForm from './TaskForm.vue'`; `defineProps<Props>()` unchanged (projectId + option lists + tags + assignableUsers); `members = computed(() => props.assignableUsers.map((user) => ({ user })))` typed `LazyMember[]`; `defineExpose({ openCreate, openEdit })`. Template: Drawer + skeleton + `<TaskForm>` (same props as before, now type-clean).
- [ ] **Step 2:** Type-check — the `:members` line 139 error must be GONE.
- [ ] **Step 3:** Commit: `refactor(project-lazy): drawer uses local TaskForm + composable`.

---

### Task 10: `TaskTable.vue`

**Files:** Create `resources/js/pages/project-lazy/task/TaskTable.vue`.

- [ ] **Step 1:** Create from `project/task/Table.vue` template verbatim, swapping the script to compose: `defineProps<LazyTaskTableProps>()`, `defineEmits<LazyTaskTableEmits>()`; `policy = inject(ProjectPolicyKey, null)`; `{ canAction } = useProjectPermissions(policy)`; `canTaskCreate/Update/Delete/canMoveTask` computeds; `const tasksRef = computed(() => props.tasks)`; `{ formattedTasks, findTaskById, isDescendant } = useTaskTree(tasksRef)`; `{ selectedKey, expandedKeys, isAllSelected, hasSelectedTasks, selectedIds, toggleSelectAll, setSelected } = useTaskSelection(formattedTasks)`; `{ deleteLoading, remove, removeSelected } = useTaskActions(props.projectId)`; drag-drop via `useTaskDragDrop({ projectId: props.projectId, tasks: tasksRef, expandedKeys, canMove: canMoveTask, findTaskById, isDescendant })`; `{ getCategoryIcon, getCategoryColor } = useTaskCategoryStyle()`; keep `activityModal` + `openActivityLog` + `formatDate` local; import `TaskActivityLogModal from '@/components/TaskActivityLogModal.vue'`, filters/toolbar from `./partials/*`. `filters` via `useSessionStorage<LazyTaskTableFilter>(...)`. Bulk delete button calls `removeSelected(selectedIds, () => setSelected({}))`.
- [ ] **Step 2:** Type-check (0 new errors).
- [ ] **Step 3:** Commit: `feat(project-lazy): add self-contained TaskTable`.

---

### Task 11: Wire `List.vue` + full verification

**Files:** Modify `resources/js/pages/project-lazy/List.vue`.

- [ ] **Step 1:** Change `import TaskTable from '@/pages/project/task/Table.vue'` → `import TaskTable from './task/TaskTable.vue'`. Keep template (props already match). `onEdit` stays `(task: ListTask) => drawer.value?.openEdit(task)`.
- [ ] **Step 2:** Run `./node_modules/.bin/vue-tsc --noEmit`; confirm List.vue:28/34 errors gone and total ≤ 31, 0 new.
- [ ] **Step 3:** Run `./node_modules/.bin/vitest run` — all composable tests pass.
- [ ] **Step 4:** Run `npm run build` — succeeds.
- [ ] **Step 5:** Commit: `feat(project-lazy): wire List to self-contained TaskTable`.

---

### Task 12: Merge + cleanup worktree

- [ ] **Step 1:** From main checkout: `git -C /Users/itrnd/Projects/project-management checkout refactor/project-detail` (already there) then `git merge --no-ff refactor/project-lazy-task-components`.
- [ ] **Step 2:** Remove worktree: `git worktree remove .worktree/project-lazy-task`.
- [ ] **Step 3:** Delete branch: `git branch -d refactor/project-lazy-task-components`.
- [ ] **Step 4:** Confirm `git worktree list` and `git branch` are clean.

---

## Self-Review

**Spec coverage:** structure (T6–T10), composables (T2–T5), type alignment (T1), wiring (T11), testing/verification (T1–T5,T11), worktree+merge+cleanup (T12). ✓
**Placeholder scan:** none — copies enumerate exact source line ranges + substitutions. ✓
**Type consistency:** `LazyTaskFormatted`, `ParentTaskNode`, `LazyTaskFormProps/Data`, `TaskFormPayload`, `LazyMember` defined in T1 and consumed consistently in T2–T10. ✓
