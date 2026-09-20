import type { PrimeSeverity } from '@/types';

export interface SprintOption {
    id: string;
    name: string;
    status: { name: string; severity: PrimeSeverity | null } | null;
    start_date: string | null;
    end_date: string | null;
}

export interface BurndownPoint {
    date: string;
    total_plan: number;
    total_actual: number;
}

export interface ReportTaskBadge {
    id: string;
    name: string;
    severity?: PrimeSeverity | null;
}

export interface ReportTask {
    id: string;
    key: string;
    title: string;
    category: { id: string; name: string; icon: string | null; severity: PrimeSeverity | null } | null;
    rootAncestor: { title: string } | null;
    status: ReportTaskBadge | null;
    users: { id: string; name: string; avatar_url?: string | null }[];
    story_points: number | null;
}

export type SprintStatusReport = Record<string, ReportTask[]>;
