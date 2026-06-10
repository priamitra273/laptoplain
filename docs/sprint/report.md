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
    SprintReportService->>DB: Raw SQL CTE (generate_series start_date..end_date)
    SprintReportService->>SprintReportService: Hanya hari kerja (kecuali Sabtu/Minggu, ISODOW 6,7)
    Note over SprintReportService: total_plan = count task dgn plan_end_date > tanggal<br/>(plan_end_date = COALESCE(due_date, sprint.end_date))<br/>total_actual = count task dgn completed_at IS NULL<br/>atau completed_at > tanggal

    Backend->>Browser: JSON {success: true, data: BurndownChartData[]} (tanpa Sqids encode)
    Browser->>Browser: Render Highcharts line chart (plan vs actual)
```

**BurndownChartData per data point:**
- `date` — tanggal
- `label` — `DAY-N` (urut hari kerja)
- `total_plan` — sisa task rencana
- `total_actual` — sisa task aktual (belum selesai)

---

## 3. Status Report

```mermaid
sequenceDiagram
    User->>Browser: GET /project/{project}/sprints/{sprint}/status-report
    Browser->>Backend: GET

    Backend->>Backend: Model binding: Project + ProjectSprint
    Backend->>Backend: abort_if(project->id !== sprint->project_id, 404)

    Backend->>SprintReportService: getSprintStatusReport(sprint)
    SprintReportService->>DB: completed_tasks = task sprint dgn status Completed/Finished/Done
    SprintReportService->>DB: incomplete_tasks = task yang pernah dipindahkan keluar<br/>(dari activity_log log_name 'move_incomplete_%', properti 'detached')

    Backend->>Browser: JSON {success: true, data: SprintStatusReportData} (Sqids encode)
    Note over Browser: SprintStatusReportData: completed_tasks[] + incomplete_tasks[]<br/>(TaskData: status, assignee/users, due date, dll)
    Browser->>Browser: Render DataTable SprintTaskTableReport
```

---

## Routes

| Method | URI | Controller | Validasi |
|--------|-----|------------|----------|
| GET | `/project/{project}/sprints/all` | `Api\SprintReportController@index` | Project model binding |
| GET | `/project/{project}/sprints/{projectSprint}/burndown` | `Api\SprintReportController@burndown` | abort_if project->id !== projectSprint->project_id |
| GET | `/project/{project}/sprints/{projectSprint}/status-report` | `Api\SprintReportController@statusReport` | abort_if project->id !== projectSprint->project_id |

## Frontend Components

| File | Purpose |
|------|---------|
| `pages/project/partials/ProjectReportTab.vue` | Tab Report: sprint selector + burndown + status report |
| `pages/project/partials/SprintBurndown.vue` | Highcharts line chart (ideal vs actual) |
| `pages/project/partials/SprintTaskTableReport.vue` | DataTable completed + incomplete sprint tasks |
