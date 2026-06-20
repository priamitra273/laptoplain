import { PrimeSeverity } from '@/types';

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
