<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import moment from 'moment';
import type { AssignedTask } from './types';

interface Props {
    data?: AssignedTask[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const columns: TableColumn<AssignedTask>[] = [
    { accessorKey: 'title', header: 'Task' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'priority', header: 'Priority' },
    { accessorKey: 'type', header: 'Type' },
    { accessorKey: 'due_date', header: 'Due date' },
];

const formatDueDate = (task: AssignedTask) => {
    if (!task.due_date) return '—';

    const due = moment(task.due_date);
    const formatted = due.format('DD MMM YYYY');
    const diffDays = due.startOf('day').diff(moment().startOf('day'), 'days');

    if (diffDays < 0) return `${formatted} · Overdue`;
    if (diffDays === 0) return `${formatted} · Today`;
    if (diffDays === 1) return `${formatted} · Tomorrow`;
    return formatted;
};
</script>

<template>
    <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
        <div>
            <UTable :data="data" :columns="columns" class="flex-1">
                <template #title-cell="{ row }">
                    <ULink :href="route('task.show', row.original.id)" class="flex flex-col gap-0.5">
                        <span class="font-medium">{{ row.original.title }}</span>
                        <ULink
                            v-if="row.original.project"
                            :href="route('project.show.kanban', { encoded: row.original.project.id })"
                            class="inline-flex w-fit items-center gap-1 text-xs text-muted"
                            @click.stop
                        >
                            <UIcon name="i-lucide-folder" class="size-3" />
                            <span>{{ row.original.project.title }}</span>
                        </ULink>
                    </ULink>
                </template>

                <template #status-cell="{ row }">
                    <UBadge v-if="row.original.status" :color="severityColor(row.original.status.severity)" variant="subtle">
                        {{ row.original.status.name }}
                    </UBadge>
                    <span v-else class="text-muted">-</span>
                </template>

                <template #priority-cell="{ row }">
                    <UBadge v-if="row.original.priority" :color="severityColor(row.original.priority.severity)" variant="subtle">
                        {{ row.original.priority.name }}
                    </UBadge>
                    <span v-else class="text-muted">-</span>
                </template>

                <template #type-cell="{ row }">
                    <UBadge v-if="row.original.type" :color="severityColor(row.original.type.severity)" variant="subtle">
                        {{ row.original.type.name }}
                    </UBadge>
                    <span v-else class="text-muted">-</span>
                </template>

                <template #due_date-cell="{ row }">
                    <span class="inline-flex items-center gap-1.5 text-sm" :class="row.original.is_overdue ? 'font-medium text-error' : ''">
                        <UIcon v-if="row.original.due_date" name="i-lucide-calendar" class="size-3.5" />
                        {{ formatDueDate(row.original) }}
                    </span>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-2 py-8">
                        <UIcon name="i-lucide-inbox" class="size-8 text-muted" />
                        <p class="text-sm text-muted">No tasks found</p>
                    </div>
                </template>
            </UTable>
        </div>
    </UCard>
</template>
