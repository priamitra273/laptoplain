import type { PrimeSeverity } from '@/types';

export interface ProjectBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface Project {
    id: string;
    project_no: string | null;
    title: string;
    emoji: string | null;
    description: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    status_id: string | null;
    priority_id: string | null;
    created_at: string | null;
}

export type ProjectStatusOption = ProjectBadge;
export type ProjectPriorityOption = ProjectBadge;
