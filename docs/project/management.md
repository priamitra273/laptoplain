# Project Management

CRUD project dengan detail page 7 tab, manajemen anggota, summary, dan policy permissions.

---

## 1. Project List (Index)

### Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant ProjectService
    participant ProjectRepo
    participant DB

    User->>Browser: GET /project
    Browser->>Backend: GET /project (route.permission middleware)

    Backend->>ProjectService: getIndexData()
    ProjectService->>ProjectRepo: getAllVisibleForUser(Auth::user())
    ProjectRepo->>DB: Query projects where user is member
    ProjectService->>ProjectRepo: getProjectStatuses(), getProjectPriorities()
    ProjectService->>ProjectService: Encode IDs with Sqids
    ProjectService->>Backend: {projects: ProjectData[], statuses, priorities}
    Backend->>Browser: Inertia render('project/Index')
    Browser-->>User: DataTable dengan cell editing + filter + pagination
```

### Frontend (Table.vue)

**Editing inline:** Cell-by-cell via `editableMode="cell"`. Setiap kolom yang bisa diedit langsung trigger `router.put(route('project.update'))`.

**Filters:**
- Search global (input text)
- Status (MultiSelect)
- Priority (MultiSelect)
- Date range (DatePicker)
- Progress (Slider)

**Pagination:** 10/25/50 per page, default 25.

---

## 2. Create Project

### Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    User->>Browser: Klik Add -> isi drawer form
    Browser->>Backend: POST /project

    Backend->>Backend: Validasi ProjectStoreRequest
    Note over Backend: title=required|string|max:255<br/>description=nullable|required|string<br/>emoji=nullable|required|string|max:100<br/>start_date=required|date<br/>due_date=nullable|date|after_or_equal:start_date<br/>status_id=required|exists:ms_project_statuses,id<br/>priority_id=required|exists:ms_project_priority,id<br/>prepareForValidation: owned_id=Auth::id() if missing<br/>prepareForValidation: Sqids-decode status_id & priority_id

    Backend->>DB: Project::create(validated)
    Backend->>DB: Update progress via $project->calculateProgress()
    Backend->>DB: Create ProjectMember as "Owner" role
    Note over DB: MsProjectRole::where('name','Owner')->first()
    Backend->>Browser: Redirect to project.index with "Project added successfully"
```

### Validasi Lengkap (ProjectStoreRequest)

| Field | Rule |
|-------|------|
| `title` | required, string, max:255 |
| `description` | nullable, required, string |
| `emoji` | nullable, required, string, max:100 |
| `start_date` | required, date |
| `due_date` | nullable, date, after_or_equal:start_date |
| `status_id` | required, exists:ms_project_statuses,id |
| `priority_id` | required, exists:ms_project_priority,id |

**prepareForValidation:** `owned_id` di-set ke `Auth::id()` jika tidak ada. `status_id` dan `priority_id` di-decode dari Sqids ke integer.

---

## 3. Update Project

### Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    Browser->>Backend: PUT /project/{encoded}

    Backend->>Backend: Sqids::decode(encoded)
    Backend->>DB: Project::findOrFail(id)
    Note over Backend: Gagal -> back()->with('error','Project not found')

    Backend->>Backend: Validasi ProjectUpdateRequest
    Note over Backend: Semua field pakai "sometimes|required" (partial update)<br/>title=sometimes|required|string|max:255<br/>description=sometimes|nullable|string<br/>emoji=sometimes|nullable|string|max:10<br/>start_date=sometimes|required|date<br/>due_date=sometimes|required|date|after_or_equal:start_date<br/>status_id=sometimes|required|exists:ms_project_statuses,id<br/>priority_id=sometimes|required|exists:ms_project_priority,id<br/>withValidator: jika due_date tidak dikirim & status_id (request/DB) 1 atau 2 -> error

    Backend->>DB: $project->update(validated)
    Backend->>DB: Update progress via $project->calculateProgress()
    Backend->>Backend: Cek referer header
    alt From detail page
        Backend->>Browser: Redirect to project.show
    else From index page
        Backend->>Browser: Redirect to project.index
    end
