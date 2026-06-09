# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Released]

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
- Version `1.0.0` covers changes after `d91fb0` through `ca033b8`.
- Latest commit incorporated: `ca033b8` (feat: conditionally require start and due dates based on task status).
- Future releases should increment:
    - `PATCH` for backward-compatible bug fixes,
    - `MINOR` for backward-compatible features,
    - `MAJOR` for breaking changes.
