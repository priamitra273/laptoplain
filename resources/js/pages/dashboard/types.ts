import type { PrimeSeverity } from '@/types';

export interface Labelled {
    name: string;
    severity: PrimeSeverity | null;
}

export interface Assignee {
    id: string;
    name: string;
}

export interface TaskProject {
    id: string;
    title: string;
    emoji?: string | null;
}

export interface TaskRow {
    id: string;
    title: string;
    due_date?: string | null;
    completed_at?: string | null;
    status?: Labelled | null;
    priority?: Labelled | null;
    project?: TaskProject | null;
    assignees: Assignee[];
    is_assigned: boolean;
    is_created_by_me: boolean;
}

export interface ProjectRow {
    id: string;
    title: string;
    project_no?: string | null;
    emoji?: string | null;
    due_date?: string | null;
    completed_at?: string | null;
    progress?: number | null;
    status?: Labelled | null;
    priority?: Labelled | null;
}

export interface StatusCount {
    name: string;
    severity: PrimeSeverity | null;
    count: number;
}

export interface Member {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

export interface TaskStats {
    total: number;
    progress: number;
    overdue: number;
    dueSoon: number;
    byStatus: StatusCount[];
}

export interface ProjectStats {
    total: number;
    progress: number;
    byStatus: StatusCount[];
}

export interface DashboardStats {
    tasks: TaskStats;
    projects: ProjectStats;
}
