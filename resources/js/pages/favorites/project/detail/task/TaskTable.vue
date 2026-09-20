<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import ProgressWithLabel from '@/components/ProgressWithLabel.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TaskCategoryBadge from '@/components/task/TaskCategoryBadge.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import TreeTable from '@/components/ui/TreeTable.vue';
import { formatDate } from '@/lib/date';
import { plural } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import type { Row, SortingState } from '@tanstack/vue-table';
import { useSessionStorage } from '@vueuse/core';
import { computed, h, ref, resolveComponent, watch } from 'vue';
import type { TaskOption } from '../types';
import TaskTableToolbar from './TaskTableToolbar.vue';
import { completionDate, flattenTasks } from './taskTree';
import type { ListTask, TaskFilters, TaskMove } from './types';
import { useTaskDragDrop } from './useTaskDragDrop';
import { useTaskTree } from './useTaskTree';

const props = defineProps<{
    projectId: string;
    userId: string;
    tasks: ListTask[];
    statuses: TaskOption[];
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    busy: boolean;
}>();
const emit = defineEmits<{
    create: [parent: ListTask | null];
    edit: [task: ListTask];
    view: [task: ListTask, history?: boolean];
    open: [task: ListTask];
    delete: [task: ListTask];
    move: [value: TaskMove];
}>();
const defaults = (): TaskFilters => ({ search: '', status: null });
const filters = useSessionStorage<TaskFilters>(`project-list:${props.projectId}:${props.userId}`, defaults(), { mergeDefaults: true });
watch(
    () => props.statuses,
    () => {
        if (filters.value.status && !props.statuses.some((status) => status.id === filters.value.status)) filters.value.status = null;
    },
    { immediate: true },
);
const { expanded, filtered, filtering, allExpanded, hasBranches, totals, visibleCount, toggleAll } = useTaskTree(
    () => props.tasks,
    () => filters.value,
);
/** Subtask ikut dihitung karena pil status menyaring seluruh pohon, bukan hanya task teratas. */
const statusOptions = computed(() => {
    const counts: Record<string, number> = {};

    for (const task of flattenTasks(props.tasks)) {
        if (task.status) counts[task.status.id] = (counts[task.status.id] ?? 0) + 1;
    }

    return props.statuses.map((status) => ({ ...status, count: counts[status.id] ?? 0 }));
});
const sorting = ref<SortingState>([]);
const canDrag = computed(() => props.canEdit && !props.busy && !filtering.value && sorting.value.length === 0);
const { sourceId, targetId, mode, dragging, start, over, drop, reset, leave } = useTaskDragDrop(
    () => props.tasks,
    () => canDrag.value,
    (value) => emit('move', value),
);
watch(canDrag, (enabled) => {
    if (!enabled) reset();
});
const UButton = resolveComponent('UButton');
const sortable =
    (label: string): TableColumn<ListTask>['header'] =>
        ({ column }) =>
            h(UButton, {
                label,
                color: 'neutral',
                variant: 'ghost',
                size: 'xs',
                class: '-ms-2 text-[11px] font-medium uppercase tracking-wide text-dimmed',
                onClick: () => (column.getIsSorted() === 'desc' ? column.clearSorting() : column.toggleSorting(column.getIsSorted() === 'asc')),
            });
