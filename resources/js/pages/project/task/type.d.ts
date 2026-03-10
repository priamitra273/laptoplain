import { PrimeSeverity } from '@/types';
import type { Task } from '..';

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
