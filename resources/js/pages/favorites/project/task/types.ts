import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';

export interface TaskBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface TaskProjectOption {
    id: string;
    title: string;
}

export interface TaskAssignee {
    id: string;
    name: string;
    avatar_url?: string | null;
}

export interface AssignedTask {
    id: string;
    title: string;
    due_date: string | null;
    is_overdue: boolean;
    sequence_number: number | null;
    status: TaskBadge | null;
    priority: TaskBadge | null;
    type: TaskBadge | null;
    project: TaskProjectOption | null;
    users: TaskAssignee[];
    sub_task_count: number;
    sub_task_done_count: number;
}

export interface TaskStatusOption extends TaskBadge {
    score: number | null;
}

export type TaskPriorityOption = TaskBadge;
export type TaskTypeOption = TaskBadge;

export interface TaskPaginator<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

export interface TaskFilters {
    search: string | null;
    project_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    type_id: string | null;
    per_page: number;
}

export interface TaskBoardColumn {
    status: TaskStatusOption;
    tasks: AssignedTask[];
    total: number;
    has_more: boolean;
}

export interface TaskStatusSummaryEntry {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
    count: number;
}

export const severityDotStyle = (severity?: PrimeSeverity | null) => ({
    backgroundColor: `var(--ui-color-${severityColor(severity)}-500)`,
});

export interface TaskDetailUser {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

export interface TaskCategoryOption extends TaskBadge {
    icon: string | null;
}

export interface TaskDetailMedia {
    uuid: string;
    file_name: string;
    size: number;
    mime_type: string;
    url: string;
}

export interface TaskDetailSubTask {
    id: string;
    title: string;
    progress: number;
    status: TaskBadge | null;
    priority: TaskBadge | null;
    type: TaskBadge | null;
    category: TaskCategoryOption | null;
    users: { id: string; name: string }[];
}

export interface TaskDetailProject {
    id: string;
    title: string;
    emoji: string | null;
}

export interface TaskDetailTask {
    id: string;
    parent_id: string | null;
    title: string;
    description: string | null;
    status_id: string | null;
    priority_id: string | null;
    type_id: string | null;
    task_category_id: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    created_at: string;
    project: TaskDetailProject;
    status: TaskBadge | null;
    priority: TaskBadge | null;
    type: TaskBadge | null;
    tags: TaskBadge[];
    sub_task_recursive: TaskDetailSubTask[];
    media: TaskDetailMedia[];
}

export interface TaskDetailComment {
    id: number;
    body: string;
    user: TaskDetailUser;
    replies: TaskDetailComment[];
    created_at: string;
    updated_at: string;
}

export interface TaskParent {
    id: string;
    key: string;
    title: string;
    category: TaskCategoryOption | null;
}

export const severityBoxStyle = (severity?: PrimeSeverity | null) => {
    const color = severityColor(severity);
    return {
        backgroundColor: `color-mix(in srgb, var(--ui-color-${color}-500) 6%, transparent)`,
        borderColor: `color-mix(in srgb, var(--ui-color-${color}-500) 25%, transparent)`,
    };
};
