import type { PrimeSeverity } from '@/types';

export interface BoardBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface BoardUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

/**
 * Bentuk minimum yang dirender kartu board. Sengaja sesempit ini supaya kedua sumber data
 * memenuhinya — `AssignedTaskData` (My Task) tidak punya `code`, `comments_count`, maupun
 * `sub_task_recursive` yang dipakai `TaskCardData` di Kanban project. Yang khas per halaman
 * masuk lewat slot, bukan lewat prop tambahan.
 */
export interface BoardCardTask {
    id: string;
    title: string;
    due_date: string | null;
    is_overdue: boolean;
    priority: BoardBadge | null;
    type: BoardBadge | null;
    users: BoardUser[];
}

/** Satu aksi kartu. Bentuknya sengaja cocok dengan item `UContextMenu` sekaligus prop `UButton`. */
export interface BoardCardAction {
    label: string;
    icon: string;
    color?: 'error';
    onSelect: () => void;
}
