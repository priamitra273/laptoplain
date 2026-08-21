import { computed, ref, type Ref } from 'vue';
import { computeMoveTarget, type DropMode } from './computeMoveTarget';
import type { ListTask, TaskMovePayload } from './types';

const findNode = (list: ListTask[], id: string): ListTask | null => {
    for (const item of list) {
        if (item.id === id) return item;
        const found = findNode(item.sub_task_recursive, id);
        if (found) return found;
    }
    return null;
};

const isDescendant = (tree: ListTask[], sourceId: string, targetId: string): boolean => {
    const source = findNode(tree, sourceId);
    if (!source) return false;

    const walk = (nodes: ListTask[]): boolean => nodes.some((node) => node.id === targetId || walk(node.sub_task_recursive));

    return walk(source.sub_task_recursive);
};

const removeFrom = (list: ListTask[], id: string): ListTask | null => {
    for (let i = 0; i < list.length; i++) {
        if (list[i].id === id) {
            return list.splice(i, 1)[0];
        }
        const removed = removeFrom(list[i].sub_task_recursive, id);
        if (removed) return removed;
    }
    return null;
};

const insertAt = (tree: ListTask[], item: ListTask, parentId: string | null, position: number): void => {
    const list = parentId ? (findNode(tree, parentId)?.sub_task_recursive ?? null) : tree;
    if (!list) {
        tree.push(item);
        return;
    }
    list.splice(Math.max(0, Math.min(position, list.length)), 0, item);
};

interface DragDropContext {
    tree: Ref<ListTask[]>;
    canMove: Ref<boolean>;
    onMove: (payload: TaskMovePayload) => void;
}

const TASK_DRAG_MIME = 'text/plain';

export const useTaskDragDrop = ({ tree, canMove, onMove }: DragDropContext) => {
    const toast = useToast();

    const draggedKey = ref<string | null>(null);
    const dropTargetKey = ref<string | null>(null);
    const dropMode = ref<DropMode | null>(null);
    const pointerOnRootDropzone = ref(false);
    let preDragSnapshot: ListTask[] | null = null;

    const restoreSnapshot = (): void => {
        if (preDragSnapshot) {
            tree.value = preDragSnapshot;
            preDragSnapshot = null;
        }
    };

    const isDraggingTask = computed(() => !!draggedKey.value);
    const isRootDropActive = computed(() => isDraggingTask.value && pointerOnRootDropzone.value && !dropTargetKey.value);

    const resetDragState = (): void => {
        draggedKey.value = null;
        dropTargetKey.value = null;
        dropMode.value = null;
        pointerOnRootDropzone.value = false;
    };

    const zoneFromEvent = (event: DragEvent): DropMode => {
        const element = event.currentTarget as HTMLElement | null;
        if (!element) return 'inside';
        const rect = element.getBoundingClientRect();
        const ratio = rect.height > 0 ? (event.clientY - rect.top) / rect.height : 0.5;
        if (ratio < 0.3) return 'before';
        if (ratio > 0.7) return 'after';
        return 'inside';
    };

    const wouldCreateCycle = (parentId: string | null): boolean => {
        if (!parentId || !draggedKey.value) return false;
        return parentId === draggedKey.value || isDescendant(tree.value, draggedKey.value, parentId);
    };

    const onDragStart = (event: DragEvent, node: ListTask): void => {
        if (!canMove.value) {
            event.preventDefault();
            return;
        }
        draggedKey.value = node.id;
        dropTargetKey.value = null;
        dropMode.value = null;
        preDragSnapshot = JSON.parse(JSON.stringify(tree.value));
        if (event.dataTransfer) {
            event.dataTransfer.setData(TASK_DRAG_MIME, node.id);
            event.dataTransfer.effectAllowed = 'move';
        }
    };

    const onDragEnd = (): void => {
        resetDragState();
    };

    const onRowDragOver = (event: DragEvent, node: ListTask): void => {
        if (!canMove.value || !draggedKey.value || node.id === draggedKey.value) return;
        event.preventDefault();
        pointerOnRootDropzone.value = false;
        dropTargetKey.value = node.id;
        dropMode.value = zoneFromEvent(event);
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    };

    /** Optimistically splices the local tree, then hands off to the caller to persist it. */
    const applyMove = (draggedId: string, parentId: string | null, position: number): void => {
        const node = removeFrom(tree.value, draggedId);
        if (!node) return;
        node.parent_id = parentId;
        insertAt(tree.value, node, parentId, position);
        tree.value = [...tree.value];

        onMove({ taskId: draggedId, parentId, position });
    };

    const onRowDrop = (event: DragEvent, node: ListTask): void => {
        if (!canMove.value || !draggedKey.value) return;
        event.preventDefault();
        event.stopPropagation();

        const mode = dropMode.value ?? 'inside';
        const target = computeMoveTarget(tree.value, draggedKey.value, node.id, mode);
        if (!target) {
            resetDragState();
            return;
        }
        if (wouldCreateCycle(target.parentId)) {
            toast.add({ title: 'Invalid move', description: 'Cannot move a task under its own descendant.', color: 'warning' });
            resetDragState();
            return;
        }

        applyMove(draggedKey.value, target.parentId, target.position);
        resetDragState();
    };

    const onRootDragOver = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) return;
        const targetEl = event.target as Element | null;
        if (targetEl?.closest('[data-task-drop-row="true"]')) return;
        event.preventDefault();
        pointerOnRootDropzone.value = true;
        dropTargetKey.value = null;
        dropMode.value = null;
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    };

    const onRootDrop = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) return;
        event.preventDefault();
        const position = tree.value.filter((task) => task.id !== draggedKey.value).length;
        applyMove(draggedKey.value, null, position);
        resetDragState();
    };

    return {
        draggedKey,
        dropTargetKey,
        dropMode,
        isDraggingTask,
        isRootDropActive,
        onDragStart,
        onDragEnd,
        onRowDragOver,
        onRowDrop,
        onRootDragOver,
        onRootDrop,
        restoreSnapshot,
    };
};
