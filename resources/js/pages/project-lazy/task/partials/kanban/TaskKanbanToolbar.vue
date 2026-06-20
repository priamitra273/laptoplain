<script setup lang="ts">
import { Label } from '@/components/ui/label';
import UserAvatar from '@/components/UserAvatar.vue';
import { computed, useTemplateRef } from 'vue';
import type { SlimUser, TaskPriorityOption, TaskTypeOption } from '../../..';

const props = defineProps<{
    allAssignees: SlimUser[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    totalTasks: number;
    doneTasks: number;
    boardProgress: number;
}>();

const searchQuery = defineModel<string>('searchQuery', { default: '' });
const filterAssignee = defineModel<string[]>('filterAssignee', { default: () => [] });
const filterPriority = defineModel<string[]>('filterPriority', { default: () => [] });
const filterType = defineModel<string[]>('filterType', { default: () => [] });

const op = useTemplateRef('op');

const hasActiveFilter = computed(() => {
    return !!searchQuery.value || filterAssignee.value.length > 0 || filterPriority.value.length > 0 || filterType.value.length > 0;
});

const countActiveFilter = computed(() => {
    return filterPriority.value.length + filterType.value.length;
});

const clearFilters = () => {
    searchQuery.value = '';
    filterAssignee.value = [];
    filterPriority.value = [];
    filterType.value = [];
};

const toggleAssignee = (id: string) => {
    if (filterAssignee.value.includes(id)) {
        filterAssignee.value = filterAssignee.value.filter((u) => u !== id);
    } else {
        filterAssignee.value = [...filterAssignee.value, id];
    }
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
            <i class="pi pi-search pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-surface-400" />
            <InputText v-model="searchQuery" placeholder="Search tasks…" class="!pl-7 !text-sm" style="height: 32px; width: 200px" />
        </div>

        <!-- Assignee chips -->
        <div class="flex items-center gap-1">
            <button
                v-for="u in allAssignees.slice(0, 5)"
                :key="u.id"
                @click="toggleAssignee(u.id as string)"
                class="rounded-full transition-all"
                :title="u.name"
                :class="filterAssignee.includes(u.id as string) ? 'ring-2 ring-blue-500 ring-offset-1' : 'opacity-70 hover:opacity-100'"
            >
                <UserAvatar :user="u" />
            </button>
        </div>

        <Button
            icon="pi pi-filter"
            label="Filter"
            :severity="countActiveFilter ? 'info' : 'secondary'"
            :badge="countActiveFilter ? countActiveFilter.toString() : undefined"
            badgeSeverity="info"
            size="small"
            outlined
            @click="(event) => op?.toggle(event)"
        />

        <div class="ml-auto flex items-center gap-2">
            <button
                v-if="hasActiveFilter"
                @click="clearFilters"
                class="flex h-7 items-center gap-1 rounded-full border border-rose-300 bg-rose-50 px-2 text-xs text-rose-500 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-950/40"
            >
                <i class="pi pi-filter-slash text-xs" /> Clear
            </button>
            <div class="flex items-center gap-2 text-xs text-surface-500 dark:text-surface-400">
                <span>{{ doneTasks }}/{{ totalTasks }}</span>
                <ProgressBar :value="boardProgress" style="width: 80px; height: 6px" :showValue="false" />
                <span>{{ boardProgress }}%</span>
            </div>
        </div>
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-4 p-1">
            <div class="flex flex-col gap-2">
                <Label class="text-surface-500 dark:text-surface-400">Priority</Label>
                <MultiSelect
                    v-model="filterPriority"
                    :options="props.taskPriorities"
                    optionLabel="name"
                    optionValue="id"
                    filter
                    placeholder="Filter Priority"
                    :maxSelectedLabels="3"
                    class="w-full md:w-80"
                />
            </div>

            <div class="flex flex-col gap-2">
                <Label class="text-surface-500 dark:text-surface-400">Type</Label>
                <MultiSelect
                    v-model="filterType"
                    :options="props.taskTypes"
                    optionLabel="name"
                    optionValue="id"
                    filter
                    placeholder="Filter Type"
                    :maxSelectedLabels="3"
                    class="w-full md:w-80"
                />
            </div>
        </div>
    </Popover>
</template>
