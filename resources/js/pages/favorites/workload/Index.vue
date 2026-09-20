<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import WorkloadHighlights from './Highlights.vue';
import { createWorkloadVisitOptions, replaceWorkloadBrowserUrl } from './navigation';
import WorkloadTable from './Table.vue';
import WorkloadToolbar from './Toolbar.vue';
import type { WorkloadFilters, WorkloadPaginator, WorkloadSortColumn, WorkloadStatusOption, WorkloadSummary, WorkloadUserOption } from './types';

const props = defineProps<{
    users: WorkloadPaginator;
    filters: WorkloadFilters;
    filterOptions: {
        users: WorkloadUserOption[];
        workload_statuses: WorkloadStatusOption[];
    };
    summary: WorkloadSummary;
}>();

const filters = reactive({
    names: [...(props.filters.names ?? [])] as string[],
    workload_statuses: [...(props.filters.workload_statuses ?? [])] as number[],
    search: props.filters.search ?? '',
});

const sort = ref<WorkloadSortColumn>(props.filters.sort ?? 'remaining_work_percent');
const direction = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');
const isTableLoading = ref(false);
const updatedAt = ref('--:--');

const markUpdated = () => {
    updatedAt.value = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date());
};

onMounted(() => {
    markUpdated();
    replaceWorkloadBrowserUrl(window.history, window.history.state, route('workload-users.index'));
});

const hasFilters = computed(() => Boolean(filters.names.length || filters.workload_statuses.length || filters.search));

const navigate = (overrides: { page?: number; per_page?: number } = {}, partial = false) => {
    router.get(
        route('workload-users.index'),
        {
            names: filters.names.length ? filters.names : undefined,
            workload_statuses: filters.workload_statuses.length ? filters.workload_statuses : undefined,
            search: filters.search || undefined,
            sort: sort.value,
            direction: direction.value,
            page: overrides.page ?? props.users.current_page,
            per_page: overrides.per_page ?? props.users.per_page,
        },
        {
            ...createWorkloadVisitOptions(partial),
            onStart: () => {
                isTableLoading.value = true;
            },
            onSuccess: markUpdated,
            onFinish: () => {
                isTableLoading.value = false;
            },
        },
    );
};

watchDebounced(filters, () => navigate({ page: 1 }), { deep: true, debounce: 400 });

// Sorting dikerjakan server, jadi perubahan kolom/arah harus memicu request baru —
// baik dari klik header tabel maupun dropdown sort di toolbar.
watch([sort, direction], () => navigate({ page: 1 }, true));

const clearFilters = () => {
    filters.names = [];
    filters.workload_statuses = [];
    filters.search = '';
};

const applySort = (column: WorkloadSortColumn) => {
    if (sort.value === column) {
        direction.value = direction.value === 'desc' ? 'asc' : 'desc';
    } else {
        sort.value = column;
        direction.value = column === 'name' ? 'asc' : 'desc';
    }

};

const page = computed({
    get: () => props.users.current_page,
    set: (value: number) => navigate({ page: value }, true),
});

const pageSize = computed({
    get: () => props.users.per_page,
    set: (value: number) => navigate({ page: 1, per_page: value }, true),
});
</script>

<template>
    <AppLayout title="Workload">
        <Head title="Workload" />

        <div class="flex flex-col gap-6">
            <Heading title="Workload" description="Monitor and manage how task load is distributed across the team.">
                <span class="font-mono text-xs tracking-wide text-dimmed">Updated {{ updatedAt }}</span>
            </Heading>

            <WorkloadHighlights
    :summary="summary"
    :status-options="filterOptions.workload_statuses"
    :active-status-ids="filters.workload_statuses"
/>
            <div class="flex flex-col gap-4">
                <WorkloadToolbar
                    v-model:search="filters.search"
                    v-model:statuses="filters.workload_statuses"
                    v-model:sort="sort"
                    v-model:direction="direction"
                    :has-filters="hasFilters"
                    @clear="clearFilters"
                />

                <WorkloadTable
    v-model:page="page"
    v-model:per-page="pageSize"
    :data="users.data"
    :status-options="filterOptions.workload_statuses"
    :sort="sort"
    :total="users.total"
    :has-filters="hasFilters"
    :loading="isTableLoading"
    @sort="applySort"
/>
            </div>
        </div>
    </AppLayout>
</template>