```

### Validasi Lengkap (ProjectUpdateRequest)

| Field | Rule |
|-------|------|
| `title` | sometimes, required, string, max:255 |
| `description` | sometimes, nullable, string |
| `emoji` | sometimes, nullable, string, max:10 |
| `start_date` | sometimes, required, date |
| `due_date` | sometimes, required, date, after_or_equal:start_date |
| `status_id` | sometimes, required, exists:ms_project_statuses,id |
| `priority_id` | sometimes, required, exists:ms_project_priority,id |

**withValidator:** Jika `due_date` dikirim, rule biasa jalan. Jika tidak: ambil `status_id` dari request atau dari DB (`projects.status_id`), lalu jika status = 1 atau 2 tambahkan error `due_date` ("Due date is required when status is set to..."). **prepareForValidation:** `owned_id` di-set ke `Auth::id()` jika tidak ada; `status_id` dan `priority_id` di-decode dari Sqids.

---

## 4. Delete Project

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    Browser->>Backend: DELETE /project/{encoded}
    Backend->>Backend: Sqids::decode(encoded)
    Backend->>DB: Project::findOrFail(id)

    Backend->>DB: $project->allTasks()->chunkById(100)
    loop Setiap chunk 100 tasks
        Backend->>DB: TaskNotification::createTaskNotification(task, assignedUserIds, DELETED)
        Note over DB: Menulis ke pivot table + Redis cache notifications:user:{id}
    end

    Backend->>DB: $project->allTasks()->delete()
    Backend->>DB: $project->delete()
    Backend->>Browser: Redirect ke project.index
```

---

## 5. Project Detail (Show)

### Flow Backend

```mermaid
sequenceDiagram
    User->>Browser: GET /project/{encoded}
    Browser->>Backend: GET /project/{encoded}

    Backend->>ProjectService: getShowData(encoded)
    ProjectService->>ProjectRepo: findWithRelationsForShow(encoded)
    Note over ProjectRepo: Load project + status + priority + members.user + members.role<br/>+ tasks.withRecursive(children.status/priority/type/category/users.media)

    ProjectService->>DB: Recalculate progress
    ProjectService->>ProjectService: formatProjectTasks()
    Note over ProjectService: Set is_overdue & completed_at per task.<br/>is_overdue = completed: updated_at > due_date.endOfDay<br/>               uncompleted: due_date.endOfDay past

    ProjectService->>ProjectService: getAuthUserPolicy(project)
    Note over ProjectService: Super admin -> fullAccessPolicy()<br/>Member -> role.config (ConfigData DTO)<br/>{"task":["create","update","delete"], "sprint":[...],<br/> "project_member":[...], "allow_task_status":[],<br/> "allow_update_task_fields":[]}

    ProjectService->>ProjectService: Encode IDs with Sqids
    ProjectService->>Backend: Return data array
    Backend->>Browser: Inertia render('project/Detail')
```

**Data yang dikembalikan:** `project`, `members` (DTO format), `roles`, `users` (non-members, available), `tasks` (with is_overdue + completed_at + media), `taskStatuses`, `taskPriorities`, `taskTypes`, `tags`, `assignableUsers` (members only), `statuses`, `priorities`, `sprints` (active), `backlog`, `taskCategories`, `epics` (encode IDs), `isMember` (boolean), `policy` (ConfigData)

### Frontend Detail Page

7 Tabs dengan active tab disimpan per-user di sessionStorage (`project-detail-active-tab-{userId}`):

| Tab | Komponen | Deskripsi |
|-----|----------|-----------|
| Kanban | `TaskKanbanBoard.vue` | Drag-drop board per status kolom (vue-draggable-plus) |
| List | `task/Table.vue` | TreeTable hierarchical dengan drag reparent (hold 900ms) |
| Backlog | `task/Backlog.vue` | Sprint sections + backlog list drag-drop |
| Details | `ProjectDetailsTab.vue` | Description (Quill editor) + timestamps |
| Team | `member/Table.vue` | Members CRUD (add/edit/delete) |
| Timeline | `ProjectGanttChart.vue` | Highcharts Gantt chart semua task |
| Report | `ProjectReportTab.vue` | Sprint selector + burndown + status report |

**Dialog/Drawer:**
- `MemberAddForm` — Add member via AutoComplete user search + role Select
- `MemberEditForm` — Edit role & active status
- `TaskForm` — Drawer right side, 50rem width, maximizable

---

## 6. Project Members

### Policy Enforcement

Di level project detail, permission diatur oleh ConfigData dari role member:
- `project_member: ['create','update','delete']` — CRUD member
- `task: ['create','update','delete']` — CRUD task
- `sprint: ['create','update','delete']` — CRUD sprint
- Super admin selalu mendapat full access (semua CRUD)

