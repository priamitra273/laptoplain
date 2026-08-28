<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import WorkloadDistribution from './Distribution.vue';
import WorkloadHighlights from './Highlights.vue';
import WorkloadTable from './Table.vue';
import WorkloadToolbar from './Toolbar.vue';
import type { WorkloadFilters, WorkloadPaginator, WorkloadSortColumn, WorkloadStatusOption, WorkloadSummary, WorkloadUserOption } from './types';
import { buildSegments } from './workload';

const props = defineProps<{
    users: WorkloadPaginator;
    filters: WorkloadFilters;
    filterOptions: {
        users: WorkloadUserOption[];
        workload_statuses: WorkloadStatusOption[];
    };
    summary: WorkloadSummary;
}>();

const pageSizes = [10, 25, 50, 100];

const filters = reactive({
    names: [...(props.filters.names ?? [])] as string[],
    workload_statuses: [...(props.filters.workload_statuses ?? [])] as number[],
    search: props.filters.search ?? '',
});

const sort = ref<WorkloadSortColumn>(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

const hasFilters = computed(() => Boolean(filters.names.length || filters.workload_statuses.length || filters.search));

const segments = computed(() => buildSegments(props.summary, props.filterOptions.workload_statuses, filters.workload_statuses));

const navigate = (overrides: { page?: number; per_page?: number } = {}) => {
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
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watchDebounced(filters, () => navigate({ page: 1 }), { deep: true, debounce: 400 });

const toggleStatus = (statusId: number) => {
    filters.workload_statuses = filters.workload_statuses.includes(statusId)
        ? filters.workload_statuses.filter((id) => id !== statusId)
        : [...filters.workload_statuses, statusId];
};

const clearFilters = () => {
    filters.names = [];
    filters.workload_statuses = [];
    filters.search = '';
};

// Kolom yang sama berarti membalik arah; kolom baru selalu mulai dari yang terberat.
const applySort = (column: WorkloadSortColumn) => {
    if (sort.value === column) {
        direction.value = direction.value === 'desc' ? 'asc' : 'desc';
    } else {
        sort.value = column;
        direction.value = column === 'name' ? 'asc' : 'desc';
    }

    navigate({ page: 1 });
};

const page = computed({
    get: () => props.users.current_page,
    set: (value: number) => navigate({ page: value }),
});

const pageSize = computed({
    get: () => props.users.per_page,
    set: (value: number) => navigate({ page: 1, per_page: value }),
});
</script>

<template>
    <AppLayout title="Workload">
        <Head title="Workload" />

        <Heading title="Workload Users" description="Monitor and manage user workload distribution." />

        <UCard
            :ui="{
                root: 'overflow-hidden',
                body: 'p-0 sm:p-0',
                footer: 'px-4 py-2.5 sm:px-4 sm:py-2.5',
            }"
        >
            <div class="flex flex-col gap-3 p-4">
                <WorkloadHighlights :summary="summary" />

                <WorkloadDistribution
                    :segments="segments"
                    :total="summary.total_users"
                    :active-status-count="filters.workload_statuses.length"
                    @toggle="toggleStatus"
                />
            </div>

            <WorkloadToolbar
                v-model:search="filters.search"
                v-model:names="filters.names"
                v-model:statuses="filters.workload_statuses"
                :user-options="filterOptions.users"
                :status-options="filterOptions.workload_statuses"
                @clear="clearFilters"
            />

            <WorkloadTable
                :data="users.data"
                :status-options="filterOptions.workload_statuses"
                :sort="sort"
                :direction="direction"
                :has-filters="hasFilters"
                @sort="applySort"
                @clear="clearFilters"
            />

            <template #footer>
                <div class="flex items-center justify-center gap-3">
                    <USelect v-model="pageSize" :items="pageSizes" color="neutral" variant="outline" class="w-20" />
                    <UPagination v-model:page="page" :items-per-page="users.per_page" :total="users.total" size="sm" />
                </div>
            </template>
        </UCard>
    </AppLayout>
</template>
