import type { PrimeSeverity } from '@/types';

export interface KanbanBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface KanbanStatusOption extends KanbanBadge {
    score: number | null;
}

export interface KanbanUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

export interface KanbanTask {
    id: string;
    parent_id: string | null;
    title: string;
    description: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    is_overdue: boolean;
    status: KanbanBadge | null;
    priority: KanbanBadge | null;
    type: KanbanBadge | null;
    category?: KanbanBadge | null;
    users: KanbanUser[];
    tags: KanbanBadge[];
    sub_task_recursive: KanbanTask[];
}

export interface KanbanColumn {
    status: KanbanStatusOption;
    tasks: KanbanTask[];
}

export interface TaskCommentUser {
    id: string;
    name: string;
    email: string;
    avatar_url?: string | null;
}

export interface TaskComment {
    id: string;
    body: string;
    user: TaskCommentUser;
    replies: TaskComment[];
    created_at: string;
    updated_at: string;
}

export interface TaskActivityField {
    field: string;
    old_value: string | null;
    new_value: string | null;
    has_value: boolean;
}

export interface TaskActivity {
    id: number;
    event: string;
    causer: TaskCommentUser | null;
    changed_fields: TaskActivityField[];
    created_at: string;
}
