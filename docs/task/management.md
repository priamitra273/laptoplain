# Task Management

Manajemen task dalam project: CRUD, status/priority/parent update, aktivitas, detail view.

---

## 1. Create Task

### Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant CreateTaskAction
    participant DB

    Browser->>Backend: POST /project/{project}/tasks

    Backend->>Backend: $projectService->findByEncodedId($encoded)
    Backend->>Backend: Policy: user->cannot('create', [Task::class, project])
    alt Unauthorized
        Backend->>Browser: back()->with('error', permission denied)
    else Authorized
    end

    Backend->>Backend: Validasi TaskStoreRequest (lihat tabel di bawah)
    Backend->>CreateTaskAction: execute(project, validated, userId)

    Note over CreateTaskAction: DB::transaction

    CreateTaskAction->>CreateTaskAction: Prepare assignees (auto-add creator if ada assignee lain)
    CreateTaskAction->>CreateTaskAction: Prepare tags (existing + create new Tag models)
    CreateTaskAction->>CreateTaskAction: Auto-increment sequence_number dari max project
    CreateTaskAction->>CreateTaskAction: Calculate initial progress dari MsTaskStatus.score

    CreateTaskAction->>DB: Task::create(taskData)
    CreateTaskAction->>DB: Sprint::syncWithoutDetaching (if sprint_id)
    CreateTaskAction->>DB: Task.users::syncWithoutDetaching (assignees)
    CreateTaskAction->>DB: Task.tags::syncWithoutDetaching (tags)
    CreateTaskAction->>DB: Media::addMediaFromRequest (attachments collection)
    CreateTaskAction->>CreateTaskAction: event(new TaskCreated(task, assignUserIds))

    Backend->>Browser: Redirect ke project.show
```

### Validasi Lengkap (TaskStoreRequest)

| Field | Rule |
|-------|------|
| `project_id` | required (POST), exists:projects,id |
| `parent_id` | sometimes, nullable, exists:tasks,id |
| `status_id` | required, exists:ms_task_statuses,id |
| `priority_id` | required, exists:ms_task_priorities,id |
| `type_id` | required, exists:ms_task_types,id |
| `task_category_id` | nullable, exists:task_categories,id |
| `sprint_id` | nullable, exists:project_sprints,id |
| `owned_id` | sometimes, exists:users,id |
| `emoji` | nullable, string, max:100 |
| `title` | required, string, max:255 |
| `description` | nullable, string |
| `start_date` | Rule::requiredIf (status not "To Do"/"Blocked"), date |
| `due_date` | Rule::requiredIf (status not "To Do"/"Blocked"), date, after_or_equal:start_date |
| `sequence_number` | nullable, integer |
| `is_archived` | boolean |
| `assign_users` | nullable, array; each: exists:users,id |
| `unassign_users` | sometimes, array; each: exists:users,id |
| `add_tag.exists` | sometimes, array; each: exists:tags,id |
| `add_tag.new.*.name` | required_with:add_tag.new, string, max:255 |
| `add_tag.new.*.severity` | nullable, string, max:50 |
| `remove_tag` | sometimes, array; each: exists:tags,id |
| `attachments.*` | required, FileOrMedia (extensions gambar/video/doc, maxSize: 20MB) |

**prepareForValidation:** Sqids-decode `status_id`, `priority_id`, `type_id`, `task_category_id`, `sprint_id`, `project_id`, `owned_id`, `parent_id`, `assign_users`, `unassign_users`, `add_tag.exists`, `remove_tag`. Merge `progress` dari `progress_value`.

**withValidator:**
- In Progress status tanpa due_date → error
- Epic category tidak bisa punya parent → error

---

## 2. Update Task

### Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant UpdateTaskAction
    participant DB

    Browser->>Backend: PUT /project/{project}/tasks/{task}

    Backend->>Backend: Sqids::decode(taskEncoded)
    Backend->>DB: Eager-load users + project members + roles
    Note over DB: Load isTaskMember dan isOwner via query exists

    Backend->>Backend: Policy: user->cannot('update', task)
    alt Unauthorized
        Backend->>Browser: abort(403)
    end

    Backend->>Backend: Validasi TaskUpdateRequest (partial)

    Backend->>UpdateTaskAction: execute(task, validated)
    Note over UpdateTaskAction: DB::transaction

    UpdateTaskAction->>UpdateTaskAction: handleStatusChange (Completed=progress 100)
    UpdateTaskAction->>UpdateTaskAction: handleProgressFromStatus (from score)
    UpdateTaskAction->>UpdateTaskAction: syncTags (add/remove)
    UpdateTaskAction->>UpdateTaskAction: cleanNonModelAttributes (strip meta fields)
    UpdateTaskAction->>UpdateTaskAction: Progress guard (drop if has children)
    UpdateTaskAction->>DB: task->update(data)
    UpdateTaskAction->>UpdateTaskAction: syncMedia (delta: keep/delete/add)
    UpdateTaskAction->>UpdateTaskAction: syncUsers (assign new + detach unassigned)
    UpdateTaskAction->>UpdateTaskAction: recalculateParentProgress (walk up chain)
    UpdateTaskAction->>UpdateTaskAction: sendNotification (UPDATED to assignees)

    Backend->>Browser: Redirect back dengan success flash
```

