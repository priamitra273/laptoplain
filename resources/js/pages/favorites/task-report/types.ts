import type { PrimeSeverity } from '@/types';

export interface TaskReportOption {
    id: string;
    name: string;
    severity?: PrimeSeverity | null;
    avatar_url?: string | null;
}

export interface TaskReportBadge {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskReportCreator {
    id: string;
    name: string;
    avatar_url?: string | null;
}

export interface TaskReportProject {
    id: string;
    title: string;
    status: TaskReportBadge | null;
}

export interface TaskReportTask {
    id: string;
    title: string;
    description?: string | null;
    summary: string;
    creator: TaskReportCreator | null;
    status: TaskReportBadge | null;
    priority: TaskReportBadge | null;
    type: TaskReportBadge | null;
    project: TaskReportProject | null;
    start_date: string | null;
    due_date: string | null;
    progress: number | null;
    created_at: string;
    updated_at: string;
}

export interface TaskReportFilterOptions {
    creators: TaskReportOption[];
    statuses: TaskReportOption[];
    priorities: TaskReportOption[];
    types: TaskReportOption[];
    project_statuses: TaskReportOption[];
}

export interface TaskReportFilters {
    names?: string[];
    statuses?: string[];
    project_statuses?: string[];
    priorities?: string[];
    types?: string[];
    start_date_from?: string;
    start_date_to?: string;
    due_date_from?: string;
    due_date_to?: string;
    search?: string;
    per_page?: number;
}
