<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { severityColor } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router } from '@inertiajs/vue3';
import {
    getCoreRowModel,
    getExpandedRowModel,
    getSortedRowModel,
    useVueTable,
    type ExpandedState,
    type Row,
    type SortingState,
} from '@tanstack/vue-table';
import moment from 'moment';
import { computed, inject, ref, watch } from 'vue';
import TaskActivityModal from './TaskActivityModal.vue';
import { useTaskDragDrop } from './useTaskDragDrop';
import type { ListTask, TaskBadge } from './types';

interface Props {
    projectId: string;
    tasks: ListTask[];
    taskStatuses?: TaskBadge[];
    taskTypes?: TaskBadge[];
}

const props = withDefaults(defineProps<Props>(), {
    taskStatuses: () => [],
    taskTypes: () => [],
});

const emit = defineEmits<{
    add: [parentId: string | null];
    edit: [task: ListTask];
    moved: [];
    deleted: [];
}>();

const policy = inject(ProjectPolicyKey, null);
const { canAction } = useProjectPermissions(policy);
const canCreate = computed(() => canAction('task', 'create'));
const canUpdate = computed(() => canAction('task', 'update'));
const canDelete = computed(() => canAction('task', 'delete'));
const canMove = computed(() => canAction('task', 'update'));

const toast = useToast();
const confirm = useConfirmDialog();
const overlay = useOverlay();
const activityModal = overlay.create(TaskActivityModal);

const localTasks = ref<ListTask[]>(JSON.parse(JSON.stringify(props.tasks)));
watch(
    () => props.tasks,
    (value) => {
        localTasks.value = JSON.parse(JSON.stringify(value));
    },
);

const globalFilter = ref('');
const statusFilter = ref<string[]>([]);
const typeFilter = ref<string[]>([]);

const hasActiveFilters = computed(() => !!(globalFilter.value || statusFilter.value.length || typeFilter.value.length));

const clearFilters = () => {
    globalFilter.value = '';
    statusFilter.value = [];
    typeFilter.value = [];
};

const toggleFilterValue = (list: string[], value: string) => (list.includes(value) ? list.filter((item) => item !== value) : [...list, value]);

const matchesFilters = (task: ListTask): boolean => {
    if (globalFilter.value && !task.title.toLowerCase().includes(globalFilter.value.toLowerCase())) return false;
    if (statusFilter.value.length && !(task.status && statusFilter.value.includes(task.status.id))) return false;
    if (typeFilter.value.length && !(task.type && typeFilter.value.includes(task.type.id))) return false;
    return true;
};

const filterTree = (tasks: ListTask[]): ListTask[] => {
    if (!hasActiveFilters.value) return tasks;
    const result: ListTask[] = [];
    for (const task of tasks) {
        const children = filterTree(task.sub_task_recursive);
        if (matchesFilters(task) || children.length > 0) {
            result.push({ ...task, sub_task_recursive: children });
        }
    }
    return result;
};

const visibleTasks = computed(() => filterTree(localTasks.value));

// ─── Drag & drop (native HTML5, mirrors the old TreeTable's before/after/inside drop zones) ───
const dragHandleTaskId = ref<string | null>(null);

const {
    draggedKey,
    dropTargetKey,
    dropMode,
    isDraggingTask,
    isRootDropActive,
    onDragStart,
    onDragEnd,
    onRowDragOver,
    onRowDrop,
    onRootDragOver,
    onRootDrop,
    restoreSnapshot,
} = useTaskDragDrop({
    tree: localTasks,
    canMove,
    onMove: (payload) => {
        router.put(
            route('project.tasks.move', { projectEncoded: props.projectId, task: payload.taskId }),
            { parent_id: payload.parentId, position: payload.position },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => emit('moved'),
                onError: () => {
                    restoreSnapshot();
                    toast.add({ title: 'Failed', description: 'Could not move task.', color: 'error' });
                },
            },
        );
    },
});

const onRowMouseDown = (event: MouseEvent, taskId: string) => {
    const target = event.target as Element | null;
    dragHandleTaskId.value = target?.closest('[data-task-drag-handle="true"]') ? taskId : null;
};

// ─── Expand / sort state ───
const expanded = ref<ExpandedState>(true);
const sorting = ref<SortingState>([]);

