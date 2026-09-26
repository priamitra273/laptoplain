<script setup lang="ts">
import EmptyState from '@/components/common/EmptyState.vue';
import FilterResetButton from '@/components/form/FilterResetButton.vue';
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import TaskBoardCard from '@/components/task/TaskBoardCard.vue';
import TaskBoardColumn from '@/components/task/TaskBoardColumn.vue';
import type { BoardCardAction } from '@/components/task/types';
import { useBoardDragDrop } from '@/composables/useBoardDragDrop';
import { isTaskStatusDone, plural } from '@/lib/utils';
import { usePage } from '@inertiajs/vue3';
import { useSessionStorage } from '@vueuse/core';
import { computed, ref } from 'vue';
import KanbanQuickAdd from './KanbanQuickAdd.vue';
import type { KanbanBadge, KanbanStatus, KanbanTask, KanbanUser, QuickAddDraft } from './types';

const props = withDefaults(
    defineProps<{
        projectId: string;
        tasks: KanbanTask[];
        statuses: KanbanStatus[];
        priorities: KanbanBadge[];
        types: KanbanBadge[];
        assignableUsers: KanbanUser[];
        statusOverrides: Record<string, KanbanBadge>;
        allowedStatusIds: string[];
        /** Task yang request-nya (move/delete) sedang berjalan — dikunci per-task, task lain tetap bisa dikerjakan. */
        processingTaskIds?: Set<string>;
        canAct?: boolean;
        canCreate?: boolean;
        canDelete?: boolean;
    }>(),
    { canAct: false, canCreate: false, canDelete: false, processingTaskIds: () => new Set() },
);

const emit = defineEmits<{
    move: [task: KanbanTask, status: KanbanStatus];
    detail: [task: KanbanTask];
    open: [task: KanbanTask];
    edit: [task: KanbanTask];
    delete: [task: KanbanTask];
    created: [];
    openFull: [payload: { status: KanbanStatus; parent: KanbanTask | null; draft: QuickAddDraft }];
}>();

const quickAddStatusId = ref<string | null>(null);

/** Terisi hanya saat quick-add dibuka lewat tombol subtask di sebuah kartu. */
const quickAddParent = ref<KanbanTask | null>(null);

const canQuickAdd = computed(() => props.canCreate);

const closeQuickAdd = () => {
    quickAddStatusId.value = null;
    quickAddParent.value = null;
};

/** Induk dikosongkan lebih dulu supaya form tidak membawa sisa dari pembukaan sebelumnya. */
const openQuickAdd = (statusId: string) => {
    quickAddParent.value = null;
    quickAddStatusId.value = statusId;
};

/**
 * Form subtask dibuka di kolom kartu induknya supaya muncul dekat kartu itu, dan statusnya
 * ikut kolom tersebut — bukan dipaksa ke status awal tertentu.
 *
 * Saat statusnya sama dengan yang sudah terbuka di kolom ini (task biasa → subtask, atau
 * sebaliknya), `quickAddStatusId` tidak berubah — hanya `quickAddParent`. `<KanbanQuickAdd>`
 * di bawah diberi `:key` yang ikut memuat induknya supaya Vue memasang ulang instance
 * komponennya, bukan sekadar mengganti prop pada instance yang sama. Tanpa itu, draft form
 * (judul, type, dsb.) yang sudah diketik akan terbawa ke sesi berikutnya walau induknya sudah
 * berganti.
 */
const startSubtask = (task: KanbanTask, status: KanbanStatus) => {
    quickAddParent.value = task;
    quickAddStatusId.value = status.id;
};

/** Dipanggil parent setelah task berhasil dibuat lewat drawer "More options", supaya quick-add + draft-nya ikut tertutup. */
defineExpose({ closeQuickAdd });

/**
 * Menu klik-kanan dan strip hover membaca daftar yang sama, supaya keduanya tidak bisa
 * berbeda saat aksinya ditambah atau gate izinnya berubah.
 */
