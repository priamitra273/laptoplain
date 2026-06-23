# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Released]

## [1.2.1] - 2026-06-23

### Added

- Added the `predis/predis` dependency for Redis client support. (b7538eb)

### Fixed

- Standardized project routing and redirections to target the correct project sub-views (`project.show.team`, `project.show.list`, `project.show.kanban`) instead of the generic `project.show`, with minor code-style cleanup (spacing, braces, trailing commas in error arrays). (91cc5eb, 695eab8)
- Corrected formatting in the GitLab CI configuration for build scripts. (44ab72b)

## [1.2.0] - 2026-06-23

### Added

- Client-side ("lazy") task flow on the project detail page: the List and Kanban tabs keep local task state, submit task create/update via axios (with a multipart `taskFormData` serializer), emit a saved payload, and recompute progress live in the browser for ancestors and the project header through `useLocalTaskTree` and a new `RecalculateProgressAction`. (7c4bb3c, 838f52e, 2f614f7, 9662565, 570b0cd, ad81d24, e591a5f, 1e9c7af, 85062cd, 753b8b3)
- Drag-to-reorder for the task list: a native drag grip in the selection column with zone-based reorder and re-parent, DataTable-style full-row dropzone with insertion line / nest highlight, optimistic `moveNode`, a `computeMoveTarget` helper, and a backend move endpoint that normalizes sequence numbers. (a672463, 9719187, c1a6d1e, 97d696f, 5092fcb, 54b28ca)
- Bulk delete for tasks via `TaskBulkDestroy`. (4fcfcad, 49218a9)
- Project Report tab now renders the `ProjectReportTab` component instead of a placeholder. (d9e2269)
- `sequence_number` added to task list item data (nullable) for ordering, plus a project option data type and assigned task data structure. (00d8512, 6e9e54c, 2e9f26b)
- Test coverage for the lazy task update endpoint and progress recalculation. (b05cb41, 373bd3f)

### Changed

- Modernized the "My Task" layout for a cleaner SaaS feel. (1d9318c)
- Reorganized project detail tabs into per-domain directories and made the Kanban tab self-contained with slim backend types. (92d1c45, 3161b6b, 3150fbe, 475107a)
- Deferred the task tree and assignable users on the project detail page using Inertia deferred props. (7c4bb3c)
- Simplified `TaskTable` access-control logic, improved its toolbar, and updated the session-storage key format. (c193a09, 3ef2f1a)
- Recalculate project progress in `UpdateTaskAction`/`TaskService` and on lazy re-parent (both old and new parent). (8b48834, a516856)
- `SprintRepository` now includes the user email in the task user relationship. (b69513a, 48a631c)
- Simplified the `ProjectObserver` updated method to always forget its cache. (86db849)
- Ordered the task tree by `sequence_number` (nulls last) so reorder persists. (19f623d, b4b4cdb)

### Fixed

- Improved error feedback for task field updates. (548df64)
- Prevented setting a null `task_category_id` when no category is provided. (76af967)
- Navigate to the Kanban view on dashboard project-row click and the project breadcrumb. (ff01778, f4772ae)
- Wrapped assignee chips in a flex container for proper layout. (a26a574)
- Deduped task-form close handling and improved loading-state management in `useTaskFormDrawer`. (97e97c3, e076673)

## [1.1.2] - 2026-06-12

### Changed

- Ordered recursive subtask queries by `sequence_number` (then `id`) in the `Task` model and `ProjectRepository` so child tasks display in their intended order. (dae2bc7)
- Updated feature documentation under `docs/` (auth, comments, dashboard, master data, menu, notifications, projects, roles, settings, sprints, tasks, teams) to reflect current validation rules, Sqids encoding, notification flow, and error handling. (d9112c4)

### Fixed

- Prevented task form fields from being disabled during task creation by checking for an existing task before applying permission-based field validation. (c2361f1)

## [1.1.1] - 2026-06-10

### Fixed

- Included the task's current status in `statusOption` so it is always visible regardless of user permissions. (8b0f72a)
- Corrected date range field disabling logic to use `end_date` instead of `due_date` in task form. (8b0f72a)

## [1.1.0] - 2026-06-10

### Added

- Due date requirement enforcement when updating task status, with dialog prompts in Kanban board, task detail, and task details views. (60f24cc, 4c4c45d)

### Changed

- Replaced Instrument Sans with Roboto as the primary application font, including variable and static font files, stylesheet definitions, and Tailwind configuration. (f2f16cf)
- Enhanced date validation logic in `TaskStoreRequest` and `TaskUpdateRequest` with updated form components for date range input. (f73be67)

## [1.0.1] - 2026-06-09

### Added

- Comprehensive feature documentation under `docs/` covering authentication, comments, dashboard, master data, menu, notifications, projects, roles, settings, sprints, tasks, teams, and users. (ace4cef)

