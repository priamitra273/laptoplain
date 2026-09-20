import type { PrimeSeverity } from '@/types';

export interface KanbanBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface KanbanStatus extends KanbanBadge {
    score: number | null;
}

export interface KanbanUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

/**
 * Cerminan `TaskCardData` di server. Sengaja tidak memuat `tags`, yang tidak dikirim endpoint
 * kanban — menaruhnya di sini hanya akan menjanjikan data yang tidak pernah ada.
 */
export interface KanbanTask {
    id: string;

    /** Dirakit di server (`T-{id}`) karena id sudah ter-encode Sqids saat sampai di sini. */
    code: string;
    comments_count: number;
    parent_id: string | null;
    title: string;
    description: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    is_overdue: boolean;
    status: KanbanBadge | null;
    priority: KanbanBadge | null;
    type: KanbanBadge | null;
    users: KanbanUser[];
    sub_task_recursive: KanbanTask[];
}

export interface KanbanColumn {
    status: KanbanStatus;
    tasks: KanbanTask[];
}

/** Isian yang sudah diketik di kartu quick-add saat pengguna pindah ke form lengkap lewat "More options". */
export interface QuickAddDraft {
    title: string;
    type_id?: string;
    priority_id?: string;
    assign_users: string[];
    start_date: string;
    due_date: string;
}
