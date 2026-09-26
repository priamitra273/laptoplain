<script setup lang="ts">
import FilterResetButton from '@/components/form/FilterResetButton.vue';
import { computed } from 'vue';
import type { WorkloadSortColumn } from './types';
import WorkloadStatusTabs from './WorkloadStatusTabs.vue';

defineProps<{
    hasFilters: boolean;
}>();

const emit = defineEmits<{
    clear: [];
}>();

const search = defineModel<string>('search', { required: true });
const statuses = defineModel<number[]>('statuses', { required: true });
const sort = defineModel<WorkloadSortColumn>('sort', { required: true });
const direction = defineModel<'asc' | 'desc'>('direction', { required: true });

const sortOptions: { label: string; icon: string; column: WorkloadSortColumn; direction: 'asc' | 'desc' }[] = [
    { label: 'Most tasks', icon: 'i-lucide-arrow-down-wide-narrow', column: 'total_tasks', direction: 'desc' },
    { label: 'Least tasks', icon: 'i-lucide-arrow-up-narrow-wide', column: 'total_tasks', direction: 'asc' },
    { label: 'Highest remaining', icon: 'i-lucide-gauge', column: 'remaining_work_percent', direction: 'desc' },
    { label: 'Lowest remaining', icon: 'i-lucide-gauge', column: 'remaining_work_percent', direction: 'asc' },
    { label: 'Name A-Z', icon: 'i-lucide-arrow-down-a-z', column: 'name', direction: 'asc' },
    { label: 'Name Z-A', icon: 'i-lucide-arrow-up-z-a', column: 'name', direction: 'desc' },
];

const activeSort = computed(
    () => sortOptions.find((option) => option.column === sort.value && option.direction === direction.value) ?? sortOptions[0],
);

const sortItems = computed(() =>
    sortOptions.map((option) => ({
        label: option.label,
        icon: option.icon,
        type: 'checkbox' as const,
        checked: option === activeSort.value,
        onSelect: () => {
            sort.value = option.column;
            direction.value = option.direction;
        },
    })),
);
</script>

<template>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <WorkloadStatusTabs v-model="statuses" class="shrink-0" />

        <UInput v-model="search" icon="i-lucide-search" placeholder="Filter by name" class="w-full sm:w-72 lg:ms-2" />

        <div class="flex items-center gap-2 lg:ms-auto">
            <FilterResetButton v-if="hasFilters" @click="emit('clear')" />

            <UDropdownMenu :items="sortItems" :content="{ align: 'end' }">
                <UButton :icon="activeSort.icon" :label="activeSort.label" color="neutral" variant="outline" size="sm" class="rounded-lg" />
            </UDropdownMenu>
        </div>
    </div>
</template>
