# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Placeholder for upcoming features.

### Changed
- Placeholder for upcoming behavior changes.

### Fixed
- Placeholder for upcoming bug fixes.

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
- Latest commit incorporated: `d91fb0` (feat: add project status filtering to task report, including updates to request validation, controller logic, and Vue component for enhanced task management).
- Future releases should increment:
  - `PATCH` for backward-compatible bug fixes,
  - `MINOR` for backward-compatible features,
  - `MAJOR` for breaking changes.
