<script setup lang="ts">
import TaskDueDateDialog from '@/components/TaskDueDateDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { severityColor } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router, useHttp } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, inject, ref, watch } from 'vue';
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus';
import { severityBoxStyle, severityDotStyle } from '../../project/task/types';
import KanbanQuickAdd from './KanbanQuickAdd.vue';
import type { KanbanBadge, KanbanStatusOption, KanbanTask, KanbanUser } from './types';

interface Props {
    projectId: string;
    sprintId?: string | null;
    selectedTaskId?: string | null;
    tasks: KanbanTask[];
    statuses: KanbanStatusOption[];
    priorities: KanbanBadge[];
    types: KanbanBadge[];
    assignableUsers: KanbanUser[];
}

const props = withDefaults(defineProps<Props>(), {
    sprintId: null,
    selectedTaskId: null,
    tasks: () => [],
    statuses: () => [],
    priorities: () => [],
    types: () => [],
    assignableUsers: () => [],
});

const emit = defineEmits<{
    add: [statusId: string | undefined];
    detail: [task: KanbanTask];
    edit: [task: KanbanTask];
    addSubtask: [task: KanbanTask];
    deleted: [];
    created: [];
}>();

const quickAddStatusId = ref<string | null>(null);
const collapsedColumns = ref<Set<string>>(new Set());
const toggleColumnCollapse = (statusId: string) => {
    const next = new Set(collapsedColumns.value);
    if (next.has(statusId)) next.delete(statusId);
    else next.add(statusId);
    collapsedColumns.value = next;
};

const toast = useToast();
const confirm = useConfirmDialog();

const policy = inject(ProjectPolicyKey, null);
const { canAction, canUpdateTaskStatus } = useProjectPermissions(policy);
const canCreate = computed(() => canAction('task', 'create'));
const canUpdate = computed(() => canAction('task', 'update'));
const canDelete = computed(() => canAction('task', 'delete'));

const searchQuery = ref('');
const filterPriority = ref<string[]>([]);
const filterType = ref<string[]>([]);

const toggleFilterValue = (list: string[], value: string) => (list.includes(value) ? list.filter((item) => item !== value) : [...list, value]);

interface LocalColumn {
    status: KanbanStatusOption;
    tasks: KanbanTask[];
}

const columns = ref<LocalColumn[]>([]);
const draggingItem = ref(false);
let preDragSnapshot: Record<string, KanbanTask[]> | null = null;

const buildColumns = (): LocalColumn[] =>
    props.statuses.map((status) => ({
        status,
        tasks: props.tasks.filter((task) => task.status?.id === status.id),
    }));

watch(
    () => [props.tasks, props.statuses],
    () => {
        if (!draggingItem.value) {
            columns.value = buildColumns();
        }
    },
    { immediate: true, deep: true },
);

const matchesFilters = (task: KanbanTask) => {
    if (searchQuery.value && !task.title.toLowerCase().includes(searchQuery.value.toLowerCase())) return false;
    if (filterPriority.value.length && !filterPriority.value.includes(task.priority?.id ?? '')) return false;
    if (filterType.value.length && !filterType.value.includes(task.type?.id ?? '')) return false;
    return true;
};

const visibleColumns = computed(() => columns.value.map((column) => ({ ...column, tasks: column.tasks.filter(matchesFilters) })));

const hasActiveFilters = computed(() => !!(searchQuery.value || filterPriority.value.length || filterType.value.length));

const onDragStart = () => {
    draggingItem.value = true;
    const snapshot: Record<string, KanbanTask[]> = {};
    for (const column of columns.value) {
        snapshot[column.status.id] = [...column.tasks];
    }
    preDragSnapshot = snapshot;
};

const restoreSnapshot = () => {
    if (!preDragSnapshot) return;
    for (const column of columns.value) {
        const snapshot = preDragSnapshot[column.status.id];
        if (snapshot) column.tasks = [...snapshot];
    }
};

const requiresDueDate = (statusName?: string) => !!statusName && !['To Do', 'Blocked'].includes(statusName);

const statusHttp = useHttp<{ status_id: string; due_date?: string }>({ status_id: '', due_date: undefined });

const doStatusUpdate = (task: KanbanTask, targetStatus: KanbanStatusOption, dueDate: string | null) => {
    statusHttp.status_id = targetStatus.id;
    statusHttp.due_date = dueDate ?? undefined;

    statusHttp.put(route('task.status.update', task.id), {
        onSuccess: () => {
            task.status = targetStatus;
            if (dueDate) task.due_date = dueDate;
        },
        onError: (errors) => {
            const message = Object.values(errors)[0];
            toast.add({ title: 'Failed', description: message ? String(message) : 'Could not update task status.', color: 'error' });
            restoreSnapshot();
        },
    });
};

const overlay = useOverlay();

const promptDueDate = (task: KanbanTask, targetStatus: KanbanStatusOption): Promise<string | null> => {
    const modal = overlay.create(TaskDueDateDialog, {
        destroyOnClose: true,
        props: { taskTitle: task.title, statusName: targetStatus.name },
    });

    return modal.open();
};

