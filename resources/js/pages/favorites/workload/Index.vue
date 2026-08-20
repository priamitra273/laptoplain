<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import WorkloadTable from './Table.vue';
import type { WorkloadFilters, WorkloadPaginator, WorkloadStatusOption, WorkloadSummary, WorkloadUserOption } from './types';

interface Props {
    users: WorkloadPaginator;
    filters: WorkloadFilters;
    filterOptions: {
        users: WorkloadUserOption[];
        workload_statuses: WorkloadStatusOption[];
    };
    summary: WorkloadSummary;
}

const props = defineProps<Props>();

const summaryCards = computed(() => [
    { key: 'total_users', label: 'Total Users', icon: 'i-lucide-users', value: props.summary.total_users },
    { key: 'free', label: 'Free', icon: 'i-lucide-circle-check', value: props.summary.free },
    { key: 'light', label: 'Almost Done', icon: 'i-lucide-chart-no-axes-column', value: props.summary.light },
    { key: 'moderate', label: 'Ongoing', icon: 'i-lucide-clock', value: props.summary.moderate },
    { key: 'busy', label: 'Overloaded', icon: 'i-lucide-triangle-alert', value: props.summary.busy },
]);

const filters = reactive({
    names: [...(props.filters.names ?? [])] as string[],
    workload_statuses: [...(props.filters.workload_statuses ?? [])] as number[],
    search: props.filters.search ?? '',
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.names.length) count++;
    if (filters.workload_statuses.length) count++;
    if (filters.search) count++;

    return count;
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const showFilters = ref(hasActiveFilters.value);

const navigate = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get(
        route('workload-users.index'),
        {
            names: filters.names.length ? filters.names : undefined,
            workload_statuses: filters.workload_statuses.length ? filters.workload_statuses : undefined,
            search: filters.search || undefined,
            page: overrides.page ?? props.users.current_page,
            per_page: overrides.per_page ?? props.users.per_page,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watchDebounced(filters, () => navigate({ page: 1 }), { deep: true, debounce: 400 });

const pageSizes = [10, 25, 50, 100];

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
    <Head title="Workload" />

    <AppLayout title="Workload">
        <div class="flex flex-col gap-6">
            <Heading title="Workload" description="Monitor and manage user workload distribution" />

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <UCard v-for="card in summaryCards" :key="card.key">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs text-muted">{{ card.label }}</p>
                            <p class="text-lg leading-tight font-semibold">{{ card.value }}</p>
                        </div>
                        <UIcon :name="card.icon" class="size-4 shrink-0 text-muted" />
                    </div>
                </UCard>
            </div>

            <div class="flex justify-end">
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
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <Label value="Users" />
                        <USelectMenu
                            v-model="filters.names"
                            :items="filterOptions.users"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All users"
                            class="w-full"
                        >
                            <template #item-leading="{ item }">
                                <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" :text="getInitials(item.name)" size="xs" />
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Workload Status" />
                        <USelectMenu
                            v-model="filters.workload_statuses"
                            :items="filterOptions.workload_statuses"
                            label-key="name"
                            value-key="id"
                            multiple
                            placeholder="All statuses"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Search" />
                        <UInput v-model="filters.search" icon="i-lucide-search" placeholder="Search by name" @keyup.enter="navigate({ page: 1 })" />
                    </div>
                </div>
            </UCard>

            <WorkloadTable :data="users.data" :status-options="filterOptions.workload_statuses" />

            <div class="flex items-center justify-center gap-3">
                <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
                <UPagination v-model:page="page" :items-per-page="pageSize" :total="users.total" />
            </div>
        </div>
    </AppLayout>
</template>
