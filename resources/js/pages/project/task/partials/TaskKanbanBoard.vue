<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { severityClasses } from '@/lib/severity';
import { ProjectPolicyKey } from '@/types/type';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { Epic, Task, TaskPriority, TaskStatus, TaskType, User } from '../..';
import TaskKanbanCard from './kanban/TaskKanbanCard.vue';
import TaskKanbanColumn from './kanban/TaskKanbanColumn.vue';
import TaskKanbanDetailPanel from './kanban/TaskKanbanDetailPanel.vue';
import TaskKanbanQuickAdd from './kanban/TaskKanbanQuickAdd.vue';
import TaskKanbanToolbar from './kanban/TaskKanbanToolbar.vue';

interface Props {
    projectId: string;
    tasks: Task[];
    epicTasks: Epic[];
    statuses: TaskStatus[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    assignableUsers?: User[];
}

interface Emits {
    statusUpdate: [taskId: string, newStatusId: string];
    add: [parentId: string | null, statusId?: string];
    edit: [task: Task, parentId: string | null];
}

interface ProgressDialog {
    visible: boolean;
    task: Task | null;
    newStatusId: string | null;
    dueDate: Date | null;
    snapshot: Record<string, Task[]> | null;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const policy = inject(ProjectPolicyKey, null);

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const { canAction, canUpdateTaskStatus } = useProjectPermissions(policy);

// ─── State ────────────────────────────────────────────────────────────────────
const grouped = ref<Record<string, Task[]>>({});
const draggingItem = ref(false);
const draggingTaskId = ref<string | null>(null);
const searchQuery = ref('');
const filterAssignee = ref<string[]>([]);
const filterPriority = ref<string[]>([]);
const filterType = ref<string[]>([]);
const collapsedCols = ref<Set<string>>(new Set());
const preDragSnapshot = ref<Record<string, Task[]> | null>(null);

// Quick-add per column
const quickAddStatus = ref<string | null>(null);
const quickAddLoading = ref(false);
const quickErrors = ref<Record<string, string>>({});

// Detail slide-over
const detailPanel = ref<{ visible: boolean; task: Task | null }>({ visible: false, task: null });

// Context menu
const cardMenu = ref();
const cardMenuTask = ref<Task | null>(null);
const cardMenuItems = computed(() => [
    {
        label: 'View Detail',
        icon: 'pi pi-eye',
        command: () => {
            detailPanel.value = { visible: true, task: cardMenuTask.value! };
        },
    },
    {
        label: 'Edit Task',
        icon: 'pi pi-pencil',
        command: () => emit('edit', cardMenuTask.value!, cardMenuTask.value!.parent_id),
        disabled: !canTaskUpdate.value,
    },
    { label: 'Add Subtask', icon: 'pi pi-sitemap', command: () => emit('add', cardMenuTask.value!.id), disabled: !canTaskCreate.value },
    { separator: true },
    { label: 'Delete Task', icon: 'pi pi-trash', command: () => deleteTask(cardMenuTask.value!), disabled: !canTaskDelete.value },
]);

// ─── Due Date Dialog ─────────────────────────────────────────────────────────
const inProgressDialog = ref<ProgressDialog>({
    visible: false,
    task: null,
    newStatusId: null,
    dueDate: null,
    snapshot: null,
});

const inProgressLoading = ref(false);

// ─── Computed ─────────────────────────────────────────────────────────────────
const canTaskCreate = computed(() => canAction('task', 'create'));
const canTaskUpdate = computed(() => canAction('task', 'update'));
const canTaskDelete = computed(() => canAction('task', 'delete'));
const canAct = computed(() => canTaskCreate.value || canTaskUpdate.value || canTaskDelete.value);

const currentUser = computed(() => page.props.auth?.user as User | undefined);

const allAssignees = computed(() => {
    const map = new Map<string, User>();
    props.tasks.forEach((t) => (t.users || []).forEach((u) => map.set(u.id as string, u as unknown as User)));

    return Array.from(map.values());
});

const userOptions = computed<User[]>(() => {
    if (props.assignableUsers?.length) return props.assignableUsers;

    return allAssignees.value;
});

const filteredGrouped = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    const result: Record<string, Task[]> = {};
    for (const [sid, tasks] of Object.entries(grouped.value)) {
        result[sid] = tasks.filter((t) => {
            const matchQ = !q || t.title.toLowerCase().includes(q);
            const matchA = !filterAssignee.value.length || (t.users || []).some((u) => filterAssignee.value.includes(u.id as string));
            const matchP = !filterPriority.value.length || filterPriority.value.includes(t.priority?.id ?? '');
            const matchT = !filterType.value.length || filterType.value.includes(t.type?.id ?? '');
            return matchQ && matchA && matchP && matchT;
        });
    }
    return result;
});

