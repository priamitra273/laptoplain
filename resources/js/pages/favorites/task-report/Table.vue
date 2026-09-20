<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PriorityIcon from '@/components/PriorityIcon.vue';
import ProgressWithLabel from '@/components/ProgressWithLabel.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import ServerDataTable from '@/components/ui/ServerDataTable.vue';
import { formatDate } from '@/lib/date';
import { getInitials } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import type { TaskReportTask } from './types';

interface Props {
    data?: TaskReportTask[];
    loading?: boolean;
    total: number;
}

withDefaults(defineProps<Props>(), {
    data: () => [],
    loading: false,
});

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

const columns: TableColumn<TaskReportTask>[] = [
    { accessorKey: 'creator', header: 'Creator / Project', enableSorting: false },
    { accessorKey: 'project', header: 'Project Status', enableSorting: false },
    { accessorKey: 'summary', header: 'Task', enableSorting: false },
    { accessorKey: 'status', header: 'Task Status', enableSorting: false },
    { accessorKey: 'priority', header: 'Priority', enableSorting: false },
    { accessorKey: 'type', header: 'Type', enableSorting: false },
    { accessorKey: 'progress', header: 'Progress', enableSorting: false },
    { accessorKey: 'start_date', header: 'Start Date', enableSorting: false },
    { accessorKey: 'due_date', header: 'Due Date', enableSorting: false },
];
</script>

<template>
    <ServerDataTable
        v-model:page="page"
        v-model:per-page="perPage"
        :data="data"
        :columns="columns"
        :loading="loading"
        :total="total"
        :page-sizes="[10, 25, 50, 100]"
        result-label="tasks"
        table-base-class="min-w-[1120px]"
    >
            <template #creator-cell="{ row }">
                <div v-if="row.original.creator" class="flex items-center gap-3">
                    <UAvatar :alt="row.original.creator.name" :text="getInitials(row.original.creator.name)" size="sm" />
                    <div>
                        <p class="font-medium">{{ row.original.creator.name }}</p>
                        <Link
                            v-if="row.original.project"
                            :href="route('project.show.kanban', { encoded: row.original.project.id })"
                            class="text-muted hover:text-default inline-flex w-fit items-center gap-1 text-xs transition-colors"
                        >
                            <UIcon name="i-lucide-folder" class="size-3 shrink-0" />
                            <span class="truncate">{{ row.original.project.title }}</span>
                        </Link>
                    </div>
                </div>
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #project-cell="{ row }">
                <StatusBadge
                    v-if="row.original.project?.status"
                    :label="row.original.project.status.name"
                    :severity="row.original.project.status.severity"
                />
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #summary-cell="{ row }">
                <Link :href="route('task.show', row.original.id)" class="block max-w-xs">
                    <p class="text-highlighted hover:text-primary truncate font-medium transition-colors">
                        {{ row.original.title }}
                    </p>
                    <p class="truncate text-xs text-muted">{{ row.original.summary }}</p>
                </Link>
            </template>

            <template #status-cell="{ row }">
                <StatusBadge
                    v-if="row.original.status"
                    :label="row.original.status.name"
                    :severity="row.original.status.severity"
                />
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #priority-cell="{ row }">
                <PriorityIcon
                    v-if="row.original.priority"
                    pill
                    :label="row.original.priority.name"
                    :severity="row.original.priority.severity"
                />
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #type-cell="{ row }">
                <TaskTypeBadge v-if="row.original.type" :label="row.original.type.name" :severity="row.original.type.severity" />
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #progress-cell="{ row }">
                <ProgressWithLabel
                    :value="row.original.progress ?? 0"
                    :bar-aria-label="`Progress for ${row.original.title}`"
                    class="min-w-36"
                />
            </template>

            <template #start_date-cell="{ row }">
                <span v-if="row.original.start_date" class="inline-flex items-center gap-1 whitespace-nowrap text-muted">
                    <UIcon name="i-lucide-calendar" class="size-3.5 shrink-0" />
                    {{ formatDate(row.original.start_date) }}
                </span>
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #due_date-cell="{ row }">
                <span v-if="row.original.due_date" class="inline-flex items-center gap-1 whitespace-nowrap text-muted">
                    <UIcon name="i-lucide-calendar" class="size-3.5 shrink-0" />
                    {{ formatDate(row.original.due_date) }}
                </span>
                <span v-else class="text-dimmed">—</span>
            </template>

            <template #empty>
                <EmptyState icon="i-lucide-inbox" title="No tasks found" />
            </template>
    </ServerDataTable>
</template>
