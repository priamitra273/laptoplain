<script setup lang="ts">
import TaskDueDateDialog from '@/components/task/TaskDueDateDialog.vue';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { FetchJsonError, fetchJson } from '@/lib/utils';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onScopeDispose, ref } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import TaskCreateDrawer from '../task/TaskCreateDrawer.vue';
import type { ListTask } from '../task/types';
import type { ShellProps } from '../types';
import TaskDetailSlideover from '../TaskDetailSlideover.vue';
import TaskEditDrawer from '../TaskEditDrawer.vue';
import { useTaskDelete } from '../useTaskDelete';
import KanbanBoard from './KanbanBoard.vue';
import { statusRequiresDueDate } from '@/lib/statusRules';
import type { KanbanBadge, KanbanStatus, KanbanTask, KanbanUser, QuickAddDraft } from './types';

interface Props extends ShellProps {
    tasks?: KanbanTask[];
    taskStatuses?: KanbanStatus[];
    taskPriorities?: KanbanBadge[];
    taskTypes?: KanbanBadge[];
    taskCategories?: KanbanBadge[];
    tags?: KanbanBadge[];
    assignableUsers?: KanbanUser[];
}

const props = defineProps<Props>();

const toast = useToast();
const overlay = useOverlay();
const { deleteTask: deleteTaskRequest } = useTaskDelete(() => props.project.id);

const { canAction, canUpdateTaskStatus } = useProjectPermissions(props.policy);

const canAct = computed(() => canAction('task', 'update'));
const canCreate = computed(() => canAction('task', 'create'));
const canDelete = computed(() => canAction('task', 'delete'));

/**
 * Sebagian peran hanya boleh menyetel status tertentu, jadi kolom lain harus menolak drop
 * di klien — bukan mengirimnya ke server lalu gagal.
 */
const allowedStatusIds = computed(() => (props.taskStatuses ?? []).filter((status) => canUpdateTaskStatus(status.id)).map((status) => status.id));

/**
 * Status hasil drag yang belum dikonfirmasi server, disimpan per task id. Memakai peta datar
 * seperti ini jauh lebih ringan daripada menyalin seluruh pohon task hanya untuk satu field.
 */
const statusOverrides = ref<Record<string, KanbanBadge>>({});

/** Task yang move/delete-nya sedang diproses — dikunci per-task supaya task lain tetap bisa dikerjakan selagi menunggu. */
const processingTaskIds = ref(new Set<string>());

const busy = ref(false);
const reloadFailed = ref(false);
const initialLoadFailed = ref(false);
const subscriptions: (() => void)[] = [];

/** Data belum tentu bisa dipercaya kalau refresh terakhir gagal, jadi mutasi baru ditahan dulu. */
const boardActionsEnabled = computed(() => !reloadFailed.value);

onMounted(() => {
    const failInitialLoad = () => {
        if (props.tasks === undefined) {
            initialLoadFailed.value = true;

            return false;
        }
    };

    subscriptions.push(router.on('httpException', failInitialLoad), router.on('networkError', failInitialLoad));
});

onScopeDispose(() => subscriptions.forEach((unsubscribe) => unsubscribe()));

const reload = (only: string[]): Promise<boolean> =>
    new Promise((resolve) => {
        let succeeded = false;
        busy.value = true;
        reloadFailed.value = false;
        initialLoadFailed.value = false;

        router.reload({
            only,
            onHttpException: () => false,
            onNetworkError: () => false,
            onSuccess: () => {
                succeeded = true;
            },
            onFinish: () => {
                reloadFailed.value = !succeeded;
                busy.value = false;
                resolve(succeeded);
            },
        });
    });

/** Refresh ringan sesudah mutasi task — cuma `tasks` yang berubah. */
const reloadTasks = () => reload(['tasks']);

/**
 * Retry pemuatan awal: board butuh seluruh grup deferred-nya (bukan cuma `tasks`), kalau tidak
 * status/priority/type/assignee/sprint aktif tetap kosong meski `tasks` sudah berhasil dimuat ulang.
 */
const retryInitialLoad = () =>
    reload(['tasks', 'taskStatuses', 'taskPriorities', 'taskTypes', 'taskCategories', 'tags', 'assignableUsers']);

