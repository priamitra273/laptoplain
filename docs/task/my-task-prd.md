# My Task — Product Requirements Document

Status: **Draft** — spec for rebuilding `task.index` in the Nuxt UI stack, reverse-engineered from the existing PrimeVue reference implementation at `resources/js/pages_v1/project/task/Index.vue`.

## 1. Overview

"My Task" is a personal, cross-project work list: every task the current user is responsible for, in one place, without having to open each project individually. It is reachable from the sidebar ("My Task") and from a "View all" link on the Dashboard.

The backend for this page already exists and is fully functional (`TaskController@index`, `TaskService::indexProps`, `TaskRepository::assignedTasksQuery`). `Inertia::render('project/task/Index', ...)` already points at the new-stack path — only the frontend needs to be built, at `resources/js/pages/project/task/Index.vue`.

## 2. Problem

Without this page, a user has to check every project's board individually to know what's on their plate. There's also no single place to see an at-a-glance count of how much work is in each status across all projects.

## 3. Who sees this page

- Any authenticated, verified user with the `task.read` permission (super-admins always pass).
- If the permission check fails, the route returns **404**, not 403 — a user without access simply doesn't see the page exists.
- No further row-level filtering beyond the "mine" scope described below — every task the query returns is visible to the viewing user by definition.

## 4. Scope: what counts as "my task"

A task belongs on this page if **all** of the following hold:

- The user is the task's creator **OR** an assigned user (`task.users` pivot) — combined with **OR**, not AND. There is no toggle to split "created by me" from "assigned to me"; the list is always the union.
- The task is a **leaf task** — it has no children. Parent tasks with subtasks are not shown as rows here (their progress rolls up and is visible on the task detail / project views instead).
- The task's category is not **Epic**.
- The task's project still exists (soft-deleted/missing projects are excluded).

## 5. User stories

1. As a user, I want to see every task assigned to or created by me, across all projects, in one list — so I don't have to check each project separately.
2. As a user, I want to switch between a kanban board and a flat list — so I can pick whichever view fits what I'm doing right now.
3. As a user, I want to filter by project, status, priority, type, and search by title — so I can narrow a long list down to what matters right now.
4. As a user, I want to drag a card to a different status column to update it, without opening the task — so quick status changes stay quick.
5. As a user, if I drag a task into a status where a due date matters and it doesn't have one yet, I want to be asked for one before the move commits — so tasks don't silently lose their due date.
6. As a user, I want to see a live count per status — so I get an at-a-glance read on my workload distribution.
7. As a Product Owner, I should only be able to move a task to To Do, Completed, or Blocked from this quick control — anything else requires opening the task, matching the extra scrutiny those transitions need.

## 6. Functional requirements

### 6.1 Header

- Title: **"My Task"**. Subtitle: **"Manage and track your work items — {user's name}"**.
- View toggle, top-right: **Board** (default) / **List**. Switching re-requests data from the server (not a client-side-only toggle) via an Inertia partial reload (`only: ['tasks','board','summary','filters','view']`).

### 6.2 Toolbar

- Search input, placeholder "Search assignments...", debounced ~300ms, matches task **title** only.
- Total count label to the right of search: "N assignments" (singular "assignment" when N = 1). In list mode this is the paginator total; in board mode it's the sum of every column's true total (not just the loaded page).
- Filter row, all filters independently clearable:
  - **Project** — type-ahead against the list of projects the user can see.
  - **Status** — dropdown of all task statuses, rendered as a colored tag.
  - **Priority** — dropdown of all task priorities, colored tag.
  - **Type** — dropdown of all task types, colored tag.
  - **Clear** — appears only when search or any filter is active; resets everything in one action.
- Every filter/search change re-issues the server request (Inertia partial reload, `preserveState`/`preserveScroll`). A loading skeleton (board- or list-shaped, matching the active view) shows while a request is in flight.

### 6.3 Status summary chips

- Row of chips, one per status that currently has count > 0: colored dot (status severity) + status name + count.
- Computed against the **same filters as the main query, except the status filter itself** — so switching which status you're filtering to never makes other chips disappear. This is what lets the chips double as an alternate way to jump between statuses.

### 6.4 List view

Server-paginated table, one row per task, whole row clickable → task detail (`task.show`).