const cardActions = (task: KanbanTask, status: KanbanStatus): BoardCardAction[] => {
    const actions: BoardCardAction[] = [
        {
            label: 'View detail',
            icon: 'i-lucide-eye',
            onSelect: () => emit('open', task),
        },
    ];

    if (props.canAct) {
        actions.push({
            label: 'Edit',
            icon: 'i-lucide-pencil',
            onSelect: () => emit('edit', task),
        });
    }

    if (canQuickAdd.value) {
        actions.push({
            label: 'Add subtask',
            icon: 'i-lucide-git-branch-plus',
            onSelect: () => startSubtask(task, status),
        });
    }

    if (props.canDelete) {
        actions.push({
            label: 'Delete',
            icon: 'i-lucide-trash-2',
            color: 'error',
            onSelect: () => emit('delete', task),
        });
    }

    return actions;
};

/**
 * `TaskCardData` tidak membawa `completed_at`, jadi selesai-tidaknya subtask dibaca dari nama
 * statusnya — sama seperti perhitungan progress sprint di tab Backlog.
 */
const subtaskProgress = (task: KanbanTask) => {
    const list = task.sub_task_recursive ?? [];

    return {
        total: list.length,
        done: list.filter((subtask) => isTaskStatusDone(subtask.status?.name)).length,
    };
};

const onCreated = () => {
    closeQuickAdd();
    emit('created');
};

/**
 * Sama seperti filter tab List: disimpan per user+project supaya bertahan saat pindah tab
 * lalu kembali, bukan state lokal yang hilang setiap kali komponen ini dipasang ulang.
 */
const page = usePage();
const userId = computed(() => String((page.props.auth as { user: { id: string } }).user.id));
const search = useSessionStorage(`project-kanban:${props.projectId}:${userId.value}:search`, '');
const priorityFilter = useSessionStorage<string | null>(`project-kanban:${props.projectId}:${userId.value}:priority`, null);

const filtering = computed(() => !!search.value.trim() || !!priorityFilter.value);

const clearFilters = () => {
    search.value = '';
    priorityFilter.value = null;
};

/**
 * Filter aktif memakai `soft`, bukan `solid`: pil priority berisi `PriorityIcon` yang punya
 * warnanya sendiri, dan latar pekat membuatnya tidak terbaca.
 */
const filterPillProps = (value: string | null) =>
    priorityFilter.value === value ? ({ color: 'primary', variant: 'soft' } as const) : ({ color: 'neutral', variant: 'outline' } as const);
const collapsedColumns = ref<Set<string>>(new Set());

const filteredTasks = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.tasks.filter((task) => {
        const matchesSearch = !query || task.title.toLowerCase().includes(query);
        const matchesPriority = !priorityFilter.value || task.priority?.id === priorityFilter.value;

        return matchesSearch && matchesPriority;
    });
});

/** Status tampilan: hasil drag yang belum dikonfirmasi server menang atas status dari props. */
const statusFor = (task: KanbanTask) => props.statusOverrides[task.id] ?? task.status;

const columns = computed(() =>
    props.statuses.map((status) => ({
        status,
        tasks: filteredTasks.value.filter((task) => statusFor(task)?.id === status.id),
    })),
);

const toggleColumn = (statusId: string) => {
    if (collapsedColumns.value.has(statusId)) {
        collapsedColumns.value.delete(statusId);

        return;
    }

    collapsedColumns.value.add(statusId);
};

const canDropInto = (status: KanbanStatus) => props.canAct && props.allowedStatusIds.includes(status.id);

