import type { LazyTaskFormatted } from '@/pages/project-lazy';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

interface DragDropContext {
    projectId: string;
    expandedKeys: Ref<Record<string, boolean>>;
    canMove: Ref<boolean>;
    isDescendant: (sourceId: string, targetId: string) => boolean;
}

const DRAG_HOLD_MS = 900;
const AUTO_EXPAND_DELAY_MS = 500;
const TASK_DRAG_MIME = 'application/x-task-id';
const TASK_DRAG_TEXT_MIME = 'text/plain';
const TASK_DRAG_LEGACY_TEXT_MIME = 'text';

export const useTaskDragDrop = ({ projectId, expandedKeys, canMove, isDescendant }: DragDropContext) => {
    const toast = useToast();

    const draggedTaskId = ref<string | null>(null);
    const dropTargetTaskId = ref<string | null>(null);
    const updateParentLoading = ref(false);
    const pointerDraggedTaskId = ref<string | null>(null);
    const pointerOnRootDropzone = ref(false);
    const dragArmedTaskId = ref<string | null>(null);
    const holdCandidateTaskId = ref<string | null>(null);
    const holdTimerId = ref<ReturnType<typeof setTimeout> | null>(null);
    const autoExpandTargetKey = ref<string | null>(null);
    const autoExpandTimerId = ref<ReturnType<typeof setTimeout> | null>(null);

    const canMoveTask = canMove;

    const activeDragTaskId = computed(() => pointerDraggedTaskId.value || draggedTaskId.value || dragArmedTaskId.value);
    const isDraggingTask = computed(() => !!activeDragTaskId.value);
    const isRootDropActive = computed(() => isDraggingTask.value && pointerOnRootDropzone.value && !dropTargetTaskId.value);

    const clearAutoExpandSchedule = (): void => {
        if (autoExpandTimerId.value) {
            clearTimeout(autoExpandTimerId.value);
            autoExpandTimerId.value = null;
        }
        autoExpandTargetKey.value = null;
    };

    const scheduleAutoExpand = (node: LazyTaskFormatted): void => {
        const hasChildren = Array.isArray(node.children) && node.children.length > 0;
        if (!hasChildren || expandedKeys.value[node.key]) {
            clearAutoExpandSchedule();
            return;
        }
        if (autoExpandTargetKey.value === node.key && autoExpandTimerId.value) {
            return;
        }
        clearAutoExpandSchedule();
        autoExpandTargetKey.value = node.key;
        autoExpandTimerId.value = setTimeout(() => {
            expandedKeys.value = { ...expandedKeys.value, [node.key]: true };
            clearAutoExpandSchedule();
        }, AUTO_EXPAND_DELAY_MS);
    };

    const resetDragState = (): void => {
        if (holdTimerId.value) {
            clearTimeout(holdTimerId.value);
            holdTimerId.value = null;
        }
        clearAutoExpandSchedule();
        draggedTaskId.value = null;
        dropTargetTaskId.value = null;
        pointerDraggedTaskId.value = null;
        pointerOnRootDropzone.value = false;
        dragArmedTaskId.value = null;
        holdCandidateTaskId.value = null;
    };

    const getDraggedTaskIdFromEvent = (event: DragEvent): string | null => {
        const transfer = event.dataTransfer;
        if (!transfer) {
            return draggedTaskId.value;
        }
        const candidates = [TASK_DRAG_MIME, TASK_DRAG_TEXT_MIME, TASK_DRAG_LEGACY_TEXT_MIME];
        for (const mime of candidates) {
            try {
                const value = transfer.getData(mime)?.trim();
                if (value) {
                    return value;
                }
            } catch {
                /* ignore */
            }
        }
        return draggedTaskId.value;
    };

    const hasTaskDragPayload = (event: DragEvent): boolean => {
        if (draggedTaskId.value) {
            return true;
        }
        const transfer = event.dataTransfer;
        if (!transfer?.types) {
            return false;
        }
        const types = Array.from(transfer.types);
        return [TASK_DRAG_MIME, TASK_DRAG_TEXT_MIME, TASK_DRAG_LEGACY_TEXT_MIME].some((mime) => types.includes(mime));
    };

    const updateTaskParent = async (taskId: string, parentId: string | null): Promise<void> => {
        if (updateParentLoading.value) {
            return;
        }
        updateParentLoading.value = true;
        try {
            await axios.put(
                route('project.tasks.parent.update', {
                    projectEncoded: projectId,
                    task: taskId,
                }),
                { parent_id: parentId },
            );
            toast.add({ severity: 'success', summary: 'Success', detail: 'Task parent updated successfully.', life: 2200 });
            router.reload();
        } catch (error: any) {
            const message = error?.response?.data?.message || 'Failed to update task parent.';
            toast.add({ severity: 'error', summary: 'Error', detail: message, life: 3000 });
        } finally {
            updateParentLoading.value = false;
            resetDragState();
        }
    };

    const moveTaskWithValidation = async (sourceTaskId: string | null, targetTaskId: string | null): Promise<void> => {
        if (!sourceTaskId) {
            resetDragState();
            return;
        }
        if (targetTaskId && sourceTaskId === targetTaskId) {
            resetDragState();
            return;
        }
        if (targetTaskId && isDescendant(sourceTaskId, targetTaskId)) {
            toast.add({ severity: 'warn', summary: 'Invalid move', detail: 'Cannot move task under its own descendant.', life: 2500 });
            resetDragState();
            return;
        }
        await updateTaskParent(sourceTaskId, targetTaskId);
    };

    const onPointerDragStart = (node: LazyTaskFormatted): void => {
        if (!canMoveTask.value) {
            return;
        }
        pointerDraggedTaskId.value = node.key;
        pointerOnRootDropzone.value = false;
        draggedTaskId.value = node.key;
        dropTargetTaskId.value = null;
    };

    const onHandleDragStart = (event: DragEvent, node: LazyTaskFormatted): void => {
        if (!canMoveTask.value || dragArmedTaskId.value !== node.key) {
            event.preventDefault();
            return;
        }

        onPointerDragStart(node);
        draggedTaskId.value = node.key;
        dropTargetTaskId.value = null;
        if (event.dataTransfer) {
            event.dataTransfer.setData(TASK_DRAG_TEXT_MIME, node.key);
            event.dataTransfer.setData(TASK_DRAG_LEGACY_TEXT_MIME, node.key);
            try {
                event.dataTransfer.setData(TASK_DRAG_MIME, node.key);
            } catch {
                /* ignore */
            }
            event.dataTransfer.effectAllowed = 'move';
        }
    };

    const onHandleDragEnd = (): void => {
        resetDragState();
    };

    const onRowDragOver = (event: DragEvent, targetNode: LazyTaskFormatted): void => {
        if (!canMoveTask.value) {
            return;
        }
        if (!hasTaskDragPayload(event)) {
            return;
        }
        const sourceTaskId = getDraggedTaskIdFromEvent(event);
        if (sourceTaskId && targetNode.key === sourceTaskId) {
            return;
        }
        event.preventDefault();
        if (!draggedTaskId.value) {
            draggedTaskId.value = sourceTaskId;
        }
        pointerOnRootDropzone.value = false;
        dropTargetTaskId.value = targetNode.key;
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
        scheduleAutoExpand(targetNode);
    };

    const onRowDrop = async (event: DragEvent, targetNode: LazyTaskFormatted): Promise<void> => {
        if (!canMoveTask.value) {
            return;
        }
        event.preventDefault();
        const sourceTaskId = getDraggedTaskIdFromEvent(event);
        await moveTaskWithValidation(sourceTaskId, targetNode.key);
    };

    const onRootDragOver = (event: DragEvent): void => {
        if (!canMoveTask.value) {
            return;
        }
        if (!hasTaskDragPayload(event)) {
            return;
        }
        const target = event.target as Element | null;
        const insideTaskRow = !!target?.closest('[data-task-drop-row="true"]');
        if (insideTaskRow) {
            return;
        }
        const sourceTaskId = getDraggedTaskIdFromEvent(event);
        event.preventDefault();
        if (!draggedTaskId.value && sourceTaskId) {
            draggedTaskId.value = sourceTaskId;
        }
        pointerOnRootDropzone.value = true;
        dropTargetTaskId.value = null;
        clearAutoExpandSchedule();
    };

    const onRootDrop = async (event: DragEvent): Promise<void> => {
        if (!canMoveTask.value) {
            return;
        }
        event.preventDefault();
        const sourceTaskId = getDraggedTaskIdFromEvent(event);
        await moveTaskWithValidation(sourceTaskId, null);
    };

    const cancelPointerHold = (): void => {
        if (holdTimerId.value) {
            clearTimeout(holdTimerId.value);
            holdTimerId.value = null;
        }
        holdCandidateTaskId.value = null;
        if (!pointerDraggedTaskId.value) {
            dragArmedTaskId.value = null;
        }
    };

    const onPointerHoldStart = (node: LazyTaskFormatted): void => {
        if (!canMoveTask.value) {
            return;
        }
        cancelPointerHold();
        holdCandidateTaskId.value = node.key;
        holdTimerId.value = setTimeout(() => {
            if (holdCandidateTaskId.value !== node.key) {
                return;
            }
            dragArmedTaskId.value = node.key;
            onPointerDragStart(node);
        }, DRAG_HOLD_MS);
    };

    const onPointerRowEnter = (node: LazyTaskFormatted): void => {
        if (!pointerDraggedTaskId.value) {
            return;
        }
        pointerOnRootDropzone.value = false;
        dropTargetTaskId.value = node.key === pointerDraggedTaskId.value ? null : node.key;
        scheduleAutoExpand(node);
    };

    const onPointerRootEnter = (): void => {
        if (!pointerDraggedTaskId.value) {
            return;
        }
        pointerOnRootDropzone.value = true;
        dropTargetTaskId.value = null;
        clearAutoExpandSchedule();
    };

    const onPointerRootLeave = (): void => {
        if (!pointerDraggedTaskId.value) {
            return;
        }
        pointerOnRootDropzone.value = false;
    };

    const onPointerContainerMove = (event: MouseEvent): void => {
        if (!pointerDraggedTaskId.value) {
            return;
        }
        const target = event.target as Element | null;
        const insideTaskRow = !!target?.closest('[data-task-drop-row="true"]');
        if (insideTaskRow) {
            pointerOnRootDropzone.value = false;
            return;
        }
        onPointerRootEnter();
    };

    const finalizePointerDrag = async (): Promise<void> => {
        if (!pointerDraggedTaskId.value) {
            return;
        }
        const sourceTaskId = pointerDraggedTaskId.value;
        const targetTaskId = pointerOnRootDropzone.value ? null : dropTargetTaskId.value;
        await moveTaskWithValidation(sourceTaskId, targetTaskId);
    };

    const onGlobalMouseUp = (): void => {
        void finalizePointerDrag();
        cancelPointerHold();
    };

    onMounted(() => {
        window.addEventListener('mouseup', onGlobalMouseUp);
    });
    onBeforeUnmount(() => {
        window.removeEventListener('mouseup', onGlobalMouseUp);
    });

    return {
        dropTargetTaskId,
        dragArmedTaskId,
        activeDragTaskId,
        isDraggingTask,
        isRootDropActive,
        canMoveTask,
        onHandleDragStart,
        onHandleDragEnd,
        onRowDragOver,
        onRowDrop,
        onRootDragOver,
        onRootDrop,
        onPointerHoldStart,
        cancelPointerHold,
        onPointerRowEnter,
        onPointerRootLeave,
        onPointerContainerMove,
    };
};