const totalTasks = computed(() => props.tasks.length);

const doneTasks = computed(() => {
    const doneStatus = props.statuses.find((s) => s.name?.toLowerCase().includes('done') || s.name?.toLowerCase().includes('complete'));
    return doneStatus ? (grouped.value[doneStatus.id] || []).length : 0;
});

const boardProgress = computed(() => (totalTasks.value ? Math.round((doneTasks.value / totalTasks.value) * 100) : 0));

const getMeta = (sid: string) => severityClasses[props.statuses.find((s) => s.id === sid)?.severity || 'default'] || severityClasses.default;
const getStatusName = (id: string) => props.statuses.find((s) => s.id === id)?.name || 'Unknown';
const requiresDueDateForStatus = (statusName?: string) => !!statusName && !['To Do', 'Blocked'].includes(statusName);

// ─── Helpers ─────────────────────────────────────────────────────────────
const isOverdue = (task: Task) =>
    !!task.due_date &&
    moment(task.due_date).isBefore(moment(), 'day') &&
    !props.statuses
        .find((s) => s.id === task.status?.id)
        ?.name?.toLowerCase()
        .includes('done');

const subtaskCount = (task: Task) => task.sub_task_recursive?.length || 0;
const doneSubtaskCount = (task: Task) => {
    const ds = props.statuses.find((s) => s.name?.toLowerCase().includes('done'));
    return ds ? (task.sub_task_recursive || []).filter((s) => s.status?.id === ds.id).length : 0;
};

// ─── Board build ──────────────────────────────────────────────────────────────
const buildGrouped = () => {
    const g: Record<string, Task[]> = {};
    props.statuses.forEach((s) => (g[s.id] = []));
    props.tasks.forEach((t) => {
        const sid = t.status?.id;
        if (sid && g[sid] !== undefined) g[sid].push(t);
    });
    return g;
};

onMounted(() => {
    grouped.value = buildGrouped();
});

watch(
    () => props.tasks,
    () => {
        if (!draggingItem.value) grouped.value = buildGrouped();
    },
    { deep: true },
);

// ─── Drag & drop ─────────────────────────────────────────────────────────────
const onGroupChange = async (task: Task, newStatusId: string) => {
    if (!canUpdateTaskStatus(newStatusId)) {
        toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You are not allowed to set this status', life: 3000 });
        grouped.value = buildGrouped();
        return;
    }

    const targetStatus = props.statuses.find((s) => s.id === newStatusId);
    const dueDateMissing = !task.due_date;

    if (requiresDueDateForStatus(targetStatus?.name) && dueDateMissing) {
        inProgressDialog.value = {
            visible: true,
            task,
            newStatusId,
            dueDate: null,
            snapshot: preDragSnapshot.value,
        };

        return;
    }

    await doStatusUpdate(task, newStatusId, null);
};

const onDragStart = (e: any) => {
    draggingItem.value = true;
    draggingTaskId.value = e.item?.dataset?.taskId || null;

    const snapshot: Record<string, Task[]> = {};

    for (const [sid, tasks] of Object.entries(grouped.value)) {
        snapshot[sid] = [...tasks];
    }

    preDragSnapshot.value = snapshot;
};

const getErrorMessage = (error: any, fallback: string) => {
    const errors = error?.response?.data?.errors as Record<string, string[]> | undefined;
    if (errors) {
        const firstError = Object.values(errors)[0]?.[0];
        if (firstError) return firstError;
    }

    return error?.response?.data?.message || fallback;
};

const doStatusUpdate = async (task: Task, newStatusId: string, dueDate: string | null) => {
    try {
        await axios.post(route('task.status.update', task.id), {
            _method: 'PUT',
            status_id: newStatusId,
            ...(dueDate ? { due_date: dueDate } : {}),
        });

        emit('statusUpdate', task.id, newStatusId);

        toast.add({ severity: 'success', summary: 'Status updated', life: 1800 });
    } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Failed to update status', detail: getErrorMessage(error, 'Failed to update status'), life: 3000 });
        grouped.value = preDragSnapshot.value ?? buildGrouped();
    }
};

const submitInProgressDialog = async () => {
    if (!inProgressDialog.value.dueDate) {
        toast.add({ severity: 'warn', summary: 'Due Date Required', detail: 'Please select a due date.', life: 3000 });
        return;
    }

    inProgressLoading.value = true;
    const formattedDueDate = moment(inProgressDialog.value.dueDate).format('YYYY-MM-DD');

    await doStatusUpdate(inProgressDialog.value.task!, inProgressDialog.value.newStatusId!, formattedDueDate);

    inProgressLoading.value = false;
    inProgressDialog.value = { visible: false, task: null, newStatusId: null, dueDate: null, snapshot: null };
};

