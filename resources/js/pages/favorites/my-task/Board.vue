<script setup lang="ts">
import TaskDueDateDialog from '@/components/task/TaskDueDateDialog.vue';
import TaskBoardCard from '@/components/task/TaskBoardCard.vue';
import TaskBoardColumn from '@/components/task/TaskBoardColumn.vue';
import type { BoardCardAction } from '@/components/task/types';
import { useBoardDragDrop } from '@/composables/useBoardDragDrop';
import { statusRequiresDueDate } from '@/lib/statusRules';
import { FetchJsonError, fetchJson } from '@/lib/utils';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { AssignedTask, MyTaskBoardColumn, MyTaskQueryParams, MyTaskStatus } from './types';

interface LocalColumn extends MyTaskBoardColumn {
    /** Halaman terakhir yang sudah diambil kolom ini. Paginasi board dihitung per kolom. */
    page: number;
    loadingMore: boolean;
}

const props = defineProps<{
    board: MyTaskBoardColumn[];
    /** Filter aktif, diteruskan apa adanya ke permintaan "Load more". */
    filterParams: MyTaskQueryParams;
}>();

const emit = defineEmits<{ moved: [] }>();

const toast = useToast();
const overlay = useOverlay();
const dueDateDialog = overlay.create(TaskDueDateDialog);

const columns = ref<LocalColumn[]>([]);
const processingTaskIds = ref(new Set<string>());
const collapsedStatusIds = ref(new Set<string>());

/**
 * Tiap kartu disalin, bukan hanya array kolomnya. Perpindahan optimistis mengubah `task.status`
 * di tempat; kalau objeknya masih satu referensi dengan `props.board`, mutasi itu ikut mengubah
 * prop dan kolom lokal dibangun ulang di tengah request — kartunya melompat balik ke kolom asal.
 * Salinan dangkal sudah cukup karena `status` selalu diganti utuh, tidak pernah diubah isinya.
 */
watch(
    () => props.board,
    (value) => {
        columns.value = value.map((column) => ({
            status: column.status,
            tasks: column.tasks.map((task) => ({ ...task })),
            total: column.total,
            has_more: column.has_more,
            page: 1,
            loadingMore: false,
        }));
    },
    { immediate: true },
);

const toggleColumn = (statusId: string) => {
    if (collapsedStatusIds.value.has(statusId)) {
        collapsedStatusIds.value.delete(statusId);

        return;
    }

    collapsedStatusIds.value.add(statusId);
};

const findTask = (taskId: string) => {
    for (const column of columns.value) {
        const task = column.tasks.find((candidate) => candidate.id === taskId);

        if (task) {
            return { column, task };
        }
    }

    return null;
};

const moveLocally = (task: AssignedTask, from: LocalColumn, to: LocalColumn, status: MyTaskStatus) => {
    from.tasks = from.tasks.filter((candidate) => candidate.id !== task.id);
    from.total = Math.max(0, from.total - 1);

    task.status = status;
    to.tasks = [task, ...to.tasks];
    to.total += 1;
};

const moveTask = async (taskId: string, status: MyTaskStatus) => {
    const found = findTask(taskId);

    if (!found || found.task.status?.id === status.id || processingTaskIds.value.has(taskId)) {
        return;
    }

    const target = columns.value.find((column) => column.status.id === status.id);

    if (!target) {
        return;
    }

    const { column: origin, task } = found;
    const previousStatus = task.status;
    let dueDate = task.due_date;

    /** Status tertentu mewajibkan due date. Tanggalnya diminta lebih dulu daripada ditolak server. */
    if (!dueDate && statusRequiresDueDate(status.name)) {
        const picked = await dueDateDialog.open({ taskTitle: task.title, statusName: status.name });

        if (!picked) {
            return;
        }

        dueDate = picked;
    }

    moveLocally(task, origin, target, status);
    processingTaskIds.value.add(task.id);

    try {
        await fetchJson(route('task.status.update', { task: task.id }), 'PUT', {
            status_id: status.id,
            due_date: dueDate,
        });

        task.due_date = dueDate;
        emit('moved');
        toast.add({ title: 'Success', description: 'Status updated.', color: 'success' });
    } catch (error) {
        if (previousStatus) {
            moveLocally(task, target, origin, previousStatus);
        }

        toast.add({
            title: 'Could not update status',
            description: error instanceof FetchJsonError ? error.message : 'Please try again.',
            color: 'error',
        });
    } finally {
        processingTaskIds.value.delete(task.id);
    }
};