const taskEditDrawer = overlay.create(TaskEditDrawer);
const dueDateDialog = overlay.create(TaskDueDateDialog);
const taskDetailPanel = overlay.create(TaskDetailSlideover);
const taskCreateDrawer = overlay.create(TaskCreateDrawer);
const kanbanBoardRef = ref<InstanceType<typeof KanbanBoard> | null>(null);

const getJson = async (url: string) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

    if (!response.ok) {
        throw new Error('request failed');
    }

    return (await response.json()).data ?? null;
};

/**
 * Form quick-add tidak menawarkan description, category, tags, atau lampiran — tombol "More
 * options" di situ membuka drawer lengkap ini sebagai gantinya, membawa isian yang sudah
 * diketik di quick-add plus status kolom dan parent-nya supaya konteks tidak hilang. Daftar
 * parent diambil baru di sini karena `tasks` milik Kanban cuma task sprint aktif, bukan
 * seluruh pohon task project.
 */
const openFullCreateForm = async (payload: { status: KanbanStatus; parent: KanbanTask | null; draft: QuickAddDraft }) => {
    let parents: { id: string; title: string; parent_id: string | null }[] = [];

    try {
        parents = (await getJson(route('project.tasks.parent-options', { projectEncoded: props.project.id }))) ?? [];
    } catch {
        /**
         * Diam-diam lanjut dengan daftar kosong akan terlihat seperti project ini memang tidak
         * punya parent task sama sekali — padahal cuma request-nya yang gagal. Batalkan saja;
         * klik "More options" lagi otomatis jadi retry-nya, tanpa perlu draft/state tambahan.
         */
        toast.add({
            title: 'Failed',
            description: 'Could not load parent tasks. Try "More options" again.',
            color: 'error',
        });

        return;
    }

    const changed = await taskCreateDrawer.open({
        projectId: props.project.id,
        project: props.project,
        parent: payload.parent as unknown as ListTask | null,
        parents,
        statuses: props.taskStatuses ?? [],
        priorities: props.taskPriorities ?? [],
        types: props.taskTypes ?? [],
        categories: props.taskCategories ?? [],
        tags: props.tags ?? [],
        assignableUsers: props.assignableUsers ?? [],
        initial: { ...payload.draft, status_id: payload.status.id },
    });

    if (changed) {
        /** Task tersimpan lewat drawer — quick-add & draft lamanya harus ikut tertutup, bukan tetap terbuka. */
        kanbanBoardRef.value?.closeQuickAdd();

        toast.add({ title: 'Success', description: 'Task created.', color: 'success' });

        reload(['tasks', 'tags']);
    }
};

const editTask = async (task: KanbanTask) => {
    const changed = await taskEditDrawer.open({
        projectId: props.project.id,
        project: props.project,
        task,
        priorities: props.taskPriorities ?? [],
        statuses: props.taskStatuses ?? [],
        types: props.taskTypes ?? [],
        categories: props.taskCategories ?? [],
        tags: props.tags ?? [],
        assignableUsers: props.assignableUsers ?? [],
    });

    if (changed) {
        reloadTasks();
    }
};

/** Klik kartu membuka panel baca; tombol di header panel yang meneruskannya ke drawer atau hapus. */
const openTaskDetail = async (task: KanbanTask) => {
    const result = await taskDetailPanel.open({
        task,
        projectId: props.project.id,
        projectTitle: props.project.title,
        assignableUsers: props.assignableUsers ?? [],
        canEdit: canAct.value,
        canDelete: canDelete.value,
    });

    if (result === 'edit') {
        editTask(task);

        return;
    }

    if (result === 'delete') {
        deleteTask(task);

        return;
    }

    /** Klik baris subtask menutup panel ini lalu membuka panel milik subtask tersebut. */
    if (result && typeof result === 'object' && 'open' in result) {
        openTaskDetail(result.open as KanbanTask);
    }
};

const deleteTask = async (task: KanbanTask) => {
    if (processingTaskIds.value.has(task.id)) {
        return;
    }

    processingTaskIds.value.add(task.id);

    try {
        if ((await deleteTaskRequest(task)) !== undefined) {
            await reloadTasks();
        }
    } finally {
        processingTaskIds.value.delete(task.id);
    }
};

