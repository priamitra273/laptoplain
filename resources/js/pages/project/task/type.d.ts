import { PrimeSeverity } from '@/types';

export interface User {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

export interface TaskStatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity;
    score?: number;
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

export interface TaskCategory {
    id: string;
    name: string; // 'Epic' | 'Story' | 'Issue'
    icon?: string;
    severity?: number;
}

export interface ProjectOptions {
    id: string;
    title: string;
}

export interface Task {
    id: string;
    title: string;
    description?: string;
    due_date?: string;
    start_date?: string;
    progress?: number;
    parent_id?: string | null;
    project?: ProjectOptions;
    status?: TaskStatusOption;
    priority?: TaskPriorityOption;
    type?: TaskTypeOption;
    category?: TaskCategory;
    users?: User[];
    creator?: User;
    sub_task_recursive?: Task[];
    completed_at?: string | null;
    is_assigned?: boolean;
    is_created_by_me?: boolean;
    created_at?: string;
    updated_at?: string;
}

// ─── Sprint types ─────────────────────────────────────────────────────────────

export interface SprintStatus {
    id: string;
    name: string; // 'Planning' | 'Active' | 'Completed'
    severity?: number;
}

export interface Sprint {
    id: string;
    name: string;
    goal?: string;
    duration?: string;
    start_date?: string;
    end_date?: string;
    order?: number;
    retrospective?: string;
    status?: SprintStatus;
    tasks?: Task[];
}

// ─── Existing types (unchanged) ───────────────────────────────────────────────

export type TaskStatus = TaskStatusOption;
export type TaskPriority = TaskPriorityOption;
export type TaskType = TaskTypeOption;