const { draggedTaskId, dragOverStatusId, isDropTarget, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop } = useBoardDragDrop<KanbanStatus>(
    canDropInto,
    (taskId, status) => {
        const task = props.tasks.find((candidate) => candidate.id === taskId);

        if (task) {
            emit('move', task, status);
        }
    },
);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="search" icon="i-lucide-search" placeholder="Search tasks on this board" class="w-full sm:w-72" />

            <UButton
                label="All"
                v-bind="filterPillProps(null)"
                size="sm"
                class="rounded-full"
                :aria-pressed="priorityFilter === null"
                @click="priorityFilter = null"
            />

            <UButton
                v-for="priority in priorities"
                :key="priority.id"
                v-bind="filterPillProps(priority.id)"
                size="sm"
                class="rounded-full"
                :aria-pressed="priorityFilter === priority.id"
                @click="priorityFilter = priority.id"
            >
                <PriorityIcon :label="priority.name" :severity="priority.severity" />
            </UButton>

            <FilterResetButton v-if="filtering" @click="clearFilters" />

            <span class="text-muted ms-auto text-sm whitespace-nowrap">
                {{ filteredTasks.length }} of {{ plural(tasks.length, 'task') }}
            </span>
        </div>

        <!-- `items-start` mencegah flex meratakan tinggi: tiap kolom setinggi isinya sendiri. -->
        <div class="flex items-start gap-4 overflow-x-auto pb-2">
            <TaskBoardColumn
                v-for="column in columns"
                :key="column.status.id"
                :status="column.status"
                :count="column.tasks.length"
                :collapsed="collapsedColumns.has(column.status.id)"
                :is-drag-over="dragOverStatusId === column.status.id"
                :is-drop-target="isDropTarget(column.status)"
                :is-empty="!column.tasks.length && quickAddStatusId !== column.status.id"
                @toggle="toggleColumn(column.status.id)"
                @dragenter="onDragOver($event, column.status)"
                @dragover="onDragOver($event, column.status)"
                @dragleave="onDragLeave($event, column.status)"
                @drop.prevent="onDrop(column.status)"
            >
                <template #header-actions>
                    <UButton
                        v-if="canQuickAdd"
                        icon="i-lucide-plus"
                        color="neutral"
                        variant="ghost"
                        size="xs"
                        square
                        :aria-label="`Add task to ${column.status.name}`"
                        @click="openQuickAdd(column.status.id)"
                    />
                </template>

                <template #lead>
                    <KanbanQuickAdd
                        v-if="quickAddStatusId === column.status.id"
                        :key="quickAddParent?.id ?? 'task'"
                        :project-id="projectId"
                        :status="column.status"
                        :types="types"
                        :priorities="priorities"
                        :assignable-users="assignableUsers"
                        :parent-task="quickAddParent"
                        @created="onCreated"
                        @cancel="closeQuickAdd()"
                        @open-full="(draft) => emit('openFull', { status: column.status, parent: quickAddParent, draft })"
                    />
                </template>

                <template #default>
                    <TaskBoardCard
                        v-for="task in column.tasks"
                        :key="task.id"
                        :task="task"
                        :actions="cardActions(task, column.status)"
                        :can-act="canAct && !processingTaskIds.has(task.id)"
                        :is-dragging="draggedTaskId === task.id"
                        @detail="emit('detail', task)"
                        @dragstart="(event: DragEvent) => onDragStart(event, task.id)"
                        @dragend="onDragEnd"
                    >
                        <template #reference>
                            <span class="text-dimmed text-xs tabular-nums">{{ task.code }}</span>
                        </template>

                        <template #meta>
                            <span class="flex items-center gap-1" :title="plural(task.comments_count, 'comment')">
                                <UIcon name="i-lucide-message-square" class="size-3.5 shrink-0" />
                                {{ task.comments_count }}
                            </span>

                            <span v-if="task.sub_task_recursive.length" class="flex items-center gap-1">
                                <UIcon name="i-lucide-list-checks" class="size-3.5 shrink-0" />
                                {{ subtaskProgress(task).done }}/{{ subtaskProgress(task).total }}
                            </span>
                        </template>
                    </TaskBoardCard>
                </template>

                <template #footer>
                    <UButton
                        v-if="canQuickAdd && quickAddStatusId !== column.status.id"
                        label="Add task"
                        icon="i-lucide-plus"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        block
                        class="justify-start"
                        @click="openQuickAdd(column.status.id)"
                    />
                </template>
            </TaskBoardColumn>

            <EmptyState
                v-if="!columns.length"
                icon="i-lucide-columns-3"
                title="No task statuses configured."
                description="Add task statuses in master data to use the board."
            />
        </div>
    </div>
</template>
