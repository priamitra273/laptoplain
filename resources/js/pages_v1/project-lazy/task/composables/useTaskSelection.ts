import type { LazyTaskFormatted } from '@/pages/project-lazy';
import { computed, ref, type Ref } from 'vue';

type SelectionState = Record<string, { checked: boolean; partialChecked: boolean }>;

export const useTaskSelection = (formattedTasks: Ref<LazyTaskFormatted[]>) => {
    const selectedKey = ref<SelectionState>({});
    const expandedKeys = ref<Record<string, boolean>>({});

    const isAllSelected = computed(() => {
        if (!formattedTasks.value.length) {
            return false;
        }
        const allKeys: string[] = [];
        const collectKeys = (node: LazyTaskFormatted): void => {
            allKeys.push(node.key);
            if (node.children) {
                node.children.forEach(collectKeys);
            }
        };
        formattedTasks.value.forEach(collectKeys);
        return allKeys.every((key) => selectedKey.value[key]?.checked);
    });

    const hasSelectedTasks = computed(() => Object.keys(selectedKey.value).length > 0);

    const selectedIds = computed(() => Object.keys(selectedKey.value));

    const selectAll = (): void => {
        const keys: SelectionState = {};
        const mark = (node: LazyTaskFormatted): void => {
            keys[node.key] = { checked: true, partialChecked: false };
            if (node.children) {
                node.children.forEach(mark);
            }
        };
        formattedTasks.value.forEach(mark);
        selectedKey.value = { ...keys };
    };

    const clearSelection = (): void => {
        selectedKey.value = {};
    };

    const toggleSelectAll = (): void => {
        if (isAllSelected.value) {
            clearSelection();
        } else {
            selectAll();
        }
    };

    const setSelected = (next: SelectionState): void => {
        selectedKey.value = { ...next };
    };

    return {
        selectedKey,
        expandedKeys,
        isAllSelected,
        hasSelectedTasks,
        selectedIds,
        selectAll,
        clearSelection,
        toggleSelectAll,
        setSelected,
    };
};
