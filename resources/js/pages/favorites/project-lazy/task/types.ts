import type { PrimeSeverity } from '@/types';

export interface TaskBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface TaskCategoryOption extends TaskBadge {
    icon: string | null;
}

export interface ListUser {
    id: string;
    name: string;
    email: string;
    avatar_url?: string | null;
}

export interface ListTask {
    id: string;
    parent_id: string | null;
    sequence_number: number | null;
    title: string;
    progress: number;
    start_date: string | null;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    status: TaskBadge | null;
    type: TaskBadge | null;
    category: TaskCategoryOption | null;
    priority: TaskBadge | null;
    users: ListUser[];
    sub_task_recursive: ListTask[];
}

export interface TaskMovePayload {
    taskId: string;
    parentId: string | null;
    position: number;
}

export interface ListTableFilters {
    global: string;
    statuses: string[];
    types: string[];
}