### Validasi Lengkap (TaskUpdateRequest)

| Field | Rule |
|-------|------|
| `project_id` | sometimes, exists:projects,id |
| `parent_id` | sometimes, nullable, exists:tasks,id |
| `status_id` | required, exists:ms_task_statuses,id |
| `priority_id` | sometimes, nullable, exists:ms_task_priorities,id |
| `type_id` | sometimes, nullable, exists:ms_task_types,id |
| `task_category_id` | sometimes, nullable, exists:task_categories,id |
| `title` | sometimes, string, max:255 |
| `description` | sometimes, nullable, string |
| `start_date` | Rule::requiredIf (status not "To Do"/"Blocked"), date |
| `due_date` | Rule::requiredIf (status not "To Do"/"Blocked"), date, after_or_equal:start_date |
| `is_archived` | sometimes, boolean |
| `assign_users` | sometimes, array; each: exists:users,id |
| `unassign_users` | sometimes, array; each: exists:users,id |
| `add_tag.exists` | sometimes, array; each: exists:tags,id |
| `add_tag.new.*.name` | required_with, string, max:255 |
| `remove_tag` | sometimes, array; each: exists:tags,id |
| `attachments.*` | nullable, FileOrMedia |

**prepareForValidation:** Sqids-decode. Jika `status_id === 1` (To Do), set `due_date = null`. Merge progress dari `progress_value`.

**withValidator:** In Progress status wajib ada due_date (check existing di DB jika tidak di request).

---

## 3. Delete Task

```mermaid
sequenceDiagram
    Browser->>Backend: DELETE /project/{project}/tasks/{task}
    Backend->>Backend: authorize('delete', projectEncoded, task)
    Note over Backend: Cek project->id === task->project_id (abort 404)<br/>Cek user->cannot('delete', task) (abort 403)
    Backend->>DB: $task->delete()
    Backend->>Browser: Redirect ke project.show
```

---

## 4. Task Detail

### Flow Backend

```mermaid
sequenceDiagram
    Browser->>Backend: GET /task/{task}
    Backend->>Backend: Policy: user->cannot('view', task)
    alt Unauthorized
        Backend->>Browser: abort(403)
    end

    Backend->>TaskService: getTaskForDetail(taskId, userId)
    TaskService->>DB: Eager-load all task relations
    Note over DB: status, priority, type, category, tags<br/>creator.media, users.media, parent, children<br/>project members with roles<br/>sub_task_recursive (nested)

    Backend->>TaskService: getTaskDetailProps(task, user)
    Note over TaskService: Recalculate progress<br/>Format assignableUsers, assignedUsers, creator<br/>isOwner (Owner role check), isTaskMember<br/>Get comments as TaskCommentData collection

    Backend->>Browser: Inertia render('project/task/Detail')
```

### Frontend Detail (Detail.vue)

Layout 2 kolom:
- **Kiri:** TaskSubtasks + TaskDetails (inline editable) + TaskMembers
- **Kanan:** TaskDescription + TaskComments

**TaskDetails.vue - Inline Editing:**

Permission check sidebar:
- Developer role → hanya bisa lihat status "In Progress" dan "In Review"
- `!isTaskMember && !hasPermission() && !isOwner` → denied, tampilkan toast
- Jika `isTaskMember` atau `hasPermission()` atau `isOwner` → bisa edit

