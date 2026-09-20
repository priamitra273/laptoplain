import { computed, ref } from 'vue';
import { computeTaskMove } from './taskTree';
import type { DropMode, ListTask, TaskMove } from './types';

/** Delegasi event di wadah tabel menjaga semua sel menjadi drop zone tanpa API PrimeVue. */
export function useTaskDragDrop(tasks: () => ListTask[], enabled: () => boolean, move: (value: TaskMove) => void) {
    const sourceId = ref<string | null>(null);
    const targetId = ref<string | null>(null);
    const mode = ref<DropMode | null>(null);
    const reset = () => {
        sourceId.value = null;
        targetId.value = null;
        mode.value = null;
    };
    const rowFor = (event: DragEvent) => (event.target instanceof Element ? event.target.closest('tr') : null);
    const idFor = (row: Element | null) => row?.querySelector<HTMLElement>('[data-task-id]')?.dataset.taskId ?? null;
    const start = (event: DragEvent) => {
        const grip = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-task-id]') : null;
        if (!enabled() || !grip) {
            event.preventDefault();
            return;
        }
        sourceId.value = grip.dataset.taskId ?? null;
        if (event.dataTransfer && sourceId.value) {
            event.dataTransfer.setData('text/plain', sourceId.value);
            event.dataTransfer.effectAllowed = 'move';
        }
    };
    const over = (event: DragEvent) => {
        if (!enabled() || !sourceId.value) return;
        const row = rowFor(event);
        const id = idFor(row);
        const root = event.target instanceof Element && !!event.target.closest('[data-root-drop]');
        const rect = row?.getBoundingClientRect();
        const fraction = rect ? (event.clientY - rect.top) / rect.height : 0;
        const nextMode: DropMode = root ? 'root' : fraction < 0.3 ? 'before' : fraction > 0.7 ? 'after' : 'inside';
        if ((!id && !root) || !computeTaskMove(tasks(), sourceId.value, id, nextMode)) {
            targetId.value = null;
            mode.value = null;
            return;
        }
        event.preventDefault();
        targetId.value = id;
        mode.value = nextMode;
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    };
    const drop = (event: DragEvent) => {
        over(event);
        if (enabled() && sourceId.value && mode.value) {
            const result = computeTaskMove(tasks(), sourceId.value, targetId.value, mode.value);
            if (result) {
                event.preventDefault();
                move(result);
            }
        }
        reset();
    };
    const leave = (event: DragEvent) => {
        if (event.currentTarget instanceof Node && event.relatedTarget instanceof Node && event.currentTarget.contains(event.relatedTarget)) return;
        targetId.value = null;
        mode.value = null;
    };
    return { sourceId, targetId, mode, dragging: computed(() => sourceId.value !== null), start, over, drop, reset, leave };
}
