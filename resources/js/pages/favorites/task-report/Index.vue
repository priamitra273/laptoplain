<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { LengthAwarePaginator } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref, watch } from 'vue';
import TaskReportTable from './Table.vue';
import {
    normalizeTaskReportFilters,
    resetTaskReportFilters,
    taskReportFiltersEqual,
} from './filters';
import TaskReportToolbar from './Toolbar.vue';
import { createTaskReportVisitOptions } from './navigation';
import type { TaskReportFilterOptions, TaskReportFilters, TaskReportOption, TaskReportTask } from './types';

interface Props {
    tasks: LengthAwarePaginator<TaskReportTask>;
    filters: TaskReportFilters;
    filterOptions: TaskReportFilterOptions;
    project_statuses: TaskReportOption[];
}

const props = defineProps<Props>();

const filters = reactive(normalizeTaskReportFilters(props.filters));

const isTableLoading = ref(false);

const navigate = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get(
        route('reports.tasks.index'),
        {
            names: filters.names.length ? filters.names : undefined,
            statuses: filters.statuses.length ? filters.statuses : undefined,
            project_statuses: filters.project_statuses.length ? filters.project_statuses : undefined,
            priorities: filters.priorities.length ? filters.priorities : undefined,
            types: filters.types.length ? filters.types : undefined,
            start_date_from: filters.start_date_from || undefined,
            start_date_to: filters.start_date_to || undefined,
            due_date_from: filters.due_date_from || undefined,
            due_date_to: filters.due_date_to || undefined,
            search: filters.search || undefined,
            page: overrides.page ?? props.tasks.meta.current_page,
            per_page: overrides.per_page ?? props.tasks.meta.per_page,
        },
        createTaskReportVisitOptions((loading) => {
            isTableLoading.value = loading;
        }),
    );
};

// filter langsung diterapkan begitu berubah; di-debounce biar nggak nembak request tiap huruf/klik
watch(
    () => props.filters,
    (value) => {
        Object.assign(filters, normalizeTaskReportFilters(value));
    },
    { deep: true },
);

watchDebounced(
    filters,
    () => {
        if (taskReportFiltersEqual(filters, props.filters)) {
            return;
        }

        navigate({ page: 1 });
    },
    { deep: true, debounce: 400 },
);

const clearFilters = () => {
    Object.assign(filters, resetTaskReportFilters());
};

/** Endpoint export adalah unduhan biasa, jadi cukup diarahkan langsung — bukan lewat Inertia. */
const exportReport = () => {
    const params = new URLSearchParams();

    filters.names.forEach((id) => params.append('names[]', id));
    filters.statuses.forEach((id) => params.append('statuses[]', id));
    filters.project_statuses.forEach((id) => params.append('project_statuses[]', id));
    filters.priorities.forEach((id) => params.append('priorities[]', id));
    filters.types.forEach((id) => params.append('types[]', id));

    if (filters.start_date_from) params.set('start_date_from', filters.start_date_from);
    if (filters.start_date_to) params.set('start_date_to', filters.start_date_to);
    if (filters.due_date_from) params.set('due_date_from', filters.due_date_from);
    if (filters.due_date_to) params.set('due_date_to', filters.due_date_to);
    if (filters.search) params.set('search', filters.search);

    window.location.href = `${route('reports.tasks.export')}?${params.toString()}`;
};

const page = computed({
    get: () => props.tasks.meta.current_page,
    set: (value: number) => navigate({ page: value }),
});

const pageSize = computed({
    get: () => props.tasks.meta.per_page,
    set: (value: number) => navigate({ page: 1, per_page: value }),
});
</script>

<template>
    <Head title="Task Report" />

    <AppLayout title="Task Report">
        <div class="flex flex-col gap-6">
            <Heading title="Task Report" description="Manage and track all tasks in your project" />

            <TaskReportToolbar
                v-model:search="filters.search"
                v-model:names="filters.names"
                v-model:statuses="filters.statuses"
                v-model:project-statuses="filters.project_statuses"
                v-model:priorities="filters.priorities"
                v-model:types="filters.types"
                v-model:start-date-from="filters.start_date_from"
                v-model:start-date-to="filters.start_date_to"
                v-model:due-date-from="filters.due_date_from"
                v-model:due-date-to="filters.due_date_to"
                :filter-options="filterOptions"
                @reset="clearFilters"
                @export="exportReport"
            />

            <TaskReportTable
                v-model:page="page"
                v-model:per-page="pageSize"
                :data="tasks.data"
                :loading="isTableLoading"
                :total="tasks.meta.total"
            />
        </div>
    </AppLayout>
</template>
