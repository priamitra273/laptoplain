import { PrimeSeverity } from '@/types';

export interface User {
    id: number;
    name: string;
    avatar_url?: string | null;
}

export interface TaskStatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskPriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskTypeOption {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface ProjectOptions {
    id: string;
    title: string;
}

export interface Task {
    id: string;
    title: string;
    due_date?: string;
    project?: ProjectOptions;
    status?: TaskStatusOption;
    priority?: TaskPriorityOption;
    type?: TaskTypeOption;
    is_assigned?: boolean;
    is_created_by_me?: boolean;
}

export interface TaskReportCreator {
    id: string | number;
    name: string;
    avatar_url?: string | null;
}

export interface TaskReportStatus {
    id: string | number;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskReportPriority {
    id: string | number;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskReportType {
    id: string | number;
    name: string;
    severity: PrimeSeverity;
}

export interface TaskReportProject {
    id: string;
    title: string;
}

export interface TaskReportTask {
    id: string;
    title: string;
    summary: string;
    creator: TaskReportCreator | null;
    status: TaskReportStatus | null;
    priority: TaskReportPriority | null;
    type: TaskReportType | null;
    project: TaskReportProject | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    created_at: string;
}

export interface TaskReportFilterOptions {
    project_statuses: TaskReportStatus[];
    creators: TaskReportCreator[];
    statuses: TaskReportStatus[];
    priorities: TaskReportPriority[];
    types: TaskReportType[];
}

export interface TaskReportPaginatedTasks {
    data: TaskReportTask[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface TaskReportFilters {
    names?: string[] | string;
    statuses?: string[] | string;
    project_statuses?: string[] | string;
    priorities?: string[] | string;
    types?: string[] | string;
    start_date_from?: string;
    start_date_to?: string;
    due_date_from?: string;
    due_date_to?: string;
    search?: string;
}

export interface TaskReportProps {
    tasks: TaskReportPaginatedTasks;
    project_statuses: TaskReportStatus[];
    filters: TaskReportFilters;
    filterOptions: TaskReportFilterOptions;
}
