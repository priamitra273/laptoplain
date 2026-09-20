import type { PrimeSeverity } from '@/types';

export interface WorkloadUser {
    id: string;
    name: string;
    avatar_url?: string | null;
    remaining_work_percent: number;
    workload_status: string;
    total_tasks: number;
}

export interface WorkloadUserOption {
    id: string;
    name: string;
    avatar_url?: string | null;
}

export interface WorkloadStatusOption {
    id: number;
    name: string;
    severity: PrimeSeverity;
}

export interface WorkloadSummary {
    total_users: number;
    free: number;
    light: number;
    moderate: number;
    busy: number;
}

export type WorkloadSortColumn = 'name' | 'total_tasks' | 'remaining_work_percent';

export interface WorkloadFilters {
    names?: string[] | null;
    workload_statuses?: number[] | null;
    search?: string | null;
    per_page: number;
    sort: WorkloadSortColumn;
    direction: 'asc' | 'desc';
}

export interface WorkloadPaginator {
    data: WorkloadUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

/** Satu segmen batang distribusi; urutannya mengikuti tingkat keparahan. */
export interface DistributionSegment {
    id: number;
    label: string;
    count: number;
    percent: number;
    severity: PrimeSeverity;
    active: boolean;
}