const moveTask = async (task: KanbanTask, status: KanbanStatus) => {
    if (processingTaskIds.value.has(task.id)) {
        return;
    }

    const current = statusOverrides.value[task.id] ?? task.status;

    if (current?.id === status.id) {
        return;
    }

    let dueDate = task.due_date;

    /**
     * Sebagian status mewajibkan due date. Daripada menembak server lalu ditolak, tanggalnya
     * diminta lebih dulu — kartu baru berpindah setelah pengguna mengisinya.
     */
    if (!dueDate && statusRequiresDueDate(status.name)) {
        const picked = await dueDateDialog.open({ taskTitle: task.title, statusName: status.name });

        if (!picked) {
            return;
        }

        dueDate = picked;
    }

    statusOverrides.value[task.id] = status;
    processingTaskIds.value.add(task.id);

    try {
        await fetchJson(route('task.status.update', task.id), 'PUT', {
            status_id: status.id,
            due_date: dueDate,
        });

        /**
         * Server sudah menyimpan status baru di titik ini. Override cuma boleh dihapus kalau
         * refresh-nya berhasil — kalau refresh gagal, kartu tanpa override akan balik
         * menampilkan status LAMA dari props walau server sudah punya yang baru.
         */
        const refreshed = await reloadTasks();

        if (refreshed) {
            delete statusOverrides.value[task.id];
        }

        toast.add({ title: 'Success', description: 'Status updated.', color: 'success' });
    } catch (error) {
        if (current) {
            statusOverrides.value[task.id] = current;
        } else {
            delete statusOverrides.value[task.id];
        }

        toast.add({
            title: 'Failed',
            description: error instanceof FetchJsonError ? error.message : 'Could not move the task.',
            color: 'error',
        });
    } finally {
        processingTaskIds.value.delete(task.id);
    }
};
</script>

<template>
    <ProjectShellLayout>
        <Head :title="`Kanban - ${project.title}`" />

        <div class="flex min-w-0 flex-col gap-4">
            <UAlert
                v-if="reloadFailed"
                color="error"
                title="Could not refresh the board."
                description="The board may be out of date. Retry to load the latest changes."
                :actions="[{ label: 'Retry', loading: busy, onClick: reloadTasks }]"
            />
            <UAlert
                v-if="initialLoadFailed"
                color="error"
                title="Could not load the board."
                :actions="[{ label: 'Retry', loading: busy, onClick: retryInitialLoad }]"
            />

            <Deferred v-else :data="['tasks', 'taskStatuses', 'taskPriorities']">
                <template #fallback>
                    <div class="flex gap-4 overflow-hidden" role="status" aria-label="Loading board">
                        <USkeleton v-for="n in 4" :key="n" class="h-72 w-72 shrink-0 rounded-xl" />
                    </div>
                </template>

                <template #rescue="{ reloading }">
                    <UAlert
                        color="error"
                        title="Could not load the board."
                        :actions="[{ label: 'Retry', loading: reloading, onClick: retryInitialLoad }]"
                    />
                </template>

                <KanbanBoard
                    ref="kanbanBoardRef"
                    :project-id="project.id"
                    :tasks="tasks ?? []"
                    :statuses="taskStatuses ?? []"
                    :priorities="taskPriorities ?? []"
                    :types="taskTypes ?? []"
                    :assignable-users="assignableUsers ?? []"
                    :status-overrides="statusOverrides"
                    :allowed-status-ids="allowedStatusIds"
                    :processing-task-ids="processingTaskIds"
                    :can-act="canAct && boardActionsEnabled"
                    :can-create="canCreate && boardActionsEnabled"
                    :can-delete="canDelete && boardActionsEnabled"
                    @move="moveTask"
                    @detail="openTaskDetail"
                    @open="(task: KanbanTask) => router.visit(route('task.show', { task: task.id }))"
                    @edit="editTask"
                    @delete="deleteTask"
                    @created="reloadTasks"
                    @open-full="openFullCreateForm"
                />
            </Deferred>
        </div>
    </ProjectShellLayout>
</template>