const columns = computed<TableColumn<ListTask>[]>(() => [
    {
        id: 'grip',
        header: '',
        size: 36,
        meta: { class: { td: 'w-9 px-2 lg:sticky lg:start-0 lg:z-10 lg:bg-default lg:group-hover:bg-[color-mix(in_oklab,var(--ui-bg-muted)_40%,var(--ui-bg))]', th: 'w-9 px-2 lg:sticky lg:start-0 lg:z-20' } },
    },
    {
        accessorKey: 'title',
        header: sortable('Title'),
        meta: {
            class: { td: 'min-w-64 max-w-80 lg:sticky lg:start-9 lg:z-10 lg:bg-default lg:group-hover:bg-[color-mix(in_oklab,var(--ui-bg-muted)_40%,var(--ui-bg))]', th: 'min-w-64 lg:sticky lg:start-9 lg:z-20' },
        },
    },
    { accessorKey: 'status.name', id: 'status', header: sortable('Status') },
    { accessorKey: 'type.name', id: 'type', header: sortable('Type') },
    { accessorKey: 'start_date', header: sortable('Start date') },
    { accessorKey: 'due_date', header: sortable('Due date') },
    { accessorKey: 'completed_at', header: sortable('Completed') },
    { accessorKey: 'progress', header: sortable('Progress'), meta: { class: { td: 'min-w-44' } } },
    {
        id: 'actions',
        header: 'Actions',
        meta: { class: { td: 'text-end lg:sticky lg:end-0 lg:z-10 lg:bg-default lg:group-hover:bg-[color-mix(in_oklab,var(--ui-bg-muted)_40%,var(--ui-bg))]', th: 'text-end lg:sticky lg:end-0 lg:z-20' } },
    },
]);
const rowMeta = computed(() => ({
    class: {
        tr: (row: Row<ListTask>) =>
            [
                sourceId.value === row.id ? 'opacity-40' : '',
                targetId.value === row.id && mode.value === 'before' ? '[&>td]:shadow-[inset_0_2px_0_var(--ui-primary)]' : '',
                targetId.value === row.id && mode.value === 'after' ? '[&>td]:shadow-[inset_0_-2px_0_var(--ui-primary)]' : '',
                targetId.value === row.id && mode.value === 'inside' ? '[&>td]:bg-primary/10' : '',
            ].join(' '),
    },
}));
const actions = (task: ListTask) => [
    ...(props.canCreate ? [{ label: 'Add subtask', icon: 'i-lucide-plus', disabled: props.busy, onSelect: () => emit('create', task) }] : []),
    { label: 'History', icon: 'i-lucide-history', onSelect: () => emit('view', task, true) },
    ...(props.canDelete
        ? [{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error' as const, disabled: props.busy, onSelect: () => emit('delete', task) }]
        : []),
];

</script>

<template>
    <div class="flex min-w-0 flex-col gap-4">
        <TaskTableToolbar v-model="filters" :statuses="statusOptions" :total="totals.total" :can-create="canCreate"
            :busy="busy" :all-expanded="allExpanded" :has-branches="hasBranches" :filtering="filtering"
            @create="emit('create', null)" @toggle-all="toggleAll" @clear="filters = defaults()" />
        <div v-if="busy" role="status" class="text-muted flex items-center gap-2 text-xs">
            <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />Updating tasks…
        </div>
        <div class="border-default min-w-0 overflow-hidden rounded-xl border" @dragstart="start" @dragover="over"
            @dragenter="over" @drop="drop" @dragend="reset" @dragleave="leave">
            <TreeTable v-model:expanded="expanded" v-model:sorting="sorting" :data="filtered.tree" :columns="columns"
                :get-sub-rows="(task: ListTask) => task.sub_task_recursive" :get-row-id="(task: ListTask) => task.id"
                expand-column="title" expand-label="Expand subtasks" collapse-label="Collapse subtasks"
                :meta="rowMeta" sticky class="max-h-[65vh] min-h-48" :ui="{
                    th: 'bg-[color-mix(in_oklab,var(--ui-bg-muted)_50%,var(--ui-bg))] px-4 py-2.5 text-[11px] font-medium uppercase tracking-wide text-dimmed',
                    td: 'px-4 py-3 text-xs whitespace-normal',
                    tr: 'group transition-colors hover:bg-muted/40 has-[>td:only-child:empty]:hidden',
                }">
                <template #grip-cell="{ row }">
                    <span :data-task-id="row.original.id" :draggable="canDrag"
                        class="inline-flex size-6 items-center justify-center"
                        :class="canDrag ? 'text-dimmed hover:text-default cursor-grab active:cursor-grabbing' : 'text-dimmed/40'"
                        :title="canDrag ? 'Drag to reorder or nest' : undefined" aria-hidden="true">
                        <UIcon v-if="canEdit" name="i-lucide-grip-vertical" class="size-4" />
                    </span>
                </template>
                <template #title-cell="{ row }">
                    <TaskCategoryBadge v-if="row.original.category" :category="row.original.category"
                        :show-label="false" />
                    <button type="button"
                        class="text-highlighted hover:text-primary min-w-0 text-start text-[13px] leading-5 font-medium wrap-anywhere focus-visible:outline-2 focus-visible:outline-primary"
                        :class="filtering && !filtered.matchedIds.has(row.id) ? 'text-muted' : ''"
                        @click="emit('view', row.original)">
                        {{ row.original.title }}
                    </button>
                </template>
                <template #status-cell="{ row }">
                    <StatusBadge v-if="row.original.status" :label="row.original.status.name"
                        :severity="row.original.status.severity" /><span v-else class="text-dimmed">—</span>
                </template>
                <template #type-cell="{ row }">
                    <TaskTypeBadge v-if="row.original.type" :label="row.original.type.name" :severity="row.original.type.severity" />
                    <span v-else class="text-dimmed">—</span>
                </template>
                <template #start_date-cell="{ row }"><span class="text-muted whitespace-nowrap">{{
                    formatDate(row.original.start_date) }}</span></template>
                <template #due_date-cell="{ row }"><span class="inline-flex items-center gap-1 whitespace-nowrap"
                        :class="row.original.is_overdue ? 'text-error' : 'text-muted'">
                        <UIcon v-if="row.original.is_overdue" name="i-lucide-circle-alert" class="size-3.5" />{{
                            formatDate(row.original.due_date)
                        }}<span v-if="row.original.is_overdue" class="sr-only"> (overdue)</span>
                    </span></template>
                <template #completed_at-cell="{ row }"><span class="text-muted whitespace-nowrap">{{
                    formatDate(completionDate(row.original.completed_at)) }}</span></template>
                <template #progress-cell="{ row }">
                    <ProgressWithLabel :value="row.original.progress"
                        :bar-aria-label="`Progress for ${row.original.title}`" />
                </template>
                <template #actions-cell="{ row }">
                    <div class="flex justify-end gap-0.5">
                        <UButton icon="i-lucide-eye" color="neutral" variant="ghost" size="xs"
                            :aria-label="`Open ${row.original.title}`" @click="emit('open', row.original)" />
                        <UButton v-if="canEdit" icon="i-lucide-pencil" color="neutral" variant="ghost" size="xs"
                            :disabled="busy" :aria-label="`Edit ${row.original.title}`"
                            @click="emit('edit', row.original)" />
                        <UDropdownMenu :items="actions(row.original)">
                            <UButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" size="xs"
                                :aria-label="`Actions for ${row.original.title}`" />
                        </UDropdownMenu>
                    </div>
                </template>
                <template #empty>
                    <EmptyState icon="i-lucide-list-tree" :title="filtering ? 'No matching tasks.' : 'No tasks yet.'"
                        :description="filtering ? 'Try another search or clear your filters.' : 'Add a task to start organizing this project.'" />
                </template>
            </TreeTable>
            <div v-if="dragging" data-root-drop
                class="border-default m-3 rounded-lg border border-dashed p-4 text-center text-sm"
                :class="mode === 'root' ? 'border-primary bg-primary/10 text-primary' : 'text-muted'">
                Drop here to move to the top level
            </div>
            <div class="border-default text-muted flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-xs"
                aria-live="polite">
                <span>{{ filtering ? `${filtered.matchedIds.size} of ${totals.total} tasks match` : plural(totals.total,
                    'task') }}
                    ·
                    {{ plural(visibleCount, 'visible row') }}</span>
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-dimmed">Project totals</span><span class="flex items-center gap-1.5"><span
                            class="size-1.5 rounded-full bg-success" />Completed {{ totals.completed }}</span><span
                        class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-primary" />In Progress
                        {{
                        totals.inProgress }}</span><span class="flex items-center gap-1.5"><span
                            class="size-1.5 rounded-full bg-error" />Overdue {{ totals.overdue }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
