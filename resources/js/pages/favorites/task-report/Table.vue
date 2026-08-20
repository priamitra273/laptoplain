<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import moment from 'moment';
import type { TaskReportTask } from './types';

interface Props {
    data?: TaskReportTask[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const columns: TableColumn<TaskReportTask>[] = [
    { accessorKey: 'creator', header: 'Name' },
    { accessorKey: 'project', header: 'Project Status' },
    { accessorKey: 'summary', header: 'Summary' },
    { accessorKey: 'status', header: 'Task Status' },
    { accessorKey: 'priority', header: 'Priority' },
    { accessorKey: 'type', header: 'Type' },
    { accessorKey: 'start_date', header: 'Start Date' },
    { accessorKey: 'due_date', header: 'Due Date' },
];

const formatDate = (date: string | null) => (date ? moment(date).format('DD MMM YYYY') : '-');
</script>

<template>
    <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
        <div>
            <UTable :data="data" :columns="columns" class="flex-1">
                <template #creator-cell="{ row }">
                    <div v-if="row.original.creator" class="flex items-center gap-3">
                        <UAvatar :alt="row.original.creator.name" :text="getInitials(row.original.creator.name)" size="sm" />
                        <div>
                            <p class="font-medium">{{ row.original.creator.name }}</p>
                            <ULink
                                v-if="row.original.project"
                                :href="route('project.show.kanban', { encoded: row.original.project.id })"
                                class="text-xs"
                            >
                                {{ row.original.project.title }}
                            </ULink>
                        </div>
                    </div>
                    <span v-else class="text-muted">-</span>
                </template>

                <template #project-cell="{ row }">
                    <UBadge v-if="row.original.project?.status" :color="severityColor(row.original.project.status.severity)" variant="subtle">
                        {{ row.original.project.status.name }}
                    </UBadge>
                    <span v-else class="text-muted">-</span>
                </template>

                <template #summary-cell="{ row }">
                    <ULink :href="route('task.show', row.original.id)" class="block max-w-xs">
                        <p class="truncate font-medium">{{ row.original.title }}</p>
                        <p class="truncate text-xs text-muted">{{ row.original.summary }}</p>
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

                <template #start_date-cell="{ row }">
                    {{ formatDate(row.original.start_date) }}
                </template>

                <template #due_date-cell="{ row }">
                    {{ formatDate(row.original.due_date) }}
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
