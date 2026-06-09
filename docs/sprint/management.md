# Sprint Management

Manajemen sprint dalam project: CRUD, lifecycle (planning → active → completed), dan task assignment.

---

## 1. Sprint CRUD

### Flow Create

```mermaid
sequenceDiagram
    Browser->>Backend: POST /project/{project}/sprints
    Note over Backend: Validasi SprintStoreRequest:<br/>name=nullable|string|max:255<br/>goal=nullable|string<br/>start_date=nullable|date<br/>end_date=nullable|date|after_or_equal:start_date<br/>duration=nullable|in:1,2,3,4 week,Custom

    Backend->>SprintService: store(decodedProjectId, validated)
    SprintService->>SprintService: Auto-generate name if empty (Sprint N+1)
    SprintService->>SprintService: Order: lastOrder + 1
    SprintService->>SprintService: sprint_status_id = "planning"
    SprintService->>SprintService: created_by = updated_by = Auth::id()
    SprintService->>DB: ProjectSprint::create()
    Backend->>Browser: Redirect ke project detail / JSON success
```

### Flow Update

```mermaid
sequenceDiagram
    Browser->>Backend: PUT /project/{project}/sprints/{sprint}
    Note over Backend: Validasi SprintUpdateRequest:<br/>name=required|string|max:255<br/>goal=nullable|string<br/>start_date=nullable|date<br/>end_date=nullable|date|after_or_equal:start_date<br/>duration=nullable|in:1,2,3,4 week,Custom

    Backend->>SprintService: findByProject(decodedSprintId, decodedProjectId)
    Backend->>SprintService: update(sprint, validated)
    SprintService->>DB: Update fields + updated_by = Auth::id()
    Backend->>Browser: Redirect ke project detail
```

### Flow Delete

```mermaid
sequenceDiagram
    Browser->>Backend: DELETE /project/{project}/sprints/{sprint}
    Backend->>SprintService: destroy(sprint)

    SprintService->>SprintService: Cek status sprint
    alt Status = "Active"
        SprintService-->>Backend: throw HttpException 422 "Cannot delete active sprint"
        Backend->>Browser: JSON error / back()->with('error')
    else
        SprintService->>DB: Detach all tasks from sprint
        SprintService->>DB: Delete sprint
        Backend->>Browser: Redirect / JSON success
    end
```

---

## 2. Sprint Lifecycle

### Start Sprint

```mermaid
sequenceDiagram
    Browser->>Backend: PATCH /project/{project}/sprints/{sprint}/start
    Note over Backend: Validasi SprintStartRequest:<br/>goal=nullable|string<br/>duration=nullable|in:1,2,3,4 week,Custom<br/>start_date=nullable|date<br/>end_date=nullable|date|after_or_equal:start_date

    Backend->>SprintService: start(sprint, validated)
    SprintService->>SprintService: Set sprint_status_id = "active"
    SprintService->>SprintService: Set start_date = now() if not provided
    SprintService->>DB: Save sprint
    Backend->>Browser: Redirect ke project detail with "Sprint {name} started"
```

### Complete Sprint

```mermaid
sequenceDiagram
    Browser->>Backend: PATCH /project/{project}/sprints/{sprint}/complete
    Note over Backend: Validasi SprintCompleteRequest:<br/>retrospective=nullable|string<br/>move_incomplete_to.existing_sprint=nullable|integer|exists:project_sprints,id<br/>move_incomplete_to.other=nullable|in:backlog,new_sprint<br/>prepareForValidation: Sqids-decode existing_sprint

    Backend->>SprintService: complete(sprint, validated)
    Note over SprintService: Route incomplete tasks berdasarkan move_incomplete_to

    alt move to existing sprint
        SprintService->>DB: Attach incomplete tasks to target sprint
        SprintService->>DB: Detach from current sprint
        SprintService->>SprintService: Log activity "move_incomplete_to_other_sprint"

    else move to backlog
        SprintService->>DB: Detach incomplete tasks (back to backlog)
        SprintService->>SprintService: Log activity "move_incomplete_to_backlog"

    else move to new sprint
        SprintService->>DB: Create new planning sprint
        SprintService->>DB: Attach + detach incomplete tasks
        SprintService->>SprintService: Log activity "move_incomplete_to_new_sprint"
    end

    SprintService->>DB: Set sprint status = "completed", end_date, retrospective
    Backend->>Browser: Redirect with "Sprint {name} completed"
```

