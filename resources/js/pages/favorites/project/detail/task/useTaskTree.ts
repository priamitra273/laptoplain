import type { ExpandedState } from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';
import { branchIds, filterTaskTree, hasTaskFilters, taskTotals, visibleTaskCount } from './taskTree';
import type { ListTask, TaskFilters } from './types';

export function useTaskTree(tasks: () => ListTask[], filters: () => TaskFilters) {
    const expanded = ref<ExpandedState>({});
    let previousExpanded: ExpandedState = {};
    const filtered = computed(() => filterTaskTree(tasks(), filters()));
    const filtering = computed(() => hasTaskFilters(filters()));
    const branches = computed(() => branchIds(filtered.value.tree));
    const allExpanded = computed(
        () => branches.value.length > 0 && (expanded.value === true || branches.value.every((id) => expanded.value !== true && expanded.value[id])),
    );
    watch(
        () => JSON.stringify(filters()),
        (_, previous) => {
            const wasFiltering = previous ? hasTaskFilters(JSON.parse(previous)) : false;
            if (filtering.value) {
                if (!wasFiltering) previousExpanded = expanded.value === true ? true : { ...expanded.value };
                expanded.value = true;
            } else if (wasFiltering) expanded.value = previousExpanded;
        },
        { immediate: true },
    );
    return {
        expanded,
        filtered,
        filtering,
        allExpanded,
        hasBranches: computed(() => branches.value.length > 0),
        totals: computed(() => taskTotals(tasks())),
        visibleCount: computed(() => visibleTaskCount(filtered.value.tree, expanded.value)),
        toggleAll: () => {
            expanded.value = allExpanded.value ? {} : true;
        },
    };
}
