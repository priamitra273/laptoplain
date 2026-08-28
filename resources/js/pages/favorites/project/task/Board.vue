<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import { router, useHttp } from '@inertiajs/vue3';
import moment from 'moment';
import { ref, watch } from 'vue';
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus';
import TaskDueDateDialog from '@/components/TaskDueDateDialog.vue';
import { severityBoxStyle, severityDotStyle, type AssignedTask, type TaskBoardColumn, type TaskStatusOption } from './types';

interface LocalColumn extends TaskBoardColumn {
    page: number;
    loadingMore: boolean;
}

interface Props {
    columns: TaskBoardColumn[];
    filterParams?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    columns: () => [],
    filterParams: () => ({}),
});

const emit = defineEmits<{
    statusUpdate: [];
}>();

const toast = useToast();
const overlay = useOverlay();

const localColumns = ref<LocalColumn[]>([]);
const draggingItem = ref(false);

const collapsedColumns = ref<Set<string>>(new Set());
const toggleColumnCollapse = (statusId: string) => {
    const next = new Set(collapsedColumns.value);
    if (next.has(statusId)) next.delete(statusId);
    else next.add(statusId);
    collapsedColumns.value = next;
};

let preDragSnapshot: Record<string, AssignedTask[]> | null = null;

const cloneColumns = (source: TaskBoardColumn[]): LocalColumn[] =>
    source.map((column) => ({
        status: column.status,
        tasks: JSON.parse(JSON.stringify(column.tasks)),
        total: column.total,
        has_more: column.has_more,
        page: 1,
        loadingMore: false,
    }));

watch(
    () => props.columns,
    (value) => {
        if (!draggingItem.value) {
            localColumns.value = cloneColumns(value ?? []);
        }
    },
    { immediate: true, deep: true },
);

const findColumn = (statusId: string) => localColumns.value.find((column) => column.status.id === statusId);

const requiresDueDate = (statusName?: string) => !!statusName && !['To Do', 'Blocked'].includes(statusName);

const onDragStart = () => {
    draggingItem.value = true;
    const snapshot: Record<string, AssignedTask[]> = {};
    for (const column of localColumns.value) {
        snapshot[column.status.id] = [...column.tasks];
    }
    preDragSnapshot = snapshot;
};

const restoreSnapshot = () => {
    if (!preDragSnapshot) return;
    for (const column of localColumns.value) {
        const snapshot = preDragSnapshot[column.status.id];
        if (snapshot) {
            column.tasks = [...snapshot];
        }
    }
};

const adjustTotals = (fromStatusId: string, toStatusId: string) => {
    const from = findColumn(fromStatusId);
    const to = findColumn(toStatusId);
    if (from) from.total = Math.max(0, from.total - 1);
    if (to) to.total += 1;
};

const statusHttp = useHttp<{ status_id: string; due_date?: string }>({ status_id: '', due_date: undefined });

const doStatusUpdate = (task: AssignedTask, targetStatus: TaskStatusOption, dueDate: string | null) => {
    const fromStatusId = task.status?.id ?? null;

    statusHttp.status_id = targetStatus.id;
    statusHttp.due_date = dueDate ?? undefined;

    statusHttp.put(route('task.status.update', task.id), {
        onSuccess: () => {
            if (fromStatusId && fromStatusId !== targetStatus.id) {
                adjustTotals(fromStatusId, targetStatus.id);
            }
            task.status = targetStatus;
            if (dueDate) task.due_date = dueDate;
            emit('statusUpdate');
        },
        onError: (errors) => {
            const message = Object.values(errors)[0] ?? 'Could not update task status.';
            toast.add({ title: 'Failed', description: String(message), color: 'error' });
            restoreSnapshot();
        },
    });
};

const promptDueDate = (task: AssignedTask, targetStatus: TaskStatusOption): Promise<string | null> => {
    const modal = overlay.create(TaskDueDateDialog, {
        destroyOnClose: true,
        props: { taskTitle: task.title, statusName: targetStatus.name },
    });

    return modal.open();
};

const onCardAdded = async (event: DraggableEvent<AssignedTask>, targetColumn: LocalColumn) => {
    const task = event.data;
    const targetStatus = targetColumn.status;

    if (requiresDueDate(targetStatus.name) && !task.due_date) {
        const dueDate = await promptDueDate(task, targetStatus);
        if (!dueDate) {
            restoreSnapshot();
            return;
        }
        doStatusUpdate(task, targetStatus, dueDate);
        return;
    }

    doStatusUpdate(task, targetStatus, null);
};

