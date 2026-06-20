import { PrimeSeverity, UploadedFile } from '@/types';

/*
|--------------------------------------------------------------------------
| Slim payload types for the lazy, per-tab project detail (project-lazy/*).
| These mirror the trimmed shapes produced by App\Services\ProjectLazyService
| — intentionally much smaller than the classic project/ Detail props.
| All ids are sqid strings at runtime (see Sqids::rec_encode_ids_in_list).
|--------------------------------------------------------------------------
*/

export interface RoleOption {
    id: string;
    name: string;
}

export interface ProjectStatusOption {
    id: string;
    name: string;
    severity?: PrimeSeverity;
}

export interface ProjectPriorityOption {
    id: string;
    name: string;
    severity?: PrimeSeverity;
}

export interface TaskStatusOption {
    id: string;
    name: string;
    severity: string;
    score?: number;
}

export interface TaskPriorityOption {
    id: string;
    name: string;
    severity: string;
}

export interface TaskTypeOption {
    id: string;
    name: string;
    severity: string;
}

export interface TaskCategoryOption {
    id: string;
    name: string;
    icon?: string;
    severity?: PrimeSeverity;
}

export interface TagOption {
    id: string;
    name: string;
    severity: string;
}

export interface SlimUser {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

/* ---- Shell (rendered by the persistent layout on every tab) ---- */

export interface ShellMember {
    id: string;
    user: SlimUser;
}

export interface ShellProject {
    id: string;
    project_no: string;
    title: string;
    emoji: string;
    progress: number;
    start_date?: string | null;
    due_date?: string | null;
    status_id?: string | null;
    priority_id?: string | null;
    status?: ProjectStatusOption | null;
    priority?: ProjectPriorityOption | null;
}

export interface ShellProps {
    project: ShellProject;
    members: ShellMember[];
    statuses: ProjectStatusOption[];
    priorities: ProjectPriorityOption[];
    isMember: boolean;
    policy: App.Data.ProjectRole.ConfigData | null;
}

/* ---- Tab payloads ---- */

export interface KanbanCard {
    id: string;
    parent_id: string | null;
    title: string;
    description?: string | null;
    start_date?: string | null;
    due_date?: string | null;
    progress: number;
    is_overdue: boolean;
    status?: TaskStatusOption | null;
    priority?: TaskPriorityOption | null;
    type?: TaskTypeOption | null;
    users: SlimUser[];
    sub_task_recursive: KanbanCard[];
}

export interface ListTask {
    id: string;
    parent_id: string | null;
    title: string;
    progress: number;
    start_date?: string | null;
    due_date?: string | null;
    completed_at?: string | null;
    is_overdue: boolean;
    created_at?: string | null;
    updated_at?: string | null;
    status?: TaskStatusOption | null;
    type?: TaskTypeOption | null;
    category?: TaskCategoryOption | null;
    users: SlimUser[];
    sub_task_recursive: ListTask[];
}

export interface Epic {
    id: string;
    title: string;
    story_points?: number | null;
}

export interface TeamMember {
    id: string;
    is_active: boolean;
    user: SlimUser;
    role: RoleOption;
}

export interface KanbanProps extends ShellProps {
    tasks: KanbanCard[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
    tags: TagOption[];
    assignableUsers: SlimUser[];
    epics: Epic[];
}

export interface ListProps extends ShellProps {
    tasks: ListTask[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
    tags: TagOption[];
    assignableUsers: SlimUser[];
}

export interface TeamProps extends ShellProps {
    members: TeamMember[];
    roles: RoleOption[];
    users: SlimUser[];
}

export interface TabItem {
    key: string;
    label: string;
    icon: string;
}

/* ---- On-demand task form payload (fetched via project.tasks.edit) ---- */

export interface TaskFormOptions {
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
    tags: TagOption[];
}

export interface ParentTaskOption {
    id: string;
    parent_id: string | null;
    title: string;
    category?: { id: string; name: string } | null;
}

/* ---- Backlog tab payload (slim sprint + backlog board) ---- */

export interface SprintStatusOption {
    id: string;
    name: string;
    severity?: string | null;
}

export interface BacklogTask {
    id: string;
    parent_id: string | null;
    title: string;
    story_points?: number | null;
    status?: TaskStatusOption | null;
    priority?: TaskPriorityOption | null;
    category?: TaskCategoryOption | null;
    users: SlimUser[];
}

export interface BacklogSprint {
    id: string;
    name: string;
    goal?: string | null;
    duration?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    order?: number | null;
    status?: SprintStatusOption | null;
    tasks: BacklogTask[];
}

export interface BacklogProps extends ShellProps {
    sprints: BacklogSprint[];
    backlog: BacklogTask[];
    epics: Epic[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
    tags: TagOption[];
    assignableUsers: SlimUser[];
}

/* ---- Task table (List tab) ---- */

export interface LazyTaskFormattedData {
    id: string;
    parent_id: string | null;
    title: string;
    status?: TaskStatusOption;
    type?: TaskTypeOption;
    category?: TaskCategoryOption;
    users: SlimUser[];
    progress: number;
    start_date: string | null;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    level?: number;
}

export interface LazyTaskFormatted {
    key: string;
    data: LazyTaskFormattedData;
    children: LazyTaskFormatted[];
    original: ListTask;
}

export interface LazyTaskTableProps {
    projectId: string;
    tasks: ListTask[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
}

export interface LazyTaskTableEmits {
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: ListTask, parentId: string | null): void;
}

export interface LazyTaskTableFilter {
    global: string;
    'status.name': string[] | null;
    'type.name': string[] | null;
}

/* ---- Task form (drawer) ---- */

export type TaskFormPayload = App.Data.Task.ProjectTaskData;

export interface LazyMember {
    user: SlimUser;
}

export interface ParentTaskNode {
    id: string;
    title: string;
    category?: { id: string; name: string } | null;
    sub_task_recursive: ParentTaskNode[];
}

export interface LazyTagForm {
    name: string;
    severity: string;
}

export interface LazyTaskFormProps {
    parentId: string | null;
    projectId: string;
    task: TaskFormPayload | null;
    sprintId?: string | null;
    tasks: ParentTaskNode[];
    taskTypes: TaskTypeOption[];
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskCategories?: TaskCategoryOption[];
    excludeEpicCategory?: boolean;
    onlyEpicCategory?: boolean;
    hideParentTaskField?: boolean;
    tags: TagOption[];
    members: LazyMember[];
}

export interface LazyTaskFormData {
    _method: 'POST' | 'PUT';
    title: string;
    description: string;
    project_id: string;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    task_category_id: string | null;
    sprint_id: string | null;
    parent_id: string | null;
    start_date: Date | null;
    due_date: Date | null;
    is_archived: boolean;
    progress_value: number;
    assign_users: string[];
    unassign_users: string[];
    add_tag: { new: LazyTagForm[]; exists: string[] };
    remove_tag: string[];
    attachments: File[] | UploadedFile[] | null;
    [key: string]: any;
}

export interface SavedTaskPayload {
    mode: 'create' | 'edit';
    id: string;
    parentId: string | null;
    title: string;
    startDate: string | null;
    dueDate: string | null;
    isArchived: boolean;
    status: TaskStatusOption | null;
    type: TaskTypeOption | null;
    category: TaskCategoryOption | null;
    priority: TaskPriorityOption | null;
    users: SlimUser[];
}