const cancelInProgressDialog = () => {
    if (inProgressDialog.value.snapshot) {
        grouped.value = inProgressDialog.value.snapshot;
    }
    inProgressDialog.value = { visible: false, task: null, newStatusId: null, dueDate: null, snapshot: null };
};

// ─── Delete ───────────────────────────────────────────────────────────────────
const deleteTask = (task: Task) => {
    confirm.require({
        message: `Delete task "${task.title}"? This cannot be undone.`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancel',
        accept: () =>
            router.delete(route('project.tasks.destroy', { projectEncoded: props.projectId, taskEncoded: task.id }), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Task deleted', life: 2000 }),
                onError: () => toast.add({ severity: 'error', summary: 'Failed to delete task', life: 3000 }),
            }),
    });
};

// ─── Quick-add ────────────────────────────────────────────────────────────────
const startQuickAdd = async (statusId: string) => {
    if (!canTaskCreate.value) return;
    quickErrors.value = {};
    quickAddStatus.value = statusId;
};

const validateQuickForm = (form: any): boolean => {
    const errs: Record<string, string> = {};
    if (!form.title.trim()) errs.title = 'Title is required.';
    if (!form.type_id) errs.type_id = 'Type is required.';
    if (!form.priority_id) errs.priority_id = 'Priority is required.';
    if (!form.assign_users.length) errs.assign_users = 'At least one assignee is required.';

    const currentStatus = props.statuses.find((s) => s.id === quickAddStatus.value);
    if (requiresDueDateForStatus(currentStatus?.name) && !form.due_date) {
        errs.due_date = 'Due date is required for this status.';
    }

    if (form.start_date && form.due_date && form.start_date > form.due_date) {
        errs.start_date = 'Start date cannot be after due date.';
    }

    quickErrors.value = errs;
    return Object.keys(errs).length === 0;
};