const loadMore = async (column: LocalColumn) => {
    if (column.loadingMore || !column.has_more) return;

    column.loadingMore = true;
    try {
        const params = new URLSearchParams({
            status_id: column.status.id,
            page: String(column.page + 1),
            per_page: '10',
            ...props.filterParams,
        });

        const response = await fetch(`${route('task.board')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) throw new Error('request failed');

        const body = await response.json();
        column.tasks.push(...(body.data ?? []));
        column.page += 1;
        column.has_more = !!body.has_more;
    } catch {
        toast.add({ title: 'Failed', description: 'Could not load more tasks.', color: 'error' });
    } finally {
        column.loadingMore = false;
    }
};

const dueDateClasses = (task: AssignedTask) => {
    if (!task.due_date) return 'text-muted';
    if (task.is_overdue) return 'text-error';
    const daysLeft = moment(task.due_date).diff(moment(), 'days');
    if (daysLeft <= 3) return 'text-warning';
    return 'text-muted';
};

const subtaskCounts = (task: AssignedTask) => ({
    total: task.sub_task_count ?? 0,
    done: task.sub_task_done_count ?? 0,
});
</script>

<template>
    <div class="overflow-x-auto py-2">
        <div class="flex flex-nowrap gap-3 pb-1">
            <div
                v-for="column in localColumns"
                :key="column.status.id"
                class="flex shrink-0 flex-col rounded-xl border p-2 transition-[width] duration-150"
                :class="collapsedColumns.has(column.status.id) ? 'w-11' : 'w-75'"
                :style="severityBoxStyle(column.status.severity)"
            >
                <button
                    v-if="collapsedColumns.has(column.status.id)"
                    type="button"
                    class="flex flex-col items-center gap-2 py-2"
                    :title="`Expand ${column.status.name}`"
                    @click="toggleColumnCollapse(column.status.id)"
                >
                    <UIcon name="i-lucide-chevron-right" class="size-3.5 text-muted" />
                    <span class="size-2.5 shrink-0 rounded-full" :style="severityDotStyle(column.status.severity)" />
                    <UBadge color="neutral" variant="subtle" size="sm" class="rounded-full">{{ column.total }}</UBadge>
                    <span class="rotate-180 text-xs font-bold tracking-wide uppercase [writing-mode:vertical-rl]">
                        {{ column.status.name }}
                    </span>
                </button>

                <template v-else>
                    <div class="flex items-center gap-2 py-1.5">
                        <UButton
                            icon="i-lucide-chevron-left"
                            color="neutral"
                            variant="ghost"
                            size="xs"
                            square
                            title="Collapse column"
                            @click="toggleColumnCollapse(column.status.id)"
                        />
                        <span class="size-2.5 shrink-0 rounded-full" :style="severityDotStyle(column.status.severity)" />
                        <span class="flex-1 truncate text-xs font-bold tracking-wide uppercase">{{ column.status.name }}</span>
                        <UBadge color="neutral" variant="subtle" size="sm" class="rounded-full">{{ column.total }}</UBadge>
                    </div>

                    <VueDraggable
                        v-model="column.tasks"
                        class="kanban-col-scroll flex max-h-[70vh] min-h-20 flex-col gap-2 overflow-y-auto pb-1"
                        :animation="150"
                        ghost-class="opacity-40"
                        group="my-task-board"
                        :scroll="true"
                        :scroll-sensitivity="80"
                        :scroll-speed="14"
                        :bubble-scroll="true"
                        @add="(e: DraggableEvent<AssignedTask>) => onCardAdded(e, column)"
                        @start="onDragStart"
                        @end="draggingItem = false"
                    >
                        <div
                            v-if="column.tasks.length === 0"
                            class="flex flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed border-default py-7 text-center"
                        >
                            <UIcon name="i-lucide-inbox" class="size-5 text-muted" />
                            <span class="text-xs text-muted">No tasks</span>
                        </div>

                        <div
                            v-for="item in column.tasks"
                            :key="item.id"
                            class="group relative cursor-grab rounded-xl border-l-2 bg-default shadow-sm transition-all duration-150 active:cursor-grabbing"
                            :class="[item.is_overdue ? 'border-error' : 'border-transparent', draggingItem ? '' : 'hover:shadow-md']"
                            @click="router.get(route('task.show', item.id))"
                        >
                            <div class="p-2.5">
                                <div class="mb-1.5 flex flex-wrap items-center gap-1">
                                    <UBadge v-if="item.type" color="neutral" variant="subtle" size="sm">{{ item.type.name }}</UBadge>
                                    <UBadge v-if="item.priority" :color="severityColor(item.priority.severity)" variant="subtle" size="sm">
                                        {{ item.priority.name }}
                                    </UBadge>
                                    <UBadge v-if="item.is_overdue" color="error" variant="subtle" size="sm" class="ml-auto">
                                        <UIcon name="i-lucide-clock" class="size-3" /> Overdue
                                    </UBadge>
                                </div>

                                <p class="mb-2 line-clamp-2 text-[13px] leading-snug font-medium">{{ item.title }}</p>

                                <ULink
                                    v-if="item.project"
                                    :href="route('project.show.kanban', { encoded: item.project.id })"
                                    class="mb-2 inline-flex items-center gap-1 text-[11px] text-muted"
                                    @click.stop
                                >
                                    <UIcon name="i-lucide-folder" class="size-3" />
                                    <span class="truncate">{{ item.project.title }}</span>
                                </ULink>

                                <div v-if="item.due_date" class="mb-2 flex items-center gap-1 text-[11px]" :class="dueDateClasses(item)">
                                    <UIcon name="i-lucide-calendar" class="size-3" />
                                    <span>{{ moment(item.due_date).format('DD MMM') }}</span>
                                    <span class="opacity-70">· {{ moment(item.due_date).fromNow() }}</span>
                                </div>

                                <div v-if="subtaskCounts(item).total > 0" class="mb-2">
                                    <div class="mb-1 flex items-center justify-between text-[10px] text-muted">
                                        <span class="flex items-center gap-0.5"><UIcon name="i-lucide-list-tree" class="size-3" /> Subtask</span>
                                        <span>{{ subtaskCounts(item).done }}/{{ subtaskCounts(item).total }}</span>
                                    </div>
                                    <UProgress
                                        :model-value="Math.round((subtaskCounts(item).done / subtaskCounts(item).total) * 100)"
                                        color="success"
                                        size="sm"
                                    />
                                </div>

                                <div class="flex items-center justify-between gap-1">
                                    <span v-if="item.sequence_number" class="text-[10px] text-muted">#{{ item.sequence_number }}</span>
                                    <div v-else class="flex-1" />
                                    <UAvatarGroup :max="3" size="3xs">
                                        <UAvatar
                                            v-for="u in item.users ?? []"
                                            :key="u.id"
                                            :src="u.avatar_url ?? undefined"
                                            :alt="u.name"
                                            :text="getInitials(u.name)"
                                        />
                                    </UAvatarGroup>
                                </div>
                            </div>

                            <div
                                class="hidden items-center justify-end gap-0.5 rounded-b-lg border-t border-default bg-elevated/50 px-2 py-1 group-hover:flex"
                            >
                                <ULink :href="route('task.show', item.id)" @click.stop>
                                    <UButton icon="i-lucide-external-link" color="neutral" variant="ghost" size="xs" />
                                </ULink>
                            </div>
                        </div>
                    </VueDraggable>

                    <button
                        v-if="column.has_more"
                        type="button"
                        :disabled="column.loadingMore"
                        class="mx-2 mb-2 flex items-center justify-center gap-1.5 rounded-lg border border-dashed border-default py-2 text-[11px] font-medium text-muted transition-colors hover:text-highlighted disabled:cursor-not-allowed disabled:opacity-60"
                        @click="loadMore(column)"
                    >
                        <UIcon
                            :name="column.loadingMore ? 'i-lucide-loader-2' : 'i-lucide-plus'"
                            :class="['size-3', column.loadingMore && 'animate-spin']"
                        />
                        {{ column.loadingMore ? 'Loading…' : `Load more (${Math.max(0, column.total - column.tasks.length)})` }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.kanban-col-scroll {
    scrollbar-width: thin;
    scrollbar-color: var(--ui-border-accented) transparent;
}
.kanban-col-scroll::-webkit-scrollbar {
    width: 6px;
}
.kanban-col-scroll::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background-color: var(--ui-border-accented);
}
</style>