### Changed

- Task statuses in `getTaskStatuses` are now ordered by `score` for consistent display in the UI. (0a6f2ef)

## [1.0.0] - 2026-06-09

### Added

- Sprint and backlog management, including task categories, story points, sprint lifecycle actions, task relocation on completion, and default sprint naming. (b7e2041, 165f2cb, 581e4f1)
- Sprint reporting with burndown charts, task status reports, and a dedicated project report tab. (86468c9, 8dea435)
- Redesigned task management interfaces for backlog, Kanban, task details, forms, epics, priority selection, and quick-add workflows. (9167776, 6c846b3, 174b706, 6d992b9)
- Threaded task comments with replies, reactions, avatars, and reusable comment UI components. (538c4af, 3466332, 8748f98)
- Configurable project-role permissions, including task status permissions and project-level permission helpers. (16e6ab9, b8564c3)
- Task attachment upload support with media validation and fallback to the original media URL. (6f01bfa, 04a21e3)
- Session-based persistence for project tabs, project tables, and task table filters. (8b2e3e6, 2d15bd0)
- Task and sprint activity logging, including task movement history. (dd71ab0, 7a28955)
- Generated Laravel Data and TypeScript data structures for projects, tasks, sprints, reports, comments, and workload data. (03f5069, bd01cef, 8eaed75)
- `TaskStatusEnum` and conditional start-date and due-date validation based on task status. (f0a5551, ca033b8)

### Changed

- Task creation, update, detail, deletion, reporting, workload, project, and sprint logic was reorganized into actions, services, repositories, events, listeners, observers, and DTOs. (157dd97, 895358f, 534651f, 08a8663, 226bc54)
- Task and project routes now use model binding and standardized route parameters instead of manual SQID lookups. (218650e, 445d3a9, c6210a0)
- Task reports now support pagination, project-status filters, normalized filter handling, and consistent TypeScript pagination types. (fe9585c, 5ff4a86, 9290ec8)
- Task forms were split into reusable field components with server-side date validation errors displayed in the date-range input. (6d992b9, 402a127, dc3ed8e)
- Project detail and task table interfaces were modularized into dedicated components with improved filtering and toolbar behavior. (063242c, d2597a0, 3cbffd2)
- Root tasks in project views are ordered by sequence, and database seeding now runs the sprint-status and task-category seeders. (66721aa, 2b41ad1)

### Fixed

- Prevented project statistics and project detail views from failing when start dates or due dates are null. (9a0bf0a, 0209b15)
- Prevented duplicated or incorrectly grouped Kanban tasks when a status update is cancelled. (83a8e50, e5d055b)
- Restored form submission from drawer footers and corrected task route parameters and parent-progress calculations. (33dd71e, 874cd3f)
- Corrected project visibility queries so permission checks use the current user model. (f74ff40)
- Preserved correct row numbering across paginated data tables. (1cd1246)
- Corrected task-report handling for non-array filters and pagination metadata. (9290ec8)
- Aligned date fields and the range arrow while improving date validation error placement. (47d9e6e, dc3ed8e)
- Prevented epic popover clicks from triggering default navigation behavior. (861f469)

## [Unreleased]

## [0.9.0] - 2026-03-17

### Added

- Project status filtering in TaskReport (request validation, controller, and Vue component updates). (d91fb08)
- Restrict task status updates for product owners; update status options in task forms and details. (d25806d)
- Navigation enhancements in task detail and kanban views (back navigation and query parameter handling). (24a33bb)
- Global search and status/type filters for task table. (6bb87e6)

### Changed

- Changelog updated for v0.8.0. (822e449)

### Fixed

- No notable bug fixes in this release.

## [0.8.0] - 2026-03-10

### Added

- Change AppMenuProfile dummy data to real data. (3fb68ca)
- Dashboard: add "View all tasks" and "View all projects" links; TaskKanban and TaskReport UI improvements (task/project titles, summary column). (fbde24d)
- Comprehensive project task management: table, detail, and form views with drag-and-drop reordering. (10a7303)
- Task form: client-side validation and auto-clear due date on status change. (71bfcbe)
- Workload index: sortable columns and links from user names to task reports. (1675c32)

### Changed

- Simplify date formatting in Table component; remove unused validation rule in ProjectStoreRequest. (f877f71)

### Fixed

- Prevent error when task has no project using null-safe operator in TaskPolicy. (d95e84b)
- Handle errors when opening tasks with deleted projects in TaskPolicy, TaskController, and ProjectController. (17bd4b6)
- Clear `due_date` when `status_id` is set to to-do. (ab9864a)

## [0.7.0] - 2026-03-05

### Added

- `SqidExists` validation rule for model existence checks. (22e60f2)
- Workload index: sortable columns and links from user names to task reports. (1675c32)

### Changed

