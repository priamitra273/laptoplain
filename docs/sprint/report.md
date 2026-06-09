# Sprint Report

Laporan sprint mencakup burndown chart dan status report untuk sprint yang sudah berjalan.

---

## 1. All Sprints

```mermaid
sequenceDiagram
    User->>Browser: GET /project/{project}/sprints/all
    Browser->>Backend: GET /project/{project}/sprints/all
    Backend->>Backend: Model binding: Project $project

    Backend->>DB: $project->sprints()->with('status')->oldest()->get()
    Note over DB: Eager-load ms_sprint_status relation, ordered by oldest first

    Backend->>Backend: SprintData::collect(sprints) — DTO collection
    Backend->>Backend: Sqids::rec_encode_ids_in_list(dto)
    Backend->>Browser: JSON {success: true, data: SprintData[]}
    Browser-->>User: Sprint selector dropdown
```

---

## 2. Burndown Chart

```mermaid
sequenceDiagram
    User->>Browser: Pilih sprint -> klik tab Report
    Browser->>Backend: GET /project/{project}/sprints/{sprint}/burndown

    Backend->>Backend: Model binding: Project + ProjectSprint
    Backend->>Backend: abort_if(project->id !== sprint->project_id, 404)
    Note over Backend: Validasi sprint belongs to project

    Backend->>SprintReportService: getBurndownChartData(sprint)
    SprintReportService->>DB: Query sprint tasks + daily snapshots
    SprintReportService->>SprintReportService: Calculate ideal line vs actual remaining
    Note over SprintReportService: Ideal = linear dari total task di awal sprint<br/>Actual = count task yang belum completed per hari

    Backend->>Browser: JSON {success: true, data: BurndownChartData[]}
    Browser->>Browser: Render Highcharts line chart (planned vs actual)
```

**BurndownChartData per data point:**
- `date` — tanggal
- `ideal` — remaining ideal (linear)
- `actual` — remaining aktual

---

## 3. Status Report

```mermaid
sequenceDiagram
    User->>Browser: GET /project/{project}/sprints/{sprint}/status-report
    Browser->>Backend: GET

    Backend->>Backend: Model binding: Project + ProjectSprint
    Backend->>Backend: abort_if(project->id !== sprint->project_id, 404)

    Backend->>SprintReportService: getSprintStatusReport(sprint)
    SprintReportService->>DB: Query task status dalam sprint
    Note over DB: Pisahkan completed vs incomplete tasks

    Backend->>Browser: JSON {success: true, data: SprintStatusReportData}
    Note over Browser: SprintStatusReportData: daftar task completed + incomplete<br/>dengan status, assignee, due date
    Browser->>Browser: Render DataTable SprintTaskTableReport
```

---

## Routes

| Method | URI | Controller | Validasi |
|--------|-----|------------|----------|
| GET | `/project/{project}/sprints/all` | `SprintReportController@index` | Project model binding |
| GET | `/project/{project}/sprints/{sprint}/burndown` | `SprintReportController@burndown` | abort_if project !== sprint.project_id |
| GET | `/project/{project}/sprints/{sprint}/status-report` | `SprintReportController@statusReport` | abort_if project !== sprint.project_id |

## Frontend Components

| File | Purpose |
|------|---------|
| `pages/project/partials/ProjectReportTab.vue` | Tab Report: sprint selector + burndown + status report |
| `pages/project/partials/SprintBurndown.vue` | Highcharts line chart (ideal vs actual) |
| `pages/project/partials/SprintTaskTableReport.vue` | DataTable completed + incomplete sprint tasks |
