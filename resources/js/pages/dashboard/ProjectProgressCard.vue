<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import ProgressWithLabel from '@/components/ProgressWithLabel.vue';
import PanelCard from '@/components/ui/PanelCard.vue';
import { formatDate } from '@/lib/date';
import { Deferred, router } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import type { DashboardProjectProgress } from './types';

const props = defineProps<{
    rows?: DashboardProjectProgress[];
    tab: string;
}>();

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'at-risk', label: 'At risk' },
];

const columns: TableColumn<DashboardProjectProgress>[] = [
    { accessorKey: 'title', header: 'Project' },
    { accessorKey: 'tasks', header: 'Tasks' },
    { accessorKey: 'progress', header: 'Progress' },
    { accessorKey: 'due_date', header: 'Deadline' },
];

const changeTab = (value: string | number) => {
    if (String(value) === props.tab) {
        return;
    }

    router.get(
        route('dashboard'),
        { projects_tab: String(value) },
        { only: ['projectProgress', 'projectsTab'], preserveScroll: true, preserveState: true, replace: true },
    );
};
</script>

<template>
    <PanelCard title="Project progress">
        <template #action>
            <UTabs :items="tabs" :model-value="tab" :content="false" size="sm" class="w-fit" @update:model-value="changeTab" />
        </template>

        <Deferred data="projectProgress">
            <template #fallback>
                <div class="flex flex-col gap-2">
                    <USkeleton class="h-8 w-full" />
                    <USkeleton v-for="n in 5" :key="n" class="h-10 w-full" />
                </div>
            </template>

            <template #default="{ reloading }">
                <UTable
                    :data="rows ?? []"
                    :columns="columns"
                    class="transition-opacity"
                    :class="{ 'opacity-50': reloading }"
                    :ui="{ tr: 'cursor-pointer hover:bg-elevated' }"
                    :on-select="(_e, row) => router.visit(route('project.show.kanban', row.original.id))"
                >
                    <template #title-cell="{ row }">
                        <div class="flex min-w-0 items-center gap-2">
                            <span v-if="row.original.emoji" class="shrink-0">{{ row.original.emoji }}</span>
                            <span class="truncate font-medium text-highlighted">{{ row.original.title }}</span>
                        </div>
                    </template>

                    <template #tasks-cell="{ row }">
                        <span class="tabular-nums">{{ row.original.done_tasks }}/{{ row.original.total_tasks }}</span>
                    </template>

                    <template #progress-cell="{ row }">
                        <ProgressWithLabel :value="row.original.progress_percent" bar-aria-label="Project progress" class="min-w-44" />
                    </template>

                    <template #due_date-cell="{ row }">
                        <span class="text-sm text-muted">{{ formatDate(row.original.due_date) }}</span>
                    </template>

                    <template #empty>
                        <EmptyState title="No projects in this category" size="compact" />
                    </template>
                </UTable>
            </template>
        </Deferred>
    </PanelCard>
</template>
