<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import type { LengthAwarePaginator } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import TaskReportTable from './Table.vue';
import type { TaskReportFilterOptions, TaskReportFilters, TaskReportOption, TaskReportTask } from './types';

interface Props {
    tasks: LengthAwarePaginator<TaskReportTask>;
    filters: TaskReportFilters;
    filterOptions: TaskReportFilterOptions;
    project_statuses: TaskReportOption[];
}

const props = defineProps<Props>();

const filters = reactive({
    names: [...(props.filters.names ?? [])] as string[],
    statuses: [...(props.filters.statuses ?? [])] as string[],
    project_statuses: [...(props.filters.project_statuses ?? [])] as string[],
    priorities: [...(props.filters.priorities ?? [])] as string[],
    types: [...(props.filters.types ?? [])] as string[],
    start_date_from: props.filters.start_date_from ?? '',
    start_date_to: props.filters.start_date_to ?? '',
    due_date_from: props.filters.due_date_from ?? '',
    due_date_to: props.filters.due_date_to ?? '',
    search: props.filters.search ?? '',
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.names.length) count++;
    if (filters.statuses.length) count++;
    if (filters.project_statuses.length) count++;
    if (filters.priorities.length) count++;
    if (filters.types.length) count++;
    if (filters.start_date_from) count++;
    if (filters.start_date_to) count++;
    if (filters.due_date_from) count++;
    if (filters.due_date_to) count++;
    if (filters.search) count++;

    return count;
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const showFilters = ref(hasActiveFilters.value);

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
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// filter langsung diterapkan begitu berubah; di-debounce biar nggak nembak request tiap huruf/klik
watchDebounced(filters, () => navigate({ page: 1 }), { deep: true, debounce: 400 });

const pageSizes = [10, 25, 50, 100];

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

            <div class="flex justify-end gap-3">
                <UButton
                    :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                    icon="i-lucide-filter"
                    color="neutral"
                    variant="outline"
                    @click="showFilters = !showFilters"
                >
                    <template v-if="activeFilterCount" #trailing>
                        <UBadge color="primary" variant="subtle" size="sm">{{ activeFilterCount }}</UBadge>
                    </template>
                </UButton>
            </div>

            <UCard v-if="showFilters">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <Label value="Assignee" />
                        <USelectMenu
                            v-model="filters.names"
                            :items="filterOptions.creators"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All assignees"
                            class="w-full"
                        >
                            <template #item-leading="{ item }">
                                <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" :text="getInitials(item.name)" size="xs" />
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Project Status" />
                        <USelectMenu
                            v-model="filters.project_statuses"
                            :items="filterOptions.project_statuses"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All project statuses"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Task Status" />
                        <USelectMenu
                            v-model="filters.statuses"
                            :items="filterOptions.statuses"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All task statuses"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Priority" />
                        <USelectMenu
                            v-model="filters.priorities"
                            :items="filterOptions.priorities"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All priorities"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Type" />
                        <USelectMenu
                            v-model="filters.types"
                            :items="filterOptions.types"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All types"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Search" />
                        <UInput v-model="filters.search" icon="i-lucide-search" placeholder="Search title or description" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Start Date From" />
                        <UInput v-model="filters.start_date_from" type="date" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Start Date To" />
                        <UInput v-model="filters.start_date_to" type="date" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Due Date From" />
                        <UInput v-model="filters.due_date_from" type="date" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Due Date To" />
                        <UInput v-model="filters.due_date_to" type="date" />
                    </div>
                </div>
            </UCard>

            <TaskReportTable :data="tasks.data" />

            <div class="flex items-center justify-center gap-3">
                <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
                <UPagination v-model:page="page" :items-per-page="pageSize" :total="tasks.meta.total" />
            </div>
        </div>
    </AppLayout>
</template>
