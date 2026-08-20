import type { PrimeSeverity } from '@/types';

export interface TimelineBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface TimelineUser {
    id: string;
    name: string;
    email: string;
}

export interface TimelineTask {
    id: string;
    parent_id: string | null;
    sequence_number: number | null;
    title: string;
    progress: number;
    start_date: string | null;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    status: TimelineBadge | null;
    type: TimelineBadge | null;
    category: TimelineBadge | null;
    users: TimelineUser[];
    sub_task_recursive: TimelineTask[];
}