- `TaskReport` component: filter value types changed from number to string for improved handling. (4f79faa)
- `TaskReportController` refactored to use `TaskReportIndexRequest` for validation and improved filter handling. (6ae6315)

### Fixed

- No notable bug fixes in this release.

## [0.6.0] - 2026-02-25

### Added

- Workload management module with user workload tracking, filtering, and summary statistics.
- Task activity logging system with modal for viewing task history and filtered field changes.
- Enhanced comment functionality with @mention support, user avatars, and rich text editor integration (quill-mention).
- Advanced drag-and-drop support for task table with auto-expand, multi-MIME type handling, and parent task updates.
- Profile picture editing with vue-advanced-cropper integration.
- Task completion tracking with `completed_at` field and enhanced status handling.
- Project filtering enhancements in task management forms.

### Changed

- Renamed "Activity Log" to "History Log" for better clarity in UI components.
- Enhanced validation rules for due dates in project and task requests.
- Improved drag-and-drop state management and event handling in task table.
- Removed hold-to-drag functionality and unnecessary permission double-checking.

### Fixed

- Severity labels and workload status handling in controllers and views.
- Drag-and-drop state reset and event handling issues.
- Unused component imports and unnecessary comments cleanup.
- Mention editor styles and ID synchronization improvements.

## [0.5.0] - 2026-02-24

### Added

- Task Kanban improvements, including status update flow and navigation to task detail.
- Task report module with filtering/export support.
- Advanced task filtering and sorting (status, priority, dates, progress, overdue/completed fields).
- Project numbering (`project_no`) and automatic generation.
- Parent task selection with `TreeSelect`, plus stronger update validation via `TaskUpdateRequest`.
- Activity logging traits for project, task, and user models.

### Changed

- Permission model and middleware were expanded for route-level and component-level access checks.
- Project detail UI became more interactive (editable fields, click-outside handling, better dialog behavior).
- Notification architecture improved with connection management and middleware sharing.
- CI and dependency setup updated (including `vue-draggable-plus` and pipeline refinements).

### Fixed

- Task due date validation and default board view consistency.
- Task status ordering in task index.
- Multiple UI/accessibility issues (title attributes, dark mode, minor layout/formatting fixes).

## [0.4.0] - 2026-01-30

### Added

- Real-time notification flow with Redis integration and streaming support.
- Gantt chart support for project timeline visualization.
- Rich text editor support for task comments.
- Avatar upload/deletion flow and profile enhancements.
- Improved 404/error handling pages and flash toast messaging.

### Changed

- Project member and owner access control became stricter.
- Dashboard/project/task data retrieval and statistics logic were refactored.
- Login layout and branding assets were improved.

### Fixed

- Notification read logic and payload encoding for task references.
- Seeder/form issues in route naming and minor UI class cleanup.

## [0.3.0] - 2025-12-24

### Added

- Tag management in tasks and richer task search/filter capabilities.
- Breadcrumb context for task detail and clickable rows in dashboard/task tables.
- Conditional validation improvements for task create/edit flows.
- Notification events for key task and membership actions.

### Changed

- Progress handling and child-task averaging were improved.
- Membership-based visibility and task action rules were refined.
- Project/task forms and tables were standardized with PrimeVue toast/confirm patterns.

### Fixed

- Variable and table layout issues in task/member UI components.

## [0.2.0] - 2025-11-28

### Added

- Core project/task domain: subtasks, hierarchy, assignment, task detail page, comments, and reactions.
- Master data for project role/priority/status and task type/status.
- Role-aware menu setup and supporting seeders.
- SQIDS-based encoded ID support across related resources.
- Severity standardization and improved task table usability (search/pagination).

### Changed

- Project member management UI and forms were heavily refactored.
- Task form/task components received major restructuring for progress and recursive behavior.

### Fixed

- Role ID decoding, route naming, assignment emits, and assorted task/menu issues.

## [0.1.0] - 2025-10-31

### Added

- Initial project scaffold and first domain migrations/models.
- Foundation for project, task, notification, comment, and tag data structures.
- Early project status/priority resources and base routes.

### Fixed

- Early dashboard UUID-related defects and minor model/style issues.

---

Notes:

- Versions above are organized semantically from historical milestones in `git log`.
- Version `1.1.1` covers changes after `f2f16cf` through `8b0f72a`.
- Version `1.1.2` covers changes after `8b0f72a` through `c2361f1`.
- Version `1.2.0` covers changes after `c2361f1` through `231fe51`.
- Version `1.2.1` covers changes after `8c0e08c` (the v1.2.0 changelog commit) through `695eab8`.
- Latest commit incorporated: `695eab8` (fix: update route redirections to project sub-views).
- Future releases should increment:
    - `PATCH` for backward-compatible bug fixes,
    - `MINOR` for backward-compatible features,
    - `MAJOR` for breaking changes.
