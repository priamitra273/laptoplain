<script setup lang="ts">
import PriorityIcon from '@/components/PriorityIcon.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import ServerDataTable from '@/components/ui/ServerDataTable.vue';
import { dueDateTone, formatDate, formatRelativeDay } from '@/lib/date';
import { Link, router } from '@inertiajs/vue3';
import type { TableColumn, TableRow } from '@nuxt/ui';
import type { AssignedTask, Paginator } from './types';

const props = defineProps<{
    tasks: Paginator<AssignedTask> | undefined;
    loading?: boolean;
}>();

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

/**
 * Kolomnya mengikuti PRD §6.4: project menempel di bawah judul, bukan jadi kolom sendiri.
 * Tidak ada header yang bisa diurutkan — pengurutan dipegang server dan belum disediakan.
 */
const columns: TableColumn<AssignedTask>[] = [
    { accessorKey: 'title', header: 'Task', enableSorting: false, meta: { class: { td: 'min-w-64 max-w-96 whitespace-normal' } } },
    { accessorKey: 'status', header: 'Status', enableSorting: false },
    { accessorKey: 'priority', header: 'Priority', enableSorting: false },
    { accessorKey: 'type', header: 'Type', enableSorting: false },
    { accessorKey: 'due_date', header: 'Due date', enableSorting: false },
];

/** Baris utuh membuka task. Tautan project di dalam judul menghentikan event supaya tidak ikut terbawa. */
const openTask = (event: Event, row: TableRow<AssignedTask>) => router.visit(route('task.show', { task: row.original.id }));
/** Nada yang sama dengan kartu board, supaya due date terbaca serupa di kedua tampilan. */
const dueDateClass = (task: AssignedTask) =>
    ({ overdue: 'text-error font-medium', soon: 'text-warning', normal: 'text-muted', none: 'text-dimmed' })[
        dueDateTone(task.due_date, task.is_overdue)
    ];
</script>

<template>
    <ServerDataTable
        v-model:page="page"
        v-model:per-page="perPage"
        :data="props.tasks?.data ?? []"
        :columns="columns"
        :loading="loading"
        :total="props.tasks?.total ?? 0"
        result-label="tasks"
        :table-ui="{ tr: 'group cursor-pointer transition-colors' }"
        :on-row-select="openTask"
    >
        <template #title-cell="{ row }">
            <div class="flex min-w-0 flex-col gap-0.5">
                <span class="text-[13px] leading-5 font-medium wrap-anywhere text-highlighted transition-colors group-hover:text-primary">
                    {{ row.original.title }}
                </span>

                <Link
                    v-if="row.original.project"
                    :href="route('project.show.kanban', { encoded: row.original.project.id })"
                    class="inline-flex w-fit items-center gap-1 text-xs text-muted transition-colors hover:text-default"
                    @click.stop
                >
                    <UIcon name="i-lucide-folder" class="size-3 shrink-0" />
                    <span class="truncate">{{ row.original.project.title }}</span>
                </Link>

                <span v-if="row.original.sub_task_count" class="text-xs text-muted tabular-nums">
                    {{ row.original.sub_task_done_count }}/{{ row.original.sub_task_count }} subtasks
                </span>
            </div>
        </template>

        <template #status-cell="{ row }">
            <StatusBadge v-if="row.original.status" :label="row.original.status.name" :severity="row.original.status.severity" />
            <span v-else class="text-dimmed">—</span>
        </template>

        <template #priority-cell="{ row }">
            <PriorityIcon v-if="row.original.priority" pill :label="row.original.priority.name" :severity="row.original.priority.severity" />
            <span v-else class="text-dimmed">—</span>
        </template>

        <template #type-cell="{ row }">
            <TaskTypeBadge v-if="row.original.type" :label="row.original.type.name" :severity="row.original.type.severity" />
            <span v-else class="text-dimmed">—</span>
        </template>

        <template #due_date-cell="{ row }">
            <span v-if="row.original.due_date" class="inline-flex items-center gap-1 text-xs whitespace-nowrap" :class="dueDateClass(row.original)">
                <UIcon name="i-lucide-calendar" class="size-3.5 shrink-0" />
                {{ formatDate(row.original.due_date) }}
                <span class="opacity-70">· {{ formatRelativeDay(row.original.due_date) }}</span>
            </span>
            <span v-else class="text-dimmed">—</span>
        </template>
    </ServerDataTable>
</template>