---

## 3. Task Assignment

### Assign Task to Sprint

```mermaid
sequenceDiagram
    Browser->>Backend: POST /project/{project}/sprints/{sprint}/tasks
    Note over Backend: Validasi SprintTaskAssignRequest:<br/>task_ids=required|array<br/>task_ids.*=integer|exists:tasks,id<br/>prepareForValidation: Sqids-decode all task_ids

    Backend->>SprintService: assignTasks(sprint, taskIds, projectId)
    SprintService->>SprintService: Validate tasks belong to project
    SprintService->>SprintService: Check category — block Epic (422)
    alt Task category = "Epic"
        SprintService-->>Backend: throw HttpException 422
        Backend->>Browser: JSON error
    else
        SprintService->>DB: Task::syncWithoutDetaching(taskIds)
        Backend->>Browser: Redirect / JSON {success: true}
    end
```

### Remove Task from Sprint

```mermaid
sequenceDiagram
    Browser->>Backend: DELETE /project/{project}/sprints/{sprint}/tasks/{task}
    Backend->>SprintService: removeTask(sprint, decodedTaskId)
    SprintService->>DB: Detach task dari sprint (move to backlog)
    Backend->>Browser: Redirect / JSON "Task dipindahkan ke backlog"
```

---

## Routes

| Method | URI | Controller | Status |
|--------|-----|------------|--------|
| GET | `/project/{project}/sprints` | `SprintController@index` | JSON |
| POST | `/project/{project}/sprints` | `SprintController@store` | Redirect / JSON |
| PUT | `/project/{project}/sprints/{sprint}` | `SprintController@update` | Redirect |
| DELETE | `/project/{project}/sprints/{sprint}` | `SprintController@destroy` | Redirect / JSON |
| PATCH | `/project/{project}/sprints/{sprint}/start` | `SprintController@start` | Redirect |
| PATCH | `/project/{project}/sprints/{sprint}/complete` | `SprintController@complete` | Redirect |
| POST | `/project/{project}/sprints/{sprint}/tasks` | `SprintController@assignTask` | Redirect / JSON |
| DELETE | `/project/{project}/sprints/{sprint}/tasks/{task}` | `SprintController@removeTask` | Redirect / JSON |
| GET | `/project/{project}/sprints/all` | `SprintReportController@index` | JSON |
| GET | `/project/{project}/sprints/{sprint}/burndown` | `SprintReportController@burndown` | JSON |
| GET | `/project/{project}/sprints/{sprint}/status-report` | `SprintReportController@statusReport` | JSON |

## Frontend Backlog Board (Backlog.vue)

**Komponen utama di tab Backlog:**

| Sub-komponen | Purpose |
|---|---|
| `SprintSection.vue` | Sprint card collapsible dengan: name, status Tag, date range, issue count, progress bar, Start/Complete buttons, drag-drop task rows, context menu (Edit/Start/Complete/Delete sprint) |
| `BacklogSection.vue` | "Backlog" header + issue count + Create Sprint button + drag-drop rows |
| `Taskrow.vue` | Drag handle + checkbox + category icon + title + story points + inline: Epic picker, Priority Select, Status Tag, Member avatars |
| `StartSprintDialog.vue` | Set goal, duration (1-4 weeks/Custom), start/end dates |
| `EditSprintDialog.vue` | Edit name, goal, duration, dates |
| `CompleteSprintDialog.vue` | Set retrospective, choose incomplete task destination |

**Permission checks (via `useProjectPermissions`):**
- `canSprintCreate` → tombol Create Sprint
- `canSprintUpdate` → tombol Edit Sprint
- `canSprintDelete` → tombol Delete Sprint
- `canTaskCreate` → tombol Create Task di backlog/sprint

**Provide/Inject:** `BacklogKey` symbol menyediakan epics, task priorities, actions (edit, add epic, update priority, view epic, toggle select, open menu, add task, add parent) ke semua child components.