### Flow Add Member

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    Browser->>Backend: POST /project/{project}/members
    Backend->>Backend: Sqids::decode(encoded)
    Note over Backend: Validasi StoreProjectMemberRequest:<br/>user_id=required|exists:users,id<br/>project_role_id=required|exists:ms_project_roles,id<br/>prepareForValidation: owned_id=Auth::id(),<br/>Sqids-decode user_id & project_role_id

    Backend->>DB: Cek duplicate (project_id + user_id)
    alt Sudah member
        Backend->>Browser: back()->with('error','User already a member')
    else Belum member
        Backend->>DB: ProjectMember::create({project_id, user_id, project_role_id, is_active=true})
        Backend->>Browser: to_route('project.show')->with('success','Member added')
    end
```

### Flow Update Member

```mermaid
sequenceDiagram
    Browser->>Backend: PUT /project/{project}/members/{member}
    Backend->>Backend: Decode Sqid memberId & find member
    Note over Backend: Validasi UpdateProjectMemberRequest:<br/>project_role_id=required|exists:ms_project_roles,id<br/>is_active=required|boolean<br/>prepareForValidation: owned_id=Auth::id(),<br/>Sqids-decode project_role_id

    Backend->>DB: Cari MsProjectRole dimana name='Owner'
    alt isCurrentlyOwner && !willBeOwner && ownerCount <= 1
        Backend->>Browser: back()->withErrors('Project must have at least one Owner')
    else
        Backend->>DB: $member->update(validated)
        Backend->>Browser: Redirect ke project detail
    end
```

### Flow Delete Member

```mermaid
sequenceDiagram
    Browser->>Backend: DELETE /project/{project}/members/{member}
    Backend->>Backend: Decode Sqid memberId & find member
    Backend->>DB: Cek jika member adalah Owner (MsProjectRole name='Owner')
    alt Owner && ownerCount <= 1
        Backend->>Browser: back()->withErrors('Project must have at least one Owner')
    else
        Backend->>DB: $member->delete()
        Backend->>Browser: Redirect ke project detail
    end
```

---

## 7. Project Summary

Single-action controller (`ProjectSummaryController@__invoke`).

**Response JSON:**

| Key | Deskripsi |
|-----|-----------|
| `statusOverview` | Count grouped by status.name + color dari status.severity |
| `priorityBreakdown` | Count grouped by priority.name |
| `categoryBreakdown` | Count grouped by category.name (default "Uncategorized") |
| `teamWorkload` | Per user: name, avatar_url, total tasks, completed count |
| `epicProgress` | Epic dengan children: id, title, progress%, total children, done count |
| `recentActivity` | 10 task terakhir: id, title, status, updated_at, users (name + avatar) |

**Authorization:** `Auth::user()->cannot('view', $project)` → `abort(403)`.

---

## Routes

| Method | URI | Controller | Middleware |
|--------|-----|------------|------------|
| GET | `/project` | `ProjectController@index` | `route.permission` |
| POST | `/project` | `ProjectController@store` | `route.permission` |
| GET | `/project/{encoded}` | `ProjectController@show` | `route.permission` |
| PUT | `/project/{project}` | `ProjectController@update` | `route.permission` |
| DELETE | `/project/{project}` | `ProjectController@destroy` | `route.permission` |
| GET | `/project/{encoded}/summary` | `ProjectSummaryController` | `route.permission` |
| POST | `/project/{project}/members` | `ProjectMemberController@store` | `auth` |
| PUT | `/project/{project}/members/{member}` | `ProjectMemberController@update` | `auth` |
| DELETE | `/project/{project}/members/{member}` | `ProjectMemberController@destroy` | `auth` |

## Frontend Files

| File | Purpose |
|------|---------|
| `pages/project/Index.vue` | Wrapper pass data ke Table.vue |
| `pages/project/Table.vue` | DataTable dengan inline cell edit (editableMode="cell") |
| `pages/project/Detail.vue` | Detail 7 tabs + dialog/drawer management |
| `pages/project/Form.vue` | Drawer form create |
| `pages/project/partials/ProjectHeader.vue` | Header title + emoji inline edit + member avatars |
| `pages/project/partials/ProjectStats.vue` | 4 stat cards: Status, Priority, Timeline (editable dates), Progress bar |
| `pages/project/partials/ProjectDetailsTab.vue` | Deskripsi (Quill editor) + timestamps |
| `pages/project/partials/ProjectReportTab.vue` | Sprint selector -> Burndown + Status report |
| `pages/project/member/Table.vue` | DataTable members |
| `pages/project/member/Form.vue` | Add member dialog |
| `pages/project/member/EditFormTemp.vue` | Edit member dialog |
| `components/ProjectGanttChart.vue` | Highcharts Gantt chart |
| `composables/useProjectPermissions.ts` | canAction, canUpdateTaskStatus, canUpdateTaskField |