const table = useVueTable({
    get data() {
        return visibleTasks.value;
    },
    columns: [
        { id: 'title', accessorKey: 'title', header: 'Title', enableSorting: true },
        { id: 'status', accessorFn: (row) => row.status?.name, header: 'Status', enableSorting: true },
        { id: 'type', accessorFn: (row) => row.type?.name, header: 'Type', enableSorting: true },
        { id: 'start_date', accessorKey: 'start_date', header: 'Start Date', enableSorting: true },
        { id: 'due_date', accessorKey: 'due_date', header: 'Due Date', enableSorting: true },
        { id: 'completed_at', accessorKey: 'completed_at', header: 'Completed', enableSorting: true },
        { id: 'progress', accessorKey: 'progress', header: 'Progress', enableSorting: true },
        { id: 'actions', header: 'Actions', enableSorting: false },
    ],
    getSubRows: (row) => row.sub_task_recursive,
    getCoreRowModel: getCoreRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    getSortedRowModel: getSortedRowModel(),
    state: {
        get expanded() {
            return expanded.value;
        },
        get sorting() {
            return sorting.value;
        },
    },
    onExpandedChange: (updater) => {
        expanded.value = typeof updater === 'function' ? updater(expanded.value) : updater;
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
});

const formatDate = (date: string | null): string => (date ? moment(date).format('DD MMM YYYY') : '—');

const rowDropClass = (row: Row<ListTask>): string => {
    if (row.original.id !== dropTargetKey.value || !dropMode.value) return '';
    if (dropMode.value === 'before') return 'shadow-[inset_0_2px_0_0_var(--ui-primary)]';
    if (dropMode.value === 'after') return 'shadow-[inset_0_-2px_0_0_var(--ui-primary)]';
    return 'bg-primary/10 shadow-[inset_0_2px_0_0_var(--ui-primary),inset_0_-2px_0_0_var(--ui-primary)]';
};

// ─── Row actions ───
const openActivityLog = (task: ListTask) => activityModal.open({ taskId: task.id, taskTitle: task.title });

const removeTask = async (task: ListTask) => {
    const confirmed = await confirm({
        title: 'Remove Task',
        description: `Remove task "${task.title}"? This action cannot be undone.`,
    });

    if (confirmed) {
        router.delete(route('project.tasks.destroy', { projectEncoded: props.projectId, task: task.id }), {
            preserveScroll: true,
            onSuccess: () => emit('deleted'),
            onError: () => toast.add({ title: 'Failed', description: 'Could not delete task.', color: 'error' }),
        });
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-semibold">Tasks</h3>
            <UButton v-if="canCreate" label="Add Task" icon="i-lucide-plus" @click="emit('add', null)" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search by title..." class="w-full" />

            <UPopover>
                <UButton label="Status" icon="i-lucide-list-filter" :color="statusFilter.length ? 'primary' : 'neutral'" variant="outline" block>
                    <template v-if="statusFilter.length" #trailing>
                        <UBadge color="primary" variant="subtle" size="sm">{{ statusFilter.length }}</UBadge>
                    </template>
                </UButton>
                <template #content>
                    <div class="flex w-56 flex-col gap-2 p-3">
                        <UCheckbox
                            v-for="option in taskStatuses"
                            :key="option.id"
                            :model-value="statusFilter.includes(option.id)"
                            :label="option.name"
                            @update:model-value="statusFilter = toggleFilterValue(statusFilter, option.id)"
                        />
                    </div>
                </template>
            </UPopover>

            <UPopover>
                <UButton label="Type" icon="i-lucide-list-filter" :color="typeFilter.length ? 'primary' : 'neutral'" variant="outline" block>
                    <template v-if="typeFilter.length" #trailing>
                        <UBadge color="primary" variant="subtle" size="sm">{{ typeFilter.length }}</UBadge>
                    </template>
                </UButton>
                <template #content>
                    <div class="flex w-56 flex-col gap-2 p-3">
                        <UCheckbox
                            v-for="option in taskTypes"
                            :key="option.id"
                            :model-value="typeFilter.includes(option.id)"
                            :label="option.name"
                            @update:model-value="typeFilter = toggleFilterValue(typeFilter, option.id)"
                        />
                    </div>
                </template>
            </UPopover>
        </div>

        <div v-if="hasActiveFilters" class="flex justify-end">
            <UButton label="Clear Filters" icon="i-lucide-filter-x" color="neutral" variant="ghost" size="sm" @click="clearFilters" />
        </div>

        <UCard :ui="{ root: 'p-0', body: 'p-0 sm:p-0' }">
            <div
                class="overflow-x-auto rounded-lg transition-colors"
                :class="isRootDropActive ? 'ring-2 ring-primary/60' : ''"
                data-task-drop-root="true"
                @dragover.prevent="onRootDragOver"
                @dragenter.prevent="onRootDragOver"
                @drop.stop.prevent="onRootDrop"
            >
                <table class="w-full min-w-[900px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-default bg-elevated/50">
                            <th class="w-8 px-2 py-2.5" />
                            <th
                                v-for="header in table.getHeaderGroups()[0].headers"
                                :key="header.id"
                                class="px-3 py-2.5 text-left text-xs font-semibold tracking-wide text-muted uppercase select-none"
                                :class="[header.column.id === 'title' ? 'min-w-60' : '', header.column.getCanSort() ? 'cursor-pointer' : '']"
                                @click="header.column.getToggleSortingHandler()?.($event)"
                            >
                                <template v-if="header.column.id === 'actions'">Actions</template>
                                <span v-else class="inline-flex items-center gap-1">
                                    {{ header.column.columnDef.header }}
                                    <UIcon v-if="header.column.getIsSorted() === 'asc'" name="i-lucide-arrow-up" class="size-3" />
                                    <UIcon v-else-if="header.column.getIsSorted() === 'desc'" name="i-lucide-arrow-down" class="size-3" />
                                    <UIcon v-else-if="header.column.getCanSort()" name="i-lucide-arrow-up-down" class="size-3 opacity-40" />
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody v-if="table.getRowModel().rows.length" class="divide-y divide-default">
                        <tr
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            data-task-drop-row="true"
                            :draggable="canMove && dragHandleTaskId === row.original.id"
                            class="transition-colors hover:bg-elevated/40"
                            :class="[rowDropClass(row), draggedKey === row.original.id ? 'opacity-40' : '']"
                            @mousedown="onRowMouseDown($event, row.original.id)"
                            @dragstart="(event) => onDragStart(event, row.original)"
                            @dragover="(event) => onRowDragOver(event, row.original)"
                            @dragenter="(event) => onRowDragOver(event, row.original)"
                            @drop="(event) => onRowDrop(event, row.original)"
                            @dragend="onDragEnd"
                        >
                            <td class="px-2 py-2 align-middle">
                                <UIcon
                                    v-if="canMove"
                                    name="i-lucide-grip-vertical"
                                    data-task-drag-handle="true"
                                    class="size-4 shrink-0 cursor-grab text-muted hover:text-default active:cursor-grabbing"
                                    :class="draggedKey === row.original.id ? 'text-primary' : ''"
                                    title="Drag to reorder / nest"
                                />
                            </td>

                            <td class="px-3 py-2 align-middle">
                                <div class="flex items-center gap-1.5" :style="{ paddingLeft: `${row.depth * 20}px` }">
                                    <button
                                        v-if="row.getCanExpand()"
                                        type="button"
                                        class="flex size-4 shrink-0 items-center justify-center text-muted hover:text-default"
                                        @click.stop="row.toggleExpanded()"
                                    >
                                        <UIcon :name="row.getIsExpanded() ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-3.5" />
                                    </button>
                                    <span v-else class="size-4 shrink-0" />

                                    <span :title="row.original.title" class="max-w-[26rem] truncate" :class="isDraggingTask ? 'select-none' : ''">
                                        {{ row.original.title }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-3 py-2 align-middle">
                                <UBadge v-if="row.original.status" :color="severityColor(row.original.status.severity)" variant="subtle" size="sm">
                                    {{ row.original.status.name }}
                                </UBadge>
                                <span v-else class="text-muted">—</span>
                            </td>

                            <td class="px-3 py-2 align-middle">
                                <UBadge v-if="row.original.type" color="neutral" variant="subtle" size="sm">{{ row.original.type.name }}</UBadge>
                                <span v-else class="text-muted">—</span>
                            </td>

                            <td class="px-3 py-2 align-middle whitespace-nowrap">{{ formatDate(row.original.start_date) }}</td>

                            <td class="px-3 py-2 align-middle whitespace-nowrap">
                                <span v-if="!row.original.due_date" class="text-muted">—</span>
                                <span v-else-if="row.original.is_overdue" class="inline-flex items-center gap-1 font-medium text-error">
                                    <UIcon name="i-lucide-alert-circle" class="size-3.5" />
                                    {{ formatDate(row.original.due_date) }}
                                </span>
                                <span v-else>{{ formatDate(row.original.due_date) }}</span>
                            </td>

                            <td class="px-3 py-2 align-middle whitespace-nowrap">{{ formatDate(row.original.completed_at) }}</td>

                            <td class="px-3 py-2 align-middle">
                                <div class="flex min-w-32 items-center gap-2">
                                    <UProgress :model-value="row.original.progress" size="sm" />
                                    <span class="shrink-0 text-xs tabular-nums">{{ Math.round(row.original.progress) }}%</span>
                                </div>
                            </td>

                            <td class="px-3 py-2 align-middle">
                                <div class="flex items-center gap-0.5">
                                    <UButton
                                        :to="route('task.show', row.original.id)"
                                        icon="i-lucide-eye"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        title="View Task"
                                    />
                                    <UButton
                                        v-if="canUpdate"
                                        icon="i-lucide-pencil"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        title="Edit Task"
                                        @click="emit('edit', row.original)"
                                    />
                                    <UDropdownMenu
                                        :items="[
                                            [
                                                {
                                                    label: 'Add Subtask',
                                                    icon: 'i-lucide-plus',
                                                    disabled: !canCreate,
                                                    onSelect: () => emit('add', row.original.id),
                                                },
                                            ],
                                            [{ label: 'History Log', icon: 'i-lucide-history', onSelect: () => openActivityLog(row.original) }],
                                            [
                                                {
                                                    label: 'Delete',
                                                    icon: 'i-lucide-trash',
                                                    disabled: !canDelete,
                                                    color: 'error',
                                                    onSelect: () => removeTask(row.original),
                                                },
                                            ],
                                        ]"
                                        :content="{ align: 'end' }"
                                    >
                                        <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs" title="More actions" />
                                    </UDropdownMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
                                    <UIcon name="i-lucide-inbox" class="size-8 text-muted" />
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">No tasks yet</p>
                                        <p class="text-sm text-muted">Create the first task to start tracking progress for this project.</p>
                                    </div>
                                    <UButton v-if="canCreate" label="Add Task" icon="i-lucide-plus" size="sm" @click="emit('add', null)" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </UCard>
    </div>
</template>