| Column | Content |
|---|---|
| Task | Title (link to task detail) with the project title underneath (link to that project's kanban tab) |
| Status | Colored tag |
| Priority | Icon + name, icon and color driven by priority severity |
| Type | Colored tag |
| Due date | Formatted date; appended with "· Overdue" (visually emphasized), "· Today", or "· Tomorrow" where applicable; `—` if unset |

Pagination is server-side; page size is adjustable.

### 6.5 Board view

One column per task status. Each column header shows a colored dot, the status name, and the column's true task count (not just what's loaded).

**Card contents:** type tag, priority tag, an "Overdue" badge when applicable, title (2-line clamp), project link, due date (color-coded: overdue = strongest emphasis, due within 3 days = warning, otherwise neutral; shown with a relative label like "in 2 days"), a subtask progress bar (only if the task has subtasks — this page's own rows are leaf tasks, but a card can still show *its own* logged progress if applicable per the source data), sequence number, and up to 3 assignee avatars with a "+N" overflow indicator. Clicking a card (or its hover-revealed open icon) goes to task detail.

**Column pagination:** each column loads its first 10 tasks (ordered by soonest due date, nulls last) up front; a **"Load more (N)"** button per column fetches the next 10 when more remain.

**Drag-and-drop status change:**
- Dragging a card into a different column is a status change, submitted immediately as `PUT task/{task}/status`.
- **Due-date gate:** if the destination status is anything other than **To Do** or **Blocked**, and the task has no due date yet, the move is held: a required, non-dismissable "set a due date" prompt (minimum = today) must be completed before the change commits. Cancelling reverts the card to its original column.
- On success: the moved card's column counts adjust locally (−1 from source, +1 to destination), and the status summary chips are refetched so their counts stay accurate.
- On failure: the card snaps back to its original column and an error message is shown.
- **Role restriction:** a user with a Product Owner role can only drop into To Do, Completed, or Blocked from this control; other destinations are rejected server-side.
- A status move to **Completed** also marks the task's completion timestamp and sets its progress to 100% (when it has no subtasks); moving away from Completed clears the completion timestamp and resets progress to the new status's configured score. This cascades to parent-task and project progress recalculation — existing backend behavior, not something the frontend needs to compute itself.

### 6.6 Empty and loading states

- **Loading:** skeleton matching the active view's shape (kanban columns or list rows).
- **Empty, filters active:** "No matching tasks" / "Try adjusting your search or filters." + a "Clear filters" action.
- **Empty, no filters:** "No tasks yet" / "Tasks assigned to you will appear here."

## 7. Out of scope

These exist elsewhere in the app (project-scoped task views, task detail page) but are **not** part of this page, matching the reference implementation's actual scope:

- Bulk selection / bulk actions.
- Creating or deleting tasks from this page.
- Inline editing of priority, type, or due date (only status is editable here, via drag-and-drop).
- A separate "created by me" vs. "assigned to me" toggle — these are always merged.
- Sorting controls (list is always newest-first by creation; board columns are always soonest-due-first).

## 8. Non-functional / implementation notes

- Everything data-facing is server-driven — there is no client-side task cache to keep in sync; every filter/search/view change is a fresh (partial) Inertia request.
- IDs on the wire are Sqids-encoded strings, consistent with the rest of the app.
- Drag-and-drop: `vue-draggable-plus` is already a project dependency and is the library used for this exact interaction elsewhere in the codebase.
- Severity → color mapping should use this codebase's established `severityColor()` helper (`@/lib/utils`), not the old PrimeVue-specific icon/color helpers the reference implementation used.
- Reuse Nuxt UI primitives already established in this app's rebuilt pages (`UCard`, `UBadge`, `UAvatar`, `USelectMenu`, `UTable` for list mode) rather than introducing new UI patterns.

## 9. Success criteria

- User-facing behavior matches this document (functional parity with the old page).
- Built entirely with Nuxt UI — no PrimeVue.
- Lives at `resources/js/pages/project/task/Index.vue`, matching the controller's existing `Inertia::render()` target so no backend path change is needed.

## 10. Open questions

- Should Board remain the default view, or should List be the default in the rebuild? (Old page defaults to Board.)
- Confirm final Nuxt UI component choices for the project type-ahead filter and the per-column "load more" pattern during implementation — no established precedent for either yet in this codebase's rebuilt pages.
