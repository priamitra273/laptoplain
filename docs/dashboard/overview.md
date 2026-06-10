# Dashboard & Workload

---

## 1. Dashboard

Halaman utama setelah login. Menampilkan statistik ringkasan dari project dan task yang diakses user.

### Flow Backend

```mermaid
sequenceDiagram
    User->>Browser: GET /dashboard
    Browser->>Backend: GET /dashboard (route.permission)

    Backend->>DB: getRecentProjects(userId)
    Note over DB: Project::with(status, priority, projectMembers.user, projectMembers.role)<br/>whereHas(projectMembers where user_id=userId)<br/>latest('id')->limit(5)

    Backend->>DB: getRecentTasks(userId)
    Note over DB: Task::with(project, status, priority, type, users)<br/>where created_by=userId OR whereHas(users id=userId)<br/>latest('id')->limit(5)
    Note over DB: Map: set is_assigned, is_created_by_me flags

    Backend->>DB: getStatistics(userId)
    Note over DB: Project: SELECT COUNT(*) as total, AVG(progress) as avg_progress<br/>whereHas(projectMembers user_id). first()
    Note over DB: Task: SELECT COUNT(*) as total, AVG(progress) as avg_progress<br/>where created_by OR whereHas(users). first()

    Backend->>DB: getTeamMembers(userId)
    Note over DB: Dari semua project user → flatMap projectMembers.user → unique('id') → values

    Backend->>Backend: Sqids::rec_encode_ids_in_list(data)
    Backend->>Browser: Inertia render('Dashboard', {projects, tasks, stats})

    Browser-->>User: 3 stat cards + Latest Projects table + Latest Tasks table
```

### Data Props

```typescript
interface Props {
    projects: Project[]   // latest 5
    tasks: Task[]          // latest 5
    stats: {
        tasks: { total: number, progress: number }
        projects: { total: number, progress: number }
        members: { total: number, list: Member[] }
    }
}
```

### Frontend (Dashboard.vue)

| Section | Komponen UI | Detail |
|---------|------------|--------|
| Tasks Stat Card | Card, ProgressBar, Tag | Total + progress bar + computed status breakdown Tags |
| Projects Stat Card | Card, ProgressBar, Tag | Total + progress bar + computed status breakdown Tags |
| Members Stat Card | Card, AvatarGroup | Count + avatar group (max 5 shown + overflow badge) |
| Latest Projects | DataTable | Title (emoji + link), Status (Tag), Priority (Tag), Due date (fromNow / format) |
| Latest Tasks | DataTable | Title (link), Status (Tag), Priority (Tag) |

**Computed properties:**
- `taskStatusBreakdown` — dari `stats.tasks.byStatus` atau di-compute dari `tasks` array (group by status.name)
- `projectStatusBreakdown` — dari `stats.projects.byStatus` atau di-compute dari `projects` array
- `validMembers` — filter null/undefined dari `stats.members.list`

**Navigation:**
- "View All" → `router.get(route('project.index'))` / `router.get(route('task.index'))`
- Row click → `router.visit(route('task.show', id))` / `router.visit(route('project.show', id))`

---

## 2. Workload Users

Monitoring beban kerja pengguna lintas project.

### Flow Backend

```mermaid
sequenceDiagram
    User->>Browser: GET /workload-users?names=...&workload_statuses=...&search=...
    Browser->>Backend: GET /workload-users (route.permission)

    Backend->>Backend: WorkloadFiltersData::fromRequest(request)
    Note over Backend: names, workload_statuses, search, project_id

    Backend->>WorkloadService: getUsersWorkload(filters)
    WorkloadService->>DB: Base query + apply filters
    Note over DB: Order by remaining_work_percent desc, paginate
    WorkloadService->>WorkloadService: Map result to WorkloadUserData DTO

    Backend->>WorkloadService: getWorkloadSummary(filters)
    WorkloadService->>DB: Aggregate values (same base query + filters)

    Backend->>WorkloadService: getFilterOptions()
    WorkloadService->>DB: Available users (encoded IDs + name + avatar_url)
    WorkloadService->>WorkloadService: WorkloadStatus enum values (id, name=label(), severity)

    Backend->>Browser: Inertia render('workload/index', {users, summary, filters, filterOptions})
    Browser-->>User: Summary cards + DataTable + filters
```

### Workload Statuses

| Status | Severity | Deskripsi |
|--------|----------|-----------|
| Free | success | Tidak memiliki task |
| Almost Done | info | Hampir selesai (rendah) |
| Ongoing | warn | Beban normal |
| Overloaded | danger | Beban berlebih (tinggi) |

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/dashboard` | `DashboardController@index` |
| GET | `/workload-users` | `WorkLoadUserController@index` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/Dashboard.vue` | Dashboard overview |
| `pages/workload/index.vue` | Workload monitoring |
