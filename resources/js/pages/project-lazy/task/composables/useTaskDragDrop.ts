import type { LazyTaskFormatted, ListTask, TaskMovePayload } from '@/pages/project-lazy';
import { useToast } from 'primevue/usetoast';
import { computed, ref, type Ref } from 'vue';
import { computeMoveTarget, type DropMode } from '../../utils/computeMoveTarget';

interface DragDropContext {
    tree: Ref<ListTask[]>;
    canMove: Ref<boolean>;
    isDescendant: (sourceId: string, targetId: string) => boolean;
    onMove: (payload: TaskMovePayload) => void;
}

const TASK_DRAG_MIME = 'application/x-task-id';
const TASK_DRAG_TEXT_MIME = 'text/plain';

export const useTaskDragDrop = ({ tree, canMove, isDescendant, onMove }: DragDropContext) => {
    const toast = useToast();

    const draggedKey = ref<string | null>(null);
    const dropTargetKey = ref<string | null>(null);
    const dropMode = ref<DropMode | null>(null);
    const pointerOnRootDropzone = ref(false);

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
        if (!element) {
            return 'inside';
        }
        const rect = element.getBoundingClientRect();
        const ratio = rect.height > 0 ? (event.clientY - rect.top) / rect.height : 0.5;
        if (ratio < 0.3) {
            return 'before';
        }
        if (ratio > 0.7) {
            return 'after';
        }
        return 'inside';
    };

    const wouldCreateCycle = (parentId: string | null): boolean => {
        if (!parentId || !draggedKey.value) {
            return false;
        }
        return parentId === draggedKey.value || isDescendant(draggedKey.value, parentId);
    };

    const onDragStart = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value) {
            event.preventDefault();
            return;
        }
        draggedKey.value = node.key;
        dropTargetKey.value = null;
        dropMode.value = null;
        if (event.dataTransfer) {
            event.dataTransfer.setData(TASK_DRAG_TEXT_MIME, node.key);
            try {
                event.dataTransfer.setData(TASK_DRAG_MIME, node.key);
            } catch {
                /* ignore */
            }
            event.dataTransfer.effectAllowed = 'move';
        }
    };

    const onDragEnd = (): void => {
        resetDragState();
    };

    const onRowDragOver = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value || !draggedKey.value || node.key === draggedKey.value) {
            return;
        }
        event.preventDefault();
        pointerOnRootDropzone.value = false;
        dropTargetKey.value = node.key;
        dropMode.value = zoneFromEvent(event);
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    };

    const onRowDrop = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        event.preventDefault();

        const mode = dropMode.value ?? 'inside';
        const target = computeMoveTarget(tree.value, draggedKey.value, node.key, mode);
        if (!target) {
            resetDragState();
            return;
        }
        if (wouldCreateCycle(target.parentId)) {
            toast.add({ severity: 'warn', summary: 'Invalid move', detail: 'Cannot move task under its own descendant.', life: 2500 });
            resetDragState();
            return;
        }

        onMove({ taskId: draggedKey.value, parentId: target.parentId, position: target.position });
        resetDragState();
    };

    const onRootDragOver = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        const targetEl = event.target as Element | null;
        if (targetEl?.closest('[data-task-drop-row="true"]')) {
            return;
        }
        event.preventDefault();
        pointerOnRootDropzone.value = true;
        dropTargetKey.value = null;
        dropMode.value = null;
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    };

    const onRootDrop = (event: DragEvent): void => {
        if (!canMove.value || !draggedKey.value) {
            return;
        }
        event.preventDefault();
        const position = tree.value.filter((task) => task.id !== draggedKey.value).length;
        onMove({ taskId: draggedKey.value, parentId: null, position });
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
    };
};