const submitQuickAdd = async (form: any) => {
    if (!validateQuickForm(form) || !quickAddStatus.value) return;

    quickAddLoading.value = true;
    try {
        await axios.post(route('project.tasks.store', props.projectId), {
            project_id: props.projectId,
            title: form.title.trim(),
            status_id: quickAddStatus.value,
            type_id: form.type_id?.id,
            priority_id: form.priority_id?.id,
            assign_users: form.assign_users.map((u: User) => u.id),
            start_date: form.start_date ? moment(form.start_date).format('YYYY-MM-DD') : null,
            due_date: form.due_date ? moment(form.due_date).format('YYYY-MM-DD') : null,
        });
        toast.add({ severity: 'success', summary: 'Task created', life: 1800 });
        quickAddStatus.value = null;
        router.reload({ only: ['tasks'] });
    } catch (err: any) {
        const laravelErrors = err?.response?.data?.errors as Record<string, string[]> | undefined;
        if (laravelErrors) {
            const mapped: Record<string, string> = {};
            for (const [key, msgs] of Object.entries(laravelErrors)) {
                mapped[key] = msgs[0];
            }
            quickErrors.value = { ...quickErrors.value, ...mapped };
            toast.add({ severity: 'warn', summary: 'Please fix the errors below', life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Failed to create task', life: 3000 });
        }
    } finally {
        quickAddLoading.value = false;
    }
};

// ─── Column collapse ──────────────────────────────────────────────────────────
const toggleCollapse = (sid: string) => {
    const s = new Set(collapsedCols.value);
    s.has(sid) ? s.delete(sid) : s.add(sid);
    collapsedCols.value = s;
};

// ─── Context menu ─────────────────────────────────────────────────────────────
const openCardMenu = (e: MouseEvent, task: Task) => {
    e.preventDefault();
    e.stopPropagation();
    cardMenuTask.value = task;
    cardMenu.value.show(e);
};
</script>

<template>
    <div class="flex h-full select-none flex-col gap-3">
        <!-- ── Toolbar ─────────────────────────────────────────────────────── -->
        <TaskKanbanToolbar
            v-model:searchQuery="searchQuery"
            v-model:filterAssignee="filterAssignee"
            v-model:filterPriority="filterPriority"
            v-model:filterType="filterType"
            :allAssignees="allAssignees"
            :taskPriorities="taskPriorities"
            :taskTypes="taskTypes"
            :totalTasks="totalTasks"
            :doneTasks="doneTasks"
            :boardProgress="boardProgress"
        />

        <!-- ── Board ───────────────────────────────────────────────────────── -->
        <div class="flex flex-nowrap gap-3 overflow-x-auto pb-4" style="min-height: 500px; align-items: flex-start">
            <TaskKanbanColumn
                v-for="(_, statusId) in grouped"
                :key="statusId"
                :statusId="statusId"
                :statusName="getStatusName(statusId)"
                :tasksCount="filteredGrouped[statusId]?.length ?? 0"
                :meta="getMeta(statusId)"
                :collapsed="collapsedCols.has(statusId)"
                :canAct="canAct"
                @toggleCollapse="toggleCollapse"
                @startQuickAdd="startQuickAdd"
            >
                <TaskKanbanQuickAdd
                    v-if="quickAddStatus === statusId"
                    :statusId="statusId"
                    :taskTypes="taskTypes"
                    :taskPriorities="taskPriorities"
                    :userOptions="userOptions"
                    :currentUser="currentUser"
                    :isNeedDueDate="requiresDueDateForStatus(getStatusName(statusId))"
                    :loading="quickAddLoading"
                    :errors="quickErrors"
                    @cancel="quickAddStatus = null"
                    @submit="submitQuickAdd"
                    @openFull="
                        emit('add', null, statusId);
                        quickAddStatus = null;
                    "
                />

                <VueDraggable
                    class="flex flex-col gap-2 overflow-y-auto"
                    style="max-height: calc(100vh - 320px); min-height: 48px"
                    v-model="grouped[statusId]"
                    :animation="180"
                    ghostClass="kanban-ghost"
                    group="kanban"
                    @add="(e) => onGroupChange(e.data, statusId)"
                    @start="onDragStart"
                    @end="
                        () => {
                            draggingItem = false;
                            draggingTaskId = null;
                        }
                    "
                >
                    <TaskKanbanCard
                        v-for="task in filteredGrouped[statusId]"
                        :key="task.id"
                        :task="task"
                        :dragging="draggingTaskId === task.id"
                        :isOverdue="isOverdue(task)"
                        :subtaskCount="subtaskCount(task)"
                        :doneSubtaskCount="doneSubtaskCount(task)"
                        :canAct="canAct"
                        @detail="detailPanel = { visible: true, task: $event }"
                        @menu="openCardMenu"
                        @add="emit('add', $event)"
                        @edit="(t, pid) => emit('edit', t, pid)"
                        @delete="deleteTask"
                    />
                </VueDraggable>

                <template #empty>
                    <div
                        v-if="(filteredGrouped[statusId]?.length ?? 0) === 0 && quickAddStatus !== statusId"
                        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-surface-200 py-6 text-surface-300 dark:border-surface-700"
                    >
                        <i class="pi pi-inbox mb-1.5 text-xl" />
                        <p class="text-xs">
                            {{ !!searchQuery || filterAssignee.length || filterPriority.length || filterType.length ? 'No match' : 'Drop here' }}
                        </p>
                    </div>
                </template>

                <template #footer v-if="quickAddStatus === statusId">
                    <div />
                    <!-- Hide normal footer button if quick adding -->
                </template>
            </TaskKanbanColumn>
        </div>
    </div>

    <Menu ref="cardMenu" :model="cardMenuItems" popup />

    <!-- ── Due Date Dialog ──────────────────────────────────────────────────── -->
    <Dialog v-model:visible="inProgressDialog.visible" modal :closable="false" :draggable="false" class="w-full max-w-md">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="pi pi-calendar-clock text-blue-600 dark:text-blue-300"></i>
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-800 dark:text-white">Set Due Date</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Required to move task to this status</p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4 py-2">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-medium text-surface-800 dark:text-surface-100"> "{{ inProgressDialog.task?.title }}" </span>
                doesn't have a due date yet. Please set one before moving it to
                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ inProgressDialog.newStatusId ? getStatusName(inProgressDialog.newStatusId) : 'this status' }}</span>.
            </p>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    <i class="pi pi-calendar-times mr-1 text-red-500"></i>DUE DATE <span class="text-red-500">*</span>
                </label>
                <DatePicker
                    v-model="inProgressDialog.dueDate"
                    dateFormat="dd M yy"
                    class="w-full"
                    showIcon
                    placeholder="Select due date"
                    :minDate="new Date()"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="cancelInProgressDialog" />
                <Button
                    label="Confirm & Move"
                    icon="pi pi-check"
                    :disabled="!inProgressDialog.dueDate"
                    :loading="inProgressLoading"
                    @click="submitInProgressDialog"
                />
            </div>
        </template>
    </Dialog>

    <TaskKanbanDetailPanel
        v-model:visible="detailPanel.visible"
        :task="detailPanel.task"
        :epic-tasks="epicTasks"
        :canAct="canAct"
        :project-members="assignableUsers"
        @edit="(t, pid) => emit('edit', t, pid)"
        @add="emit('add', $event)"
        @delete="deleteTask"
    />
</template>

<style scoped>
.kanban-ghost {
    opacity: 0.3;
    background: #dbeafe;
    border: 1.5px dashed #93c5fd;
    border-radius: 8px;
}
</style>
