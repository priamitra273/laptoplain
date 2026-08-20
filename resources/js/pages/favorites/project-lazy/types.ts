import type { PrimeSeverity } from '@/types';

export interface ShellBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface ShellProject {
    id: string;
    project_no: string | null;
    title: string;
    emoji: string | null;
    progress: number;
    start_date: string | null;
    due_date: string | null;
    status_id: string | null;
    priority_id: string | null;
    status: ShellBadge | null;
    priority: ShellBadge | null;
}

export interface ShellMember {
    id: string;
    user: {
        id: string;
        name: string;
        email: string;
        avatar_url?: string | null;
    };
}

export type ShellStatusOption = ShellBadge;
export type ShellPriorityOption = ShellBadge;

export interface ShellProps {
    project: ShellProject;
    members: ShellMember[];
    statuses: ShellStatusOption[];
    priorities: ShellPriorityOption[];
    isMember: boolean;
    policy: App.Data.ProjectRole.ConfigData | null;
}
