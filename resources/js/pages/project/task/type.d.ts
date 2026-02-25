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