Inplace edit flow:
1. Klik field → `startEdit()` → masuk edit mode
2. `handleSelectChange()`: jika status "In Progress" + no due_date → emit `showInProgressDialog`
3. `autoSave(field, value)`: gunakan `useForm` + `form.put()` ke `project.tasks.update` route
4. Click outside / Escape → `cancelEdit()`
5. Progress bar otomatis (auto) jika task punya sub_task_recursive

---

## 5. Update Status (Kanban Drag-Drop)

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant TaskService
    participant DB

    User->>Browser: Drag card ke kolom lain
    Browser->>Browser: Cek canUpdateTaskStatus(statusId)
    Note over Browser: Policy check: allow_task_status kosong → allow all<br/>atau must include statusId

    Browser->>Backend: PUT /task/{task}/status

    Backend->>Backend: Policy: user->cannot('update', task)
    alt Unauthorized
        Backend->>Browser: abort(403)
    else Authorized
    end

    Backend->>Backend: Validasi TaskUpdateStatusRequest
    Note over Backend: status_id=required|SqidExists(MsTaskStatus)<br/>due_date=sometimes|nullable|date<br/>due_date requiredIf + closure: >=task.start_date<br/>withValidator: Product Owner restricted

    Backend->>TaskService: updateStatus(task, status, due_date)
    TaskService->>TaskService: On Completed: set now + progress=100
    TaskService->>TaskService: On other: null completed_at + score progress
    TaskService->>TaskService: Recalculate parent progress up chain
    TaskService->>TaskService: dispatchNotification(TaskNotificationType::UPDATED)

    Backend->>Browser: JSON {success: true}
```

### TaskUpdateStatusRequest Validation

| Field | Rule |
|-------|------|
| `status_id` | required, string, SqidExists(MsTaskStatus) |
| `due_date` | sometimes, nullable, date; requiredIf status not "To Do"/"Blocked" and task has no due_date; closure: >= task.start_date |

**withValidator:** Product Owner (role `product-owner-*`) hanya bisa set status ke "To Do", "Completed" (Complete), atau "Block" (Blocked).

---

## 6. Update Parent (Drag-Drop TreeTable)

```mermaid
sequenceDiagram
    User->>Browser: Hold 900ms then drag to target or root drop zone
    Browser->>Backend: PUT /project/{project}/tasks/{task}/parent
    Backend->>Backend: authorize('update', encoded, task)
    Backend->>Backend: Validasi TaskUpdateParentRequest
    Note over Backend: parent_id nullable, closure validasi:<br/>1. Sqids-decode → find Task exists<br/>2. Task must be in same project<br/>3. Task cannot be the task itself<br/>4. Task cannot be a descendant of self
    Backend->>TaskService: updateParent(task, decodedParentId)
    TaskService->>TaskService: Recalculate progress: old parent tree + new parent tree
    Backend->>Browser: JSON {success: true}
```

### TaskUpdateParentRequest Validation

Semua validasi dalam satu closure pada field `parent_id`:
1. **Nullable.** Kalau null, tidak ada validasi lanjutan.
2. **Sqids-decode** + cek existence di `tasks` table.
3. **Same project:** parent `project_id` harus sama.
4. **Self check:** parent tidak boleh task itu sendiri.
5. **Ancestor check:** parent tidak boleh child/descendant dari task ini.

### TreeTable Frontend (Table.vue)

**Drag mechanics:**
- Hold 900ms (`DRAG_HOLD_MS`) untuk mengaktifkan drag mode
- `holdCandidateTaskId` → `holdTimerId` → validasi masih hover di row yang sama
- `autoExpandTargetKey` — auto-expand 500ms saat hover di atas parent
- Root drop zone: `pointerOnRootDropzone` = pointer di luar task row
- Filter disimpan per-user di sessionStorage (`task-table-filters-{userId}`)

**Action menu:**
- View Detail
- Add Subtask (emit add dengan parentId)
- Edit Task (emit edit)
- Delete (confirm + axios DELETE)
- Activity History (open modal → fetch `GET /task/{id}/activities`)

---

## 7. Task Activity

```mermaid
sequenceDiagram
    Browser->>Backend: GET /task/{encoded}/activities
    Backend->>Backend: Sqids::decode + find task
    Backend->>Backend: Policy: user->cannot('view', task)
    alt Unauthorized
        Backend->>Browser: abort(403)
    else Authorized
    end
    Backend->>TaskService: getActivities(task, 'updated')
    TaskService->>DB: Fetch activity logs
    TaskService->>TaskService: Resolve foreign keys to model names
    TaskService->>TaskService: Compare old vs new values per field
    TaskService->>TaskService: Resolve parent_id to task titles
    TaskService->>TaskService: Filter out unchanged fields
    Backend->>Browser: JSON {success: true, activities: [...]}
