import type { PrimeSeverity } from '@/types';

export interface SprintStatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface SprintOption {
    id: string;
    project_id: string;
    name: string;
    goal: string | null;
    duration: string | null;
    start_date: string | null;
    end_date: string | null;
    order: number;
    retrospective: string | null;
    status: SprintStatusOption | null;
}

export interface BurndownPoint {
    date: string;
    label: string;
    total_plan: number;
    total_actual: number;
}

export interface ReportTaskBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface ReportTaskUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}

export interface ReportTask {
    id: string;
    key: string;
    title: string;
    status: ReportTaskBadge | null;
    priority: ReportTaskBadge | null;
    category: ReportTaskBadge | null;
    rootAncestor: ReportTask | null;
    users: ReportTaskUser[] | null;
    story_points: number | null;
}

export interface SprintStatusReport {
    completed_tasks: ReportTask[];
    incomplete_tasks: ReportTask[];
}
