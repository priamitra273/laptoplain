import type { ListTask, SavedTaskPayload } from '@/pages/project-lazy';
import { computeOverdue, isCompletedStatus } from './nodeProgress';

export const buildListTaskNode = (payload: SavedTaskPayload): ListTask => {
    const now = new Date().toISOString();
    return {
        id: payload.id,
        parent_id: payload.parentId,
        title: payload.title,
        progress: payload.status?.score ?? 0,
        start_date: payload.startDate,
        due_date: payload.dueDate,
        completed_at: isCompletedStatus(payload.status) ? now : null,
        is_overdue: computeOverdue(payload.dueDate, payload.status),
        created_at: now,
        updated_at: now,
        status: payload.status,
        type: payload.type,
        category: payload.category,
        users: payload.users,
        sub_task_recursive: [],
    };
};

export const patchListTaskNode = (node: ListTask, payload: SavedTaskPayload): void => {
    node.title = payload.title;
    node.parent_id = payload.parentId;
    node.start_date = payload.startDate;
    node.due_date = payload.dueDate;
    node.status = payload.status;
    node.progress = payload.status?.score ?? 0;
    node.type = payload.type;
    node.category = payload.category;
    node.users = payload.users;
    node.completed_at = isCompletedStatus(payload.status) ? (node.completed_at ?? new Date().toISOString()) : null;
    node.is_overdue = computeOverdue(payload.dueDate, payload.status);
    node.updated_at = new Date().toISOString();
};
