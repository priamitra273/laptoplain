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