```

---

## 8. Task Report

### Flow

```mermaid
sequenceDiagram
    Browser->>Backend: GET /reports/tasks?filters...&per_page=25
    Backend->>Backend: Validasi TaskReportIndexRequest

    Note over Backend: names=array, names.*=SqidExists(User)<br/>statuses=array, statuses.*=SqidExists(MsTaskStatus)<br/>project_statuses=array, project_statuses.*=SqidExists(MsProjectStatus)<br/>priorities=array, priorities.*=SqidExists(MsTaskPriority)<br/>types=array, types.*=SqidExists(MsTaskType)<br/>start_date_from/to=date<br/>due_date_from/to=date<br/>search=string<br/>per_page=integer|min:1|max:100

    Backend->>TaskReportService: getIndexData(filters, perPage)
    TaskReportService->>TaskReportRepo: Paginate results
    TaskReportService->>TaskReportService: Format filter options with encoded IDs
    Backend->>Browser: Inertia render('project/task/TaskReport')

    Note over User,DB: EXPORT
    User->>Browser: Klik Export
    Browser->>Backend: GET /reports/tasks/export?filters...
    Backend->>TaskReportService: generateExportCallback(filters)
    Note over TaskReportService: Stream CSV dengan kolom:<br/>Task ID, Title, Summary (strip HTML, truncate 100),<br/>Creator, Status, Priority, Type, Project,<br/>Start Date, Due Date, Progress (%), Created At
    Backend->>Browser: StreamedResponse CSV download
```

### Frontend (TaskReport.vue)

**Filters:**
- Assignee (MultiSelect dengan SqidExists)
- Project Status (MultiSelect)
- Task Status (MultiSelect)
- Priority (MultiSelect)
- Type (MultiSelect)
- Start Date / Due Date (DatePicker range)
- Search (input text)

**Pagination:** 10/25/50/100 per page, default 25.

---

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/task` | `TaskController@index` |
| GET | `/task/{task}` | `TaskController@show` |
| POST | `/project/{project}/tasks` | `TaskController@store` |
| PUT | `/project/{project}/tasks/{task}` | `TaskController@update` |
| DELETE | `/project/{project}/tasks/{task}` | `TaskController@destroy` |
| PUT | `/task/{task}/status` | `TaskController@updateStatus` |
| PUT | `/project/{project}/tasks/{task}/priority` | `TaskController@updatePriority` |
| PUT | `/project/{project}/tasks/{task}/parent` | `TaskController@updateParent` |
| PUT | `/task/{task}/parents` | `TaskController@update_parents` |
| GET | `/task/{task}/parents` | `TaskController@parents` |
| GET | `/task/{task}/comment` | `TaskController@comments` |
| GET | `/task/{encoded}/activities` | `TaskActivityController@index` |
| GET | `/reports/tasks` | `TaskReportController@index` |
| GET | `/reports/tasks/export` | `TaskReportController@export` |

## Frontend Files

| File | Purpose |
|------|---------|
| `pages/project/task/Detail.vue` | Detail 2 kolom: content (kiri) + description/comments (kanan) |
| `pages/project/task/Form.vue` | Drawer form (right, 50rem) dengan sub-components di `form-ui/` |
| `pages/project/task/Table.vue` | TreeTable List tab — hold 900ms drag reparent, filter sessionStorage |
| `pages/project/task/partials/TaskKanbanBoard.vue` | Kanban drag-drop columns (vue-draggable-plus) |
| `pages/project/task/partials/TaskDetails.vue` | Inline edit: status, priority, type, dates, progress |
| `pages/project/task/partials/TaskHeader.vue` | Breadcrumb + gradient card |
| `pages/project/task/partials/TaskComments.vue` | Comment thread + MentionEditor |
| `pages/project/task/partials/TaskInProgressDialog.vue` | Due date dialog |
| `pages/project/task/TaskReport.vue` | Report page dengan semua filter |
| `components/TaskActivityLogModal.vue` | Activity log modal |
| `components/Mentioneditor.vue` | Rich text editor dengan @mentions |
