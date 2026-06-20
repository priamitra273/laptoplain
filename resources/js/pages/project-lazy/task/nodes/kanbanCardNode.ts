import type { KanbanCard, SavedTaskPayload } from '@/pages/project-lazy';
import { computeOverdue } from './nodeProgress';

export const buildKanbanCardNode = (payload: SavedTaskPayload): KanbanCard => ({
    id: payload.id,
    parent_id: payload.parentId,
    title: payload.title,
    description: null,
    start_date: payload.startDate,
    due_date: payload.dueDate,
    progress: payload.status?.score ?? 0,
    is_overdue: computeOverdue(payload.dueDate, payload.status),
    status: payload.status,
    priority: payload.priority,
    type: payload.type,
    users: payload.users,
    sub_task_recursive: [],
});

export const patchKanbanCardNode = (node: KanbanCard, payload: SavedTaskPayload): void => {
    node.title = payload.title;
    node.parent_id = payload.parentId;
    node.start_date = payload.startDate;
    node.due_date = payload.dueDate;
    node.status = payload.status;
    node.progress = payload.status?.score ?? 0;
    node.priority = payload.priority;
    node.type = payload.type;
    node.users = payload.users;
    node.is_overdue = computeOverdue(payload.dueDate, payload.status);
};
