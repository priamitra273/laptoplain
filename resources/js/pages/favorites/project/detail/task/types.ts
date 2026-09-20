import type { ShellBadge, ShellProps, TaskOption, TaskOptionUser } from '../types';

export interface ListCategory extends ShellBadge {
    icon: string | null;
}
export interface ListTask extends Record<string, unknown> {
    id: string;
    parent_id: string | null;
    sequence_number: number | null;
    title: string;
    progress: number;
    start_date: string | null;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    status: ShellBadge | null;
    type: ShellBadge | null;
    category: ListCategory | null;
    users: TaskOptionUser[];
    sub_task_recursive: ListTask[];
}

export interface ListProps extends ShellProps {
    tasks?: ListTask[];
    assignableUsers?: TaskOptionUser[];
    taskStatuses: TaskOption[];
    taskPriorities: TaskOption[];
    taskTypes: TaskOption[];
    taskCategories: TaskOption[];
    tags: TaskOption[];
}

export interface TaskFilters {
    search: string;
    status: string | null;
}

export type DropMode = 'before' | 'inside' | 'after' | 'root';
export interface TaskMove {
    taskId: string;
    parentId: string | null;
    position: number;
}
