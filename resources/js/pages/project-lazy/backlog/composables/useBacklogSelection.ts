import { computed, ref, type Ref } from 'vue';
import type { BacklogTask } from '@/pages/project-lazy';

/**
 * Multi-select state for the backlog board, scoped to the currently visible task ids.
 */
export function useTaskSelection(visibleTaskIds: Ref<string[]>) {
    const selectedTaskIds = ref<string[]>([]);

    const selectedCount = computed(() => selectedTaskIds.value.length);
    const isAllSelected = computed(() => visibleTaskIds.value.length > 0 && visibleTaskIds.value.every((id) => selectedTaskIds.value.includes(id)));

    const toggleTask = (task: BacklogTask, checked: boolean) => {
        const id = String(task.id);
        if (checked) {
            if (!selectedTaskIds.value.includes(id)) selectedTaskIds.value = [...selectedTaskIds.value, id];
            return;
        }
        selectedTaskIds.value = selectedTaskIds.value.filter((taskId) => taskId !== id);
    };

    const toggleMany = (taskIds: string[], checked: boolean) => {
        const next = new Set(selectedTaskIds.value);
        taskIds.forEach((id) => (checked ? next.add(id) : next.delete(id)));
        selectedTaskIds.value = Array.from(next);
    };

    const toggleAll = () => {
        selectedTaskIds.value = isAllSelected.value ? [] : [...visibleTaskIds.value];
    };

    const clear = () => {
        selectedTaskIds.value = [];
    };

    /** Drop ids that are no longer visible (e.g. after a move/refresh). */
    const prune = () => {
        const visible = new Set(visibleTaskIds.value);
        selectedTaskIds.value = selectedTaskIds.value.filter((id) => visible.has(id));
    };

    return { selectedTaskIds, selectedCount, isAllSelected, toggleTask, toggleMany, toggleAll, clear, prune };
}
