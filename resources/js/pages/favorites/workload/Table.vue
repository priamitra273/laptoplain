<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import { computed } from 'vue';
import type { WorkloadStatusOption, WorkloadUser } from './types';

interface Props {
    data?: WorkloadUser[];
    statusOptions?: WorkloadStatusOption[];
}

const props = withDefaults(defineProps<Props>(), {
    data: () => [],
    statusOptions: () => [],
});

const columns: TableColumn<WorkloadUser>[] = [
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'total_tasks', header: 'Total Tasks' },
    { accessorKey: 'remaining_work_percent', header: 'Remaining Work' },
    { accessorKey: 'workload_status', header: 'Status' },
];

const severityByStatus = computed(() => new Map(props.statusOptions.map((option) => [option.name, option.severity])));

const avatarSrc = (url?: string | null) => (url && !url.includes('default-avatar') ? url : undefined);
</script>

<template>
    <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
        <div>
            <UTable :data="data" :columns="columns" class="flex-1">
                <template #name-cell="{ row }">
                    <ULink :href="route('reports.tasks.index', { names: [row.original.id] })" class="flex items-center gap-2">
                        <UAvatar
                            :src="avatarSrc(row.original.avatar_url)"
                            :alt="row.original.name"
                            :text="getInitials(row.original.name)"
                            size="sm"
                        />
                        <span class="truncate font-medium">{{ row.original.name }}</span>
                    </ULink>
                </template>

                <template #total_tasks-cell="{ row }">
                    <UBadge color="neutral" variant="subtle">{{ row.original.total_tasks }}</UBadge>
                </template>

                <template #remaining_work_percent-cell="{ row }">
                    <div class="flex items-center gap-3">
                        <UProgress
                            :model-value="row.original.remaining_work_percent"
                            :color="severityColor(severityByStatus.get(row.original.workload_status))"
                            size="sm"
                            class="max-w-32"
                        />
                        <span class="text-muted tabular-nums">{{ row.original.remaining_work_percent }}%</span>
                    </div>
                </template>

                <template #workload_status-cell="{ row }">
                    <UBadge :color="severityColor(severityByStatus.get(row.original.workload_status))" variant="subtle">
                        {{ row.original.workload_status }}
                    </UBadge>
                </template>

                <template #empty>
                    <p class="text-center text-sm text-muted">No users found.</p>
                </template>
            </UTable>
        </div>
    </UCard>
</template>
