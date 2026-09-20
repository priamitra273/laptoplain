import type { BacklogTask } from './types';

let draggedTask: BacklogTask | null = null;
let draggedFromSprintId: string | null = null;

export function setDraggedTask(task: BacklogTask | null, fromSprintId: string | null): void {
    draggedTask = task;
    draggedFromSprintId = fromSprintId;
}

export function takeDraggedTask(): { task: BacklogTask | null; fromSprintId: string | null } {
    const dragged = { task: draggedTask, fromSprintId: draggedFromSprintId };

    draggedTask = null;
    draggedFromSprintId = null;

    return dragged;
}
