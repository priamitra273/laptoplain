# Frontend Type Migration: project/Detail Task chain → `App.Data.Task.ProjectTaskData`

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Replace the hand-written `Task` interface with the generated `App.Data.Task.ProjectTaskData` across the **project/Detail data chain only** (tasks tree, backlog, sprint tasks, and their rendering components), so the frontend types match the backend payload. Keep the hand-written `Task` for non-project/Detail contexts (task Detail page, Dashboard, task Index, task Form's edited task).

**Architecture:** First repair the broken type-checker (`tsconfig` has a `vue/tsx` typo that makes `vue-tsc` abort before checking ANY code). Capture the resulting **pre-existing error baseline** (31 errors, mostly unrelated to tasks). Then retype the project/Detail source props + their downstream component prop types to `ProjectTaskData`, fixing code-level mismatches (notably `created_by: string|null → number|null`). **Success = `vue-tsc` introduces NO NEW errors beyond the documented baseline** (the 31 pre-existing, unrelated errors are explicitly out of scope and left alone).

**Tech Stack:** Vue 3.5 `<script setup lang="ts">`, vue-tsc, Inertia, generated `App.Data.*` types (Spatie TypeScript Transformer), ESLint, Vite.

---

## ⚠️ Critical context (verified empirically)

- **`vue-tsc` is currently broken project-wide.** `tsconfig.json:20` has `"types": ["vite/client", "vue/tsx", "./resources/js/types"]`. Vue 3.5 ships `vue/jsx` (and `vue/jsx-runtime`), **not** `vue/tsx`. The missing type lib raises `TS2688` and `vue-tsc` aborts WITHOUT type-checking code (a deliberately-injected `const x: number = 'str'` was NOT caught). Removing `"vue/tsx"` restores checking (the injected error IS then caught).
- **After the fix, there are 31 pre-existing errors** in ~15 files, MOSTLY unrelated to tasks (`ChartProgress.vue` ×7, `ProjectVerifyImport.vue` ×4, `ssr.ts` ×3, `UserInfo.vue` ×3, `Welcome.vue` ×2, sidebar/header, master-data tables, etc.). These are OUT OF SCOPE — do not fix them. They form the **baseline**.
- **Migration cascade (measured):** retyping just the 3 source props introduces ~10 new errors in `Detail.vue` (lines 70, 242, 258, 297, 345), `task/Backlog.vue` (~4), `task/partials/SprintSection.vue` (1) — all assignability failures (`ProjectTaskData` vs `Task`/`TaskNode`/`Sprint`). Fixing those by retyping downstream component prop types will surface further errors; **iterate `vue-tsc` until the diff vs baseline is empty.**
- **`Detail.vue` must be committed by the user BEFORE Task 2** (it had uncommitted WIP). Task 2 edits it.
- **Keep `Task` for:** `TaskDetailProps.task`, `Dashboard.vue`, `task/Index.vue`, `TaskFormProps.task` (the single edited task). Only the COLLECTION props that carry project/Detail payload migrate.

## Baseline capture command (used as the success oracle)
```bash
# with vue/tsx removed from tsconfig:
npx vue-tsc --noEmit 2>&1 | grep "error TS" | grep -v "vue/tsx" | sort > /tmp/fe_baseline.txt
wc -l /tmp/fe_baseline.txt   # expect 31
```
Success oracle after each change:
```bash
npx vue-tsc --noEmit 2>&1 | grep "error TS" | grep -v "vue/tsx" | sort > /tmp/fe_now.txt
comm -13 /tmp/fe_baseline.txt /tmp/fe_now.txt   # MUST be empty (no NEW errors)
```

---

## Task 1: Repair `vue-tsc` (tsconfig) + capture baseline

**Files:** Modify `tsconfig.json`

- [ ] **Step 1: Remove the `vue/tsx` types entry**

In `tsconfig.json` line ~20, change:
```json
"types": ["vite/client", "vue/tsx", "./resources/js/types"],
```
to:
```json
"types": ["vite/client", "./resources/js/types"],
```
(Vue 3.5 has no `vue/tsx`; the project has no `.tsx` files needing Vue's JSX globals via this entry. If a later need for JSX types arises, the correct entry is `vue/jsx`.)

- [ ] **Step 2: Verify type-checking now actually runs**

```bash
printf 'export const __probe: number = "x";\n' > resources/js/__probe.ts
npx vue-tsc --noEmit 2>&1 | grep -c "__probe"   # expect 1 (checking works)
rm resources/js/__probe.ts
```
Expected: `1`. If `0`, type-checking still isn't running — stop and investigate.

- [ ] **Step 3: Capture the pre-existing baseline**

```bash
npx vue-tsc --noEmit 2>&1 | grep "error TS" | grep -v "vue/tsx" | sort > /tmp/fe_baseline.txt
wc -l /tmp/fe_baseline.txt
```
Expected: ~31 errors. These are pre-existing and OUT OF SCOPE. Keep `/tmp/fe_baseline.txt` for the rest of the plan.

- [ ] **Step 4: Commit**

```bash
git add tsconfig.json
git commit -m "fix(types): drop invalid vue/tsx types entry so vue-tsc type-checks again"
```

---

## Task 2: Migrate project/Detail chain to `ProjectTaskData`

**Files (known; more may surface via vue-tsc iteration):**
- `resources/js/pages/project/index.d.ts` (type definitions)
- `resources/js/pages/project/Detail.vue` (**must be committed by user first**)
- `resources/js/pages/project/task/Backlog.vue`
- `resources/js/pages/project/task/partials/SprintSection.vue`
- `resources/js/pages/project/task/Table.vue` (the `created_by` mapping + ProjectTaskTableProps)
- plus any further files surfaced by `vue-tsc` iteration (e.g. `Taskrow.vue`, `BacklogSection.vue`, `BacklogTaskRow.vue`, kanban components, `TaskTableToolbar.vue`)

> PREREQUISITE: `git status` must show `resources/js/pages/project/Detail.vue` as clean (committed). If not, STOP and ask the user to commit it.

- [ ] **Step 1: Add a friendly alias in `index.d.ts`**

Near the top of `resources/js/pages/project/index.d.ts` (after existing imports), add:
```ts
export type ProjectTask = App.Data.Task.ProjectTaskData;
```
Use `ProjectTask` as the migration target type below (it reads cleanly and centralizes the mapping).

- [ ] **Step 2: Retype the project/Detail SOURCE + collection props in `index.d.ts`**

Change ONLY these (leave `Task` itself and non-project/Detail props intact):
- `Sprint.tasks?: Task[];` → `tasks?: ProjectTask[];`
- `ProjectDetailProps.tasks: Task[];` → `tasks: ProjectTask[];`
- `ProjectDetailProps.backlog: Task[];` → `backlog: ProjectTask[];`
- `ProjectTaskTableProps.tasks: Task[];` → `tasks: ProjectTask[];`
- `ProjectTaskTableEmits` — change `edit` payload `task: Task` → `task: ProjectTask`.
- `TaskFormatted.original: Task;` → `original: ProjectTask;`
- `TaskFormattedData.created_by: string | null;` → `created_by: number | null;` (matches the new DTO)
- `TaskFormProps.tasks: Task[];` → `tasks: ProjectTask[];` (the candidate-parent list comes from project tasks). Leave `TaskFormProps.task: Task | null` as `ProjectTask | null` ONLY if vue-tsc requires it; otherwise leave as `Task` (the edited task). Decide based on the error oracle.

Do NOT change: `TaskDetailProps.task`, anything in `Dashboard`, `task/Index.vue`'s local types, `TaskUser`/`TaskPivot` (the DTO has no pivot; consumers don't use pivot — verified zero `.pivot` usages).

- [ ] **Step 3: Iterate `vue-tsc` and fix each NEW error by retyping the consuming component**

Run the oracle:
```bash
npx vue-tsc --noEmit 2>&1 | grep "error TS" | grep -v "vue/tsx" | sort > /tmp/fe_now.txt
comm -13 /tmp/fe_baseline.txt /tmp/fe_now.txt
```
For each NEW error (a line printed by `comm -13`), open the file and retype the local `Task` usage to `ProjectTask` (import it from `@/pages/project` or the relative index). Typical fixes:
- In `<script setup>`, change `import type { Task } from '...'` usages that hold project/Detail tasks → `ProjectTask`; change `ref<Task|null>` / function params / computed return types accordingly.
- `Table.vue`: the `formatTasks` mapping at ~line 94 (`created_by: t.created_by ?? null`) — `created_by` is now `number | null`; ensure `TaskFormattedData.created_by` is `number | null` (done in Step 2) and the assignment type-checks. The `TaskNode` type referenced in `Detail.vue(297)` — locate it (likely a kanban/table-local type) and retype its task field to `ProjectTask`.
- `Backlog.vue` / `SprintSection.vue` / `BacklogSection.vue` / `BacklogTaskRow.vue` / kanban (`TaskKanbanCard.vue`, `TaskKanbanDetailPanel.vue`, `TaskKanbanBoard.vue`, `TaskKanban.vue`): retype task props/refs that receive project/Detail tasks.

Repeat run-fix until `comm -13 /tmp/fe_baseline.txt /tmp/fe_now.txt` prints NOTHING (no new errors). Do NOT touch any file whose only errors are in the baseline (pre-existing, out of scope) — verify a file's errors are NEW (in the `comm -13` output) before editing it.

- [ ] **Step 4: Remove `pivot` references if any block compilation**

`TaskUser`/`TaskPivot` remain defined for non-migrated contexts. If a migrated component referenced `users[].pivot` it would now error (the DTO has no pivot) — but a prior scan found ZERO `.pivot` usages, so expect none. If one appears in the oracle, remove that usage.

- [ ] **Step 5: Final verification — no new type errors, build, lint**

```bash
# no NEW type errors vs baseline:
npx vue-tsc --noEmit 2>&1 | grep "error TS" | grep -v "vue/tsx" | sort > /tmp/fe_now.txt
comm -13 /tmp/fe_baseline.txt /tmp/fe_now.txt   # MUST be empty
# build:
npm run build
# lint (only on changed files is fine; project script is eslint --fix):
npm run lint
```
Expected: `comm -13` empty; `npm run build` exits 0; lint clean on changed files. Also confirm no errors were ADDED to the baseline files (the migration shouldn't worsen pre-existing files).

- [ ] **Step 6: Pint not needed (no PHP). Commit**

```bash
git add resources/js/pages/project/index.d.ts resources/js/pages/project/**/*.vue
git status   # confirm ONLY intended frontend files staged
git commit -m "refactor(project): migrate project/Detail task chain to ProjectTaskData type"
```
Stage only the frontend files you actually changed; do not stage unrelated files.

---

## Task 3: Smoke verification

- [ ] **Step 1:** Confirm the generated type is the one consumed: `grep -rn "ProjectTask\b\|ProjectTaskData" resources/js/pages/project/index.d.ts` shows the alias + usages.
- [ ] **Step 2:** `npm run build` green (already in Task 2, re-confirm).
- [ ] **Step 3:** Optional manual smoke: `npm run dev` + open a project Detail page; verify tasks tree, backlog, sprint board render (behavior-preserving — types only; no runtime change expected).

---

## Out of scope (do NOT do here)
- Fixing the 31 pre-existing `vue-tsc` errors unrelated to tasks (charts, ssr, sidebar, master-data tables, ProjectVerifyImport, Welcome, UserInfo). Track separately.
- Migrating `Task` for task Detail page, Dashboard, task Index, or the single edited task in TaskForm.
- Any backend/runtime change.
