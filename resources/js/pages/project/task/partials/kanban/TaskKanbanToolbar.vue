<script setup lang="ts">
import TaskPriorityIcon from '@/components/TaskPriorityIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { computed } from 'vue';
import type { TaskPriority, TaskType, User } from '../../..';

const props = defineProps<{
    allAssignees: User[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    totalTasks: number;
    doneTasks: number;
    boardProgress: number;
}>();

const searchQuery = defineModel<string>('searchQuery', { default: '' });
const filterAssignee = defineModel<string[]>('filterAssignee', { default: () => [] });
const filterPriority = defineModel<string[]>('filterPriority', { default: () => [] });
const filterType = defineModel<string[]>('filterType', { default: () => [] });

const hasActiveFilter = computed(() => {
    return !!searchQuery.value || filterAssignee.value.length > 0 || filterPriority.value.length > 0 || filterType.value.length > 0;
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

const togglePriority = (id: string) => {
    if (filterPriority.value.includes(id)) {
        filterPriority.value = filterPriority.value.filter((p) => p !== id);
    } else {
        filterPriority.value = [...filterPriority.value, id];
    }
};

const toggleType = (id: string) => {
    if (filterType.value.includes(id)) {
        filterType.value = filterType.value.filter((t) => t !== id);
    } else {
        filterType.value = [...filterType.value, id];
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

        <!-- Priority pills -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="p in taskPriorities"
                :key="p.id"
                @click="togglePriority(p.id)"
                class="flex h-7 items-center gap-1 rounded-full border px-2 text-xs transition-all"
                :class="
                    filterPriority.includes(p.id)
                        ? 'border-blue-400 bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300'
                        : 'border-surface-200 bg-white text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-400'
                "
            >
                <TaskPriorityIcon :priority="p" /> <span class="pl-1">{{ p.name }}</span>
            </button>
        </div>

        <!-- Type pills -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="tp in taskTypes"
                :key="tp.id"
                @click="toggleType(tp.id)"
                class="h-7 rounded-full border px-2 text-xs transition-all"
                :class="
                    filterType.includes(tp.id)
                        ? 'border-blue-400 bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300'
                        : 'border-surface-200 bg-white text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-400'
                "
            >
                {{ tp.name }}
            </button>
        </div>

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
</template>
