import type { PrimeSeverity } from '@/types';
import type { KanbanBadge, KanbanUser } from '../kanban/types';

export interface BacklogSprintStatus {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface BacklogTask {
    id: string;
    parent_id: string | null;
    title: string;
    story_points: number | null;
    status: KanbanBadge | null;
    priority: KanbanBadge | null;
    category: KanbanBadge | null;
    users: KanbanUser[];
}

export interface BacklogSprint {
    id: string;
    name: string;
    goal: string | null;
    duration: string | null;
    start_date: string | null;
    end_date: string | null;
    order: number | null;
    status: BacklogSprintStatus | null;
    tasks: BacklogTask[];
}

export interface BacklogEpic {
    id: string;
    title: string;
    story_points: number | null;
}
