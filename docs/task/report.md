# Task Report

Laporan task lintas project dengan filter lanjutan dan export CSV.

## Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant TaskReportService
    participant DB

    Note over User,DB: VIEW REPORT
    User->>Browser: Buka /reports/tasks
    Browser->>Backend: GET /reports/tasks?filters...
    Backend->>TaskReportService: getIndexData(filters, perPage)
    TaskReportService->>DB: Query tasks with filters
    Backend->>Browser: Render TaskReport page
    Browser-->>User: Tabel + filter + pagination

    Note over User,DB: EXPORT CSV (tombol UI saat ini di-comment; route & logic tetap ada)
    User->>Browser: Klik "Export" (exportReport → window.open)
    Browser->>Backend: GET /reports/tasks/export?filters...
    Backend->>TaskReportService: generateExportCallback(filters)
    TaskReportService->>DB: Stream query results
    Backend->>Browser: Streamed CSV response
    Browser-->>User: Download CSV file
```

## Filters

- Assignee (`names`)
- Project Status (`project_statuses`) — hanya dipakai di view/index, TIDAK diteruskan ke export
- Task Status (`statuses`)
- Priority (`priorities`)
- Type (`types`)
- Start Date Range (`start_date_from`, `start_date_to`)
- Due Date Range (`due_date_from`, `due_date_to`)
- Search text (`search`)

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/reports/tasks` | `TaskReportController@index` |
| GET | `/reports/tasks/export` | `TaskReportController@export` |

## Frontend Pages

- `pages/project/task/TaskReport.vue` — Halaman report + filter
