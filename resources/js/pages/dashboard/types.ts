import type { PrimeSeverity } from '@/types';

export interface DashboardActivity {
    values: { date: string; count: number }[];
    total_events: number;
    weeks: number;
    busiest_date: string | null;
    busiest_count: number;
    current_streak: number;
    weekly_average: number;
}

export interface DashboardAttentionItem {
    id: string;
    title: string;
    due_date: string;
    days_remaining: number;
    open_subtasks: number;
    owner_name: string | null;
}

export interface DashboardProjectProgress {
    id: string;
    title: string;
    emoji: string | null;
    total_tasks: number;
    done_tasks: number;
    progress_percent: number;
    due_date: string | null;
    status_name: string | null;
    status_severity: string | null;
}

export interface DashboardLatestProject {
    id: string;
    title: string;
    description: string | null;
    created_at: string;
    members_count: number;
    status_name: string | null;
    status_severity: PrimeSeverity | null;
}
