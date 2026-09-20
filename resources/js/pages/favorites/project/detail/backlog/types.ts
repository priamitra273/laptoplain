import type { PrimeSeverity } from '@/types';

export interface BacklogBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface BacklogCategory extends BacklogBadge {
    icon: string | null;
}

export interface BacklogUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}

export interface BacklogTask {
    id: string;
    parent_id: string | null;
    title: string;
    story_points: number | null;
    status: BacklogBadge | null;
    priority: BacklogBadge | null;
    category: BacklogCategory | null;
    users: BacklogUser[];
}

export interface BacklogSprintStatus {
    id: string;
    name: string;
    severity: string | null;
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
