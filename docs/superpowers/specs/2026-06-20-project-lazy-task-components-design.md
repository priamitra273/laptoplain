# Design: Self-contained, Type-aligned Task Components for `project-lazy`

**Date:** 2026-06-20
**Branch:** `refactor/project-lazy-task-components` (worktree `.worktree/project-lazy-task`, merges back to `refactor/project-detail`)

## Problem

`resources/js/pages/project-lazy/List.vue` reuses the classic `TaskTable`
(`@/pages/project/task/Table.vue`) and `TaskForm` (`@/pages/project/task/Form.vue`).
Those components are typed against the *full* `ProjectTaskData` shape, but the
lazy List tab is fed the *slim* `ListTask` payload from `ProjectLazyService`.
This produces real type mismatches (see Baseline) and leaves a large amount of
table/drag-drop/form logic inlined inside the shared components.

## Goal

1. Make `project-lazy` own a **self-contained** copy of `TaskTable`,
   `TaskFormDrawer`, `TaskForm`, and all their child components.
2. **Align types** to the actual backend payloads (`ListTask` for the table,
   `App.Data.Task.ProjectTaskData` for the on-demand edit fetch).
3. **Extract heavy logic into colocated composables.**
4. Leave the original `project/task/*` components **untouched**.

## Non-goals

- No backend changes.
- No changes to the Kanban / Backlog / Team / Report tabs.
- No refactor of generic shared leaf components (`Icon`, `Label`,
  `TaskActivityLogModal`) — these stay referenced from `@/components`.
- No new runtime dependencies (`vitest` is already installed).

## Target structure (`resources/js/pages/project-lazy/task/`)

```
TaskTable.vue              ← from project/task/Table.vue (logic extracted)
TaskFormDrawer.vue         ← improve existing file (logic extracted)
TaskForm.vue               ← from project/task/Form.vue (logic extracted)
partials/
  TaskTableFilters.vue     ← copy, retype to lazy options
  TaskTableToolbar.vue     ← copy, fix PageProps generic
  form-ui/                 ← copy all 10, retype
    InputAttachment.vue  InputDateRange.vue  InputDescription.vue
    InputTags.vue        InputTitle.vue      SelectArchivedProgress.vue
    SelectCategory.vue   SelectMembers.vue   SelectParentTask.vue
    SelectTypeStatusPriority.vue
composables/
  useTaskTree.ts          formatTasks, sorted formattedTasks, findTaskById, isDescendant
  useTaskSelection.ts     selectedKey, isAllSelected, hasSelectedTasks, select/clear/toggle, selectedIds
  useTaskActions.ts       deleteLoading, remove(task), removeSelected(ids)
  useTaskDragDrop.ts      full HTML5 + pointer-hold drag, auto-expand, move validation, updateTaskParent
  useTaskCategoryStyle.ts getCategoryIcon, getCategoryColor
  useTaskFormDrawer.ts    visible/loading/task/parentTree/parentId, header, buildTree, fetch, openCreate/openEdit/close
  useTaskForm.ts          useForm state, member/tag diffing, category defaults, status change, submit
```

**Kept as global references (not copied):** `@/components/TaskActivityLogModal.vue`,
`@/components/Icon.vue`, `@/components/Label.vue`,
`@/composables/useProjectPermissions`, `@/composables/useSeverityColor`.

## Type strategy (additions to `project-lazy/index.d.ts`)

- `TaskTable` props consume `ListTask[]` + the existing lazy option types
  (`TaskStatusOption`, `TaskTypeOption`, `TaskCategoryOption`). The formatted row
  type drops `priority` and `created_by` (absent from `ListTask`, unused in the
  table template).
- The drawer's on-demand edit payload is the full task → alias
  `export type TaskFormPayload = App.Data.Task.ProjectTaskData;`
- New lazy form types mirroring the classic ones but with slim option types:
  `LazyTaskFormProps`, `LazyTaskFormData`, `LazyTaskTableProps`,
  `LazyTaskTableEmits`, `LazyTaskTableFilter`, `LazyTaskFormatted` /
  `LazyTaskFormattedData`, `LazyMember = { user: SlimUser }`.
- form-ui partials retype: `TaskCategory`→`TaskCategoryOption`,
  `User`→`SlimUser`, `ProjectTask`→`ParentTaskNode` (the parent-tree node shape).

## Behavior preservation

The new components must be **behaviorally identical** to the originals:
selection, bulk delete, the full drag-and-drop machinery (drag handle arming via
hold, root drop zone, auto-expand on hover, descendant-guard), filters, activity
log modal, and the create/edit/subtask form flow (members, tags, attachments,
parent picker, archived/progress, status→progress sync, date rules).

## Wiring

`List.vue` imports the new local `TaskTable` (replacing
`@/pages/project/task/Table.vue`). `TaskFormDrawer` is already local. Props stay
the same; only types tighten.

## Testing & verification

- **Unit (vitest):** add a minimal `vitest.config.ts` + `test` script. Test the
  pure logic extracted into composables: `formatTasks` (shape, level, ordering),
  `buildTree` (flat→tree), `isDescendant` (guard), `getCategoryIcon` /
  `getCategoryColor` (fallbacks). These need no component mounting.
- **Type-check:** `vue-tsc --noEmit` must add **0 new errors** vs the 35-error
  baseline, and should *remove* the 4 mismatches in `List.vue`,
  `TaskFormDrawer.vue`, `TaskTableToolbar.vue` (target ≈ 31).
- **Build:** `vite build` succeeds.

## Baseline (recorded 2026-06-20, commit 2eafb19)

35 pre-existing `vue-tsc` errors. Relevant ones this work should fix:

| File:line | Error |
|---|---|
| `project-lazy/List.vue:28` | `ListTask[]` not assignable to `ProjectTaskData[]` |
| `project-lazy/List.vue:34` | edit handler `ListTask` vs `ProjectTaskData` |
| `project-lazy/task/TaskFormDrawer.vue:139` | `{ user: SlimUser }[]` vs `ProjectMember[]` |
| `project/task/partials/TaskTableToolbar.vue:18` | `ProjectDetailProps` not a `PageProps` (in copied file) |

(`project-lazy/Kanban.vue:30` is the same family but out of scope.)

## Risks

- **Logic divergence:** copies can drift from originals over time. Accepted —
  the lazy tab intentionally diverges (slim payload). Composables reduce the
  inlined surface.
- **Drag-drop regressions:** highest-risk extraction. Mitigate by keeping the
  composable a faithful move of existing code (same refs, same handlers) and a
  manual smoke check after build.

## Workflow

All work in the `.worktree/project-lazy-task` worktree, then merge
`refactor/project-lazy-task-components` → `refactor/project-detail`.
