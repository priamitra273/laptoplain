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

    Note over User,DB: EXPORT CSV
    User->>Browser: Klik "Export"
    Browser->>Backend: GET /reports/tasks/export?filters...
    Backend->>TaskReportService: generateExportCallback(filters)
    TaskReportService->>DB: Stream query results
    Backend->>Browser: Streamed CSV response
    Browser-->>User: Download CSV file
```

## Filters

- Assignee
- Project Status
- Task Status
- Priority
- Type
- Start Date Range
- Due Date Range
- Search text

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/reports/tasks` | `TaskReportController@index` |
| GET | `/reports/tasks/export` | `TaskReportController@export` |

## Frontend Pages

- `pages/project/task/TaskReport.vue` — Halaman report + filter