const { dragOverStatusId, isDropTarget, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop, draggedTaskId } = useBoardDragDrop<MyTaskStatus>(
    () => true,
    moveTask,
);

const openTask = (task: AssignedTask) => router.visit(route('task.show', { task: task.id }));

/**
 * My Task hanya membaca pekerjaan lintas project; mengubah, menambah subtask, dan menghapus
 * tetap milik halaman project. Menu klik-kanan dan strip hover membaca daftar yang sama.
 */
const cardActions = (task: AssignedTask): BoardCardAction[] => {
    const actions: BoardCardAction[] = [{ label: 'View detail', icon: 'i-lucide-eye', onSelect: () => openTask(task) }];
    const project = task.project;

    if (project) {
        actions.push({
            label: 'Open project',
            icon: 'i-lucide-folder-open',
            onSelect: () => router.visit(route('project.show.kanban', { encoded: project.id })),
        });
    }

    return actions;
};

const loadMore = async (column: LocalColumn) => {
    if (column.loadingMore || !column.has_more) {
        return;
    }

    column.loadingMore = true;

    try {
        const params = new URLSearchParams({
            ...props.filterParams,
            status_id: column.status.id,
            page: String(column.page + 1),
        });

        const response = await fetch(`${route('task.board')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('request failed');
        }

        const payload = (await response.json()) as { data: AssignedTask[]; has_more: boolean; next_page: number | null };

        column.tasks = [...column.tasks, ...payload.data];
        column.has_more = payload.has_more;
        column.page += 1;
    } catch {
        toast.add({ title: 'Could not load more tasks', description: 'Please try again.', color: 'error' });
    } finally {
        column.loadingMore = false;
    }
};

const remaining = (column: LocalColumn) => Math.max(0, column.total - column.tasks.length);
</script>

<template>
    <!-- `items-start` mencegah flex meratakan tinggi: tiap kolom setinggi isinya sendiri. -->
    <div class="flex items-start gap-4 overflow-x-auto pb-2">
        <TaskBoardColumn
            v-for="column in columns"
            :key="column.status.id"
            :status="column.status"
            :count="column.total"
            :collapsed="collapsedStatusIds.has(column.status.id)"
            :is-drag-over="dragOverStatusId === column.status.id"
            :is-drop-target="isDropTarget(column.status)"
            :is-empty="!column.tasks.length"
            @toggle="toggleColumn(column.status.id)"
            @dragenter="onDragOver($event, column.status)"
            @dragover="onDragOver($event, column.status)"
            @dragleave="onDragLeave($event, column.status)"
            @drop.prevent="onDrop(column.status)"
        >
            <TaskBoardCard
                v-for="task in column.tasks"
                :key="task.id"
                :task="task"
                :actions="cardActions(task)"
                :can-act="!processingTaskIds.has(task.id)"
                :is-dragging="draggedTaskId === task.id"
                @detail="openTask(task)"
                @dragstart="(event: DragEvent) => onDragStart(event, task.id)"
                @dragend="onDragEnd"
            >
                <template #reference>
                    <UBadge v-if="task.is_overdue" color="error" variant="subtle" size="sm" icon="i-lucide-clock" label="Overdue" />
                </template>

                <template #body>
                    <Link
                        v-if="task.project"
                        :href="route('project.show.kanban', { encoded: task.project.id })"
                        class="text-muted hover:text-default inline-flex w-fit items-center gap-1 text-xs transition-colors"
                        @click.stop
                    >
                        <UIcon name="i-lucide-folder" class="size-3 shrink-0" />
                        <span class="truncate">{{ task.project.title }}</span>
                    </Link>
                </template>

                <template #meta>
                    <span v-if="task.sub_task_count" class="flex items-center gap-1">
                        <UIcon name="i-lucide-list-checks" class="size-3.5 shrink-0" />
                        {{ task.sub_task_done_count }}/{{ task.sub_task_count }}
                    </span>

                    <span v-if="task.sequence_number" class="text-dimmed tabular-nums">#{{ task.sequence_number }}</span>
                </template>
            </TaskBoardCard>

            <template #footer>
                <UButton
                    v-if="column.has_more"
                    :label="`Load more (${remaining(column)})`"
                    icon="i-lucide-plus"
                    color="neutral"
                    variant="subtle"
                    size="xs"
                    block
                    :loading="column.loadingMore"
                    @click="loadMore(column)"
                />
            </template>
        </TaskBoardColumn>
    </div>
</template>
