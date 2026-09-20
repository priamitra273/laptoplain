import type { PrimeSeverity } from '@/types';

export interface MyTaskBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface MyTaskStatus extends MyTaskBadge {
    score: number | null;
}

export interface MyTaskProject {
    id: string;
    title: string;
}

export interface MyTaskUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}

/**
 * Cermin `AssignedTaskData` di server. Sengaja ramping: tanpa description, tags, media, dan
 * progress, dan subtask hanya dibawa sebagai jumlah — bukan pohon seperti tab List/Kanban project.
 */
export interface AssignedTask {
    id: string;
    title: string;
    due_date: string | null;
    is_overdue: boolean;
    sequence_number: number | null;
    status: MyTaskStatus | null;
    priority: MyTaskBadge | null;
    type: MyTaskBadge | null;
    project: MyTaskProject | null;
    users: MyTaskUser[];
    sub_task_count: number;
    sub_task_done_count: number;
}

/** Filter yang dikembalikan server supaya kontrol di klien bisa dihidrasi ulang setelah reload. */
export interface MyTaskFilters {
    search: string | null;
    project_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    type_id: string | null;
    per_page: number;
}

export interface MyTaskSummaryEntry extends MyTaskBadge {
    count: number;
}

/** Satu kolom board. `total` dan `has_more` milik kolom itu sendiri — paginasinya per kolom. */
export interface MyTaskBoardColumn {
    status: MyTaskStatus;
    tasks: AssignedTask[];
    total: number;
    has_more: boolean;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

/** Filter aktif dalam bentuk query string, diteruskan ke permintaan "Load more" per kolom. */
export type MyTaskQueryParams = Record<string, string>;