const onCardAdded = async (event: DraggableEvent<KanbanTask>, targetColumn: LocalColumn) => {
    const task = event.data;
    const targetStatus = targetColumn.status;

    if (!canUpdateTaskStatus(targetStatus.id)) {
        toast.add({ title: 'Access Denied', description: 'You are not allowed to move a task to this status.', color: 'warning' });
        restoreSnapshot();
        return;
    }

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

const subtaskCounts = (task: KanbanTask) => ({
    total: task.sub_task_recursive.length,
    done: task.sub_task_recursive.filter((child) => child.progress >= 100).length,
});

const dueDateClasses = (task: KanbanTask) => {
    if (!task.due_date) return 'text-muted';
    if (task.is_overdue) return 'text-error';
    const daysLeft = moment(task.due_date).diff(moment(), 'days');
    if (daysLeft <= 3) return 'text-warning';
    return 'text-muted';
};

const priorityIcon = (name?: string) => {
    const normalized = (name ?? '').toLowerCase();
    if (['critical', 'high'].includes(normalized)) return 'i-lucide-chevron-up';
    if (normalized === 'low') return 'i-lucide-chevron-down';
    return 'i-lucide-minus';
};

const handleDelete = async (task: KanbanTask, projectId: string) => {
    const confirmed = await confirm({
        title: 'Delete Task',
        description: `Are you sure want to delete "${task.title}"? This cannot be undone.`,
    });

    if (confirmed) {
        router.delete(route('project.tasks.destroy', { projectEncoded: projectId, task: task.id }), {
            preserveScroll: true,
            onSuccess: () => emit('deleted'),
        });
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="searchQuery" icon="i-lucide-search" placeholder="Search tasks on this board" class="min-w-48 flex-1" />

            <UPopover>
                <UButton
                    label="Priority"
                    trailing-icon="i-lucide-chevron-down"
                    :color="filterPriority.length ? 'primary' : 'neutral'"
                    variant="outline"
                >
                    <template v-if="filterPriority.length" #trailing>
                        <UBadge color="primary" variant="subtle" size="sm" class="tabular-nums">{{ filterPriority.length }}</UBadge>
                    </template>
                </UButton>
                <template #content>
                    <div class="flex w-56 flex-col gap-2 p-3">
                        <UCheckbox
                            v-for="option in priorities"
                            :key="option.id"
                            :model-value="filterPriority.includes(option.id)"
                            :label="option.name"
                            @update:model-value="filterPriority = toggleFilterValue(filterPriority, option.id)"
                        />
                        <p v-if="!priorities.length" class="text-xs text-muted">No priorities configured.</p>
                    </div>
                </template>
            </UPopover>

            <UPopover>
                <UButton label="Type" trailing-icon="i-lucide-chevron-down" :color="filterType.length ? 'primary' : 'neutral'" variant="outline">
                    <template v-if="filterType.length" #trailing>
                        <UBadge color="primary" variant="subtle" size="sm" class="tabular-nums">{{ filterType.length }}</UBadge>
                    </template>
                </UButton>
                <template #content>
                    <div class="flex w-56 flex-col gap-2 p-3">
                        <UCheckbox
                            v-for="option in types"
                            :key="option.id"
                            :model-value="filterType.includes(option.id)"
                            :label="option.name"
                            @update:model-value="filterType = toggleFilterValue(filterType, option.id)"
                        />
                        <p v-if="!types.length" class="text-xs text-muted">No types configured.</p>
                    </div>
                </template>
            </UPopover>

            <UButton
                v-if="hasActiveFilters"
                label="Clear"
                icon="i-lucide-filter-x"
                color="neutral"
                variant="ghost"
                size="sm"
                @click="((searchQuery = ''), (filterPriority = []), (filterType = []))"
            />
        </div>

        <div v-if="statuses.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <UIcon name="i-lucide-inbox" class="size-10 text-muted" />
            <div class="space-y-1">
                <p class="text-base font-medium">No task statuses configured</p>
                <p class="text-sm text-muted">Add at least one Task Status in Master Data before tasks can be tracked here.</p>
            </div>
        </div>

        <div
            v-else-if="tasks.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-default px-6 py-14 text-center"
        >
            <UIcon name="i-lucide-calendar-range" class="size-8 text-muted" />
            <div class="space-y-1.5">
                <p class="text-base font-semibold text-highlighted">Nothing is on the board yet</p>
                <p class="mx-auto max-w-lg text-sm leading-relaxed text-muted">
                    The board only ever shows the batch of work the team is running right now. Tasks that are not in it — in an upcoming batch, or in
                    no batch at all — are safe and waiting in the Backlog and List tabs.
                </p>
            </div>
            <div class="mt-1 flex flex-wrap justify-center gap-2">
                <UButton :to="route('project.show.backlog', { encoded: projectId })" label="Open Backlog to start a batch" />
                <UButton :to="route('project.show.list', { encoded: projectId })" label="See all tasks" color="neutral" variant="outline" />
            </div>
        </div>

        <div v-else class="overflow-x-auto py-2">
            <div class="flex flex-nowrap gap-3 pb-1">
                <div
                    v-for="column in visibleColumns"
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
                        <UBadge color="neutral" variant="subtle" size="sm" class="rounded-full">{{ column.tasks.length }}</UBadge>
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
                            <UBadge color="neutral" variant="subtle" size="sm" class="rounded-full">{{ column.tasks.length }}</UBadge>
                            <UButton
                                v-if="canCreate"
                                icon="i-lucide-plus"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                square
                                title="Quick add"
                                @click="quickAddStatusId = quickAddStatusId === column.status.id ? null : column.status.id"
                            />
                        </div>

                        <div v-if="quickAddStatusId === column.status.id">
                            <KanbanQuickAdd
                                :status="column.status"
                                :project-id="projectId"
                                :sprint-id="sprintId"
                                :priorities="priorities"
                                :types="types"
                                :assignable-users="assignableUsers"
                                @cancel="quickAddStatusId = null"
                                @created="
                                    quickAddStatusId = null;
                                    emit('created');
                                "
                                @open-full="
                                    quickAddStatusId = null;
                                    emit('add', column.status.id);
                                "
                            />
                        </div>

                        <VueDraggable
                            v-model="column.tasks"
                            class="kanban-col-scroll flex max-h-[70vh] min-h-20 flex-col gap-2 overflow-y-auto pb-1"
                            :animation="150"
                            ghost-class="opacity-40"
                            group="project-kanban"
                            :scroll="true"
                            :scroll-sensitivity="80"
                            :scroll-speed="14"
                            :bubble-scroll="true"
                            @add="(e: DraggableEvent<KanbanTask>) => onCardAdded(e, column)"
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
                                :class="[
                                    item.id === selectedTaskId ? 'border-primary ring-1 ring-primary/30' : 'border-transparent',
                                    draggingItem ? '' : 'hover:shadow-md',
                                ]"
                                @click="emit('detail', item)"
                            >
                                <div class="p-2.5">
                                    <div class="mb-1.5 flex flex-wrap items-center gap-1">
                                        <UBadge v-if="item.type" color="neutral" variant="subtle" size="sm">{{ item.type.name }}</UBadge>
                                        <UBadge
                                            v-if="item.priority"
                                            :color="severityColor(item.priority.severity)"
                                            variant="subtle"
                                            size="sm"
                                            :icon="priorityIcon(item.priority.name)"
                                        >
                                            {{ item.priority.name }}
                                        </UBadge>
                                    </div>

                                    <p class="mb-2 line-clamp-2 text-[13px] leading-snug font-medium">{{ item.title }}</p>

                                    <div v-if="item.tags.length" class="mb-2 flex flex-wrap items-center gap-1">
                                        <UBadge v-for="tag in item.tags" :key="tag.id" :color="severityColor(tag.severity)" variant="soft" size="sm">
                                            {{ tag.name }}
                                        </UBadge>
                                    </div>

                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 text-[11px]">
                                            <span v-if="item.due_date" class="flex items-center gap-1" :class="dueDateClasses(item)">
                                                <UIcon name="i-lucide-calendar" class="size-3" />
                                                {{ moment(item.due_date).format('DD MMM') }}
                                            </span>
                                            <span v-if="subtaskCounts(item).total > 0" class="flex items-center gap-1 text-muted">
                                                <UIcon name="i-lucide-list-checks" class="size-3" />
                                                {{ subtaskCounts(item).done }}/{{ subtaskCounts(item).total }}
                                            </span>
                                        </div>

                                        <UAvatarGroup v-if="item.users.length" :max="3" size="3xs">
                                            <UAvatar v-for="user in item.users" :key="user.id" :src="user.avatar_url ?? undefined" :alt="user.name" />
                                        </UAvatarGroup>
                                    </div>
                                </div>

                                <div
                                    class="hidden items-center justify-end gap-0.5 rounded-b-xl border-t border-default bg-elevated/50 px-2 py-1 group-hover:flex"
                                >
                                    <ULink :href="route('task.show', item.id)" @click.stop>
                                        <UButton icon="i-lucide-external-link" color="neutral" variant="ghost" size="xs" title="Open full page" />
                                    </ULink>
                                    <UButton
                                        v-if="canCreate"
                                        icon="i-lucide-list-plus"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        title="Add subtask"
                                        @click.stop="emit('addSubtask', item)"
                                    />
                                    <UButton
                                        v-if="canUpdate"
                                        icon="i-lucide-pencil"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        @click.stop="emit('edit', item)"
                                    />
                                    <UButton
                                        v-if="canDelete"
                                        icon="i-lucide-trash"
                                        color="error"
                                        variant="ghost"
                                        size="xs"
                                        @click.stop="handleDelete(item, projectId)"
                                    />
                                </div>
                            </div>
                        </VueDraggable>

                        <UButton
                            v-if="canCreate && quickAddStatusId !== column.status.id"
                            label="Add task"
                            icon="i-lucide-plus"
                            color="neutral"
                            variant="ghost"
                            size="xs"
                            block
                            class="justify-start"
                            @click="quickAddStatusId = column.status.id"
                        />
                    </template>
                </div>
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
