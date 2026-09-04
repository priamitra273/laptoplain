<script setup lang="ts">
import { Deferred, router } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';

interface ProjectRow {
    id: string;
    title: string;
    emoji: string | null;
    total_tasks: number;
    done_tasks: number;
    progress_percent: number;
    due_date: string | null;
    status_name: string | null;
    status_severity: string | null;
}

const props = defineProps<{
    rows?: ProjectRow[];
    tab: string;
}>();

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'at-risk', label: 'At risk' },
];

const columns: TableColumn<ProjectRow>[] = [
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

const deadline = (value: string | null): string =>
    value ? new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <UCard :ui="{ root: 'gap-0 py-0', body: 'flex flex-col gap-4 p-4 sm:p-4' }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-highlighted">Project progress</h2>

            <UTabs
                :items="tabs"
                :model-value="tab"
                :content="false"
                size="sm"
                class="w-fit"
                @update:model-value="changeTab"
            />
        </div>

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
                    :on-select="(_e, row) => router.visit(route('project.show', row.original.id))"
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
                        <div class="flex items-center gap-2">
                            <UProgress :model-value="row.original.progress_percent" size="sm" class="min-w-24 flex-1" />
                            <span class="w-9 text-right text-xs tabular-nums text-muted">{{ row.original.progress_percent }}%</span>
                        </div>
                    </template>

                    <template #due_date-cell="{ row }">
                        <span class="text-sm text-muted">{{ deadline(row.original.due_date) }}</span>
                    </template>

                    <template #empty>
                        <p class="py-6 text-center text-sm text-muted">Tidak ada project di kategori ini.</p>
                    </template>
                </UTable>
            </template>
        </Deferred>
    </UCard>
</template>
