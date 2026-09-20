<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import ProgressWithLabel from '@/components/ProgressWithLabel.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import ServerDataTable from '@/components/ui/ServerDataTable.vue';
import { getInitials, severityColor } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import { computed } from 'vue';
import type { WorkloadSortColumn, WorkloadStatusOption, WorkloadUser } from './types';

const props = defineProps<{
    data: WorkloadUser[];
    statusOptions: WorkloadStatusOption[];
    sort: WorkloadSortColumn;
    total: number;
    hasFilters: boolean;
    loading?: boolean;
}>();

const emit = defineEmits<{
    sort: [column: WorkloadSortColumn];
}>();

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

const sortableColumns: WorkloadSortColumn[] = ['name', 'total_tasks', 'remaining_work_percent'];

const severityByStatus = computed(() => new Map(props.statusOptions.map((option) => [option.name, option.severity])));

const rows = computed(() =>
    props.data.map((user) => ({
        ...user,
        severity: severityByStatus.value.get(user.workload_status) ?? null,
        initials: getInitials(user.name),
        avatar: user.avatar_url && !user.avatar_url.includes('default-avatar') ? user.avatar_url : undefined,
    })),
);

type WorkloadRow = (typeof rows.value)[number];

const columns: TableColumn<WorkloadRow>[] = [
    { accessorKey: 'name', header: 'Member' },
    { accessorKey: 'total_tasks', header: 'Tasks' },
    { accessorKey: 'remaining_work_percent', header: 'Remaining work' },
    { accessorKey: 'workload_status', header: 'Status', enableSorting: false },
    { id: 'actions', header: '', enableSorting: false },
];

const displayedTaskTotal = computed(() => props.data.reduce((total, user) => total + user.total_tasks, 0));

/** ServerDataTable meng-emit id kolom sebagai string biasa. */
const handleSort = (column: string) => {
    if (sortableColumns.includes(column as WorkloadSortColumn)) {
        emit('sort', column as WorkloadSortColumn);
    }
};

const actions = (user: WorkloadUser) => [
    {
        label: 'View tasks',
        icon: 'i-lucide-list-checks',
        to: route('reports.tasks.index', { names: [user.id] }),
    },
];
</script>

<template>
    <ServerDataTable
        v-model:page="page"
        v-model:per-page="perPage"
        :data="rows"
        :columns="columns"
        :loading="loading"
        :sort="sort"
        :total="total"
        :page-sizes="[10, 25, 50, 100]"
        result-label="members"
        table-base-class="w-full min-w-180"
        @sort="handleSort"
    >
        <template #name-cell="{ row }">
            <div class="flex min-w-0 items-center gap-3">
                <UAvatar :src="row.original.avatar" :text="row.original.initials" :alt="row.original.name" size="sm" class="shrink-0" />
                <ULink
                    :to="route('reports.tasks.index', { names: [row.original.id] })"
                    class="truncate text-sm font-medium text-highlighted hover:text-primary"
                >
                    {{ row.original.name }}
                </ULink>
            </div>
        </template>

        <template #total_tasks-cell="{ row }">
            <span class="text-sm tabular-nums text-highlighted">{{ row.original.total_tasks }}</span>
        </template>

        <template #remaining_work_percent-cell="{ row }">
            <ProgressWithLabel
                compact
                :value="row.original.remaining_work_percent"
                :color="severityColor(row.original.severity)"
                :bar-aria-label="`Remaining work for ${row.original.name}`"
                class="min-w-48"
            />
        </template>

        <template #workload_status-cell="{ row }">
            <StatusBadge :label="row.original.workload_status" :severity="row.original.severity" />
        </template>

        <template #actions-cell="{ row }">
            <div class="flex justify-end">
                <UDropdownMenu :items="actions(row.original)" :content="{ align: 'end' }">
                    <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs" square aria-label="Member actions" />
                </UDropdownMenu>
            </div>
        </template>

        <template #empty>
            <EmptyState
                v-if="hasFilters"
                icon="i-lucide-filter"
                title="No matching users"
                description="Try adjusting the member, status, or name filter."
            />

            <div v-else class="flex flex-col items-center gap-2">
                <EmptyState
                    icon="i-lucide-users"
                    title="No active users yet"
                    description="Workload shows up here once there are active users with tasks assigned to them."
                />

                <UButton :to="route('user.index')" label="Manage users" color="neutral" variant="outline" size="sm" />
            </div>
        </template>

        <template #footer-meta>
            <span class="font-mono text-xs text-dimmed">{{ displayedTaskTotal }} tasks on this page</span>
        </template>
    </ServerDataTable>
</template>
