<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import { severityClasses } from '@/lib/severity';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref, watch } from 'vue';
import { DraggableEvent, VueDraggable } from 'vue-draggable-plus';
import { Task, TaskStatus } from '../type';

interface Props {
    tasks: Task[];
    statuses: TaskStatus[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    statusUpdate: [taskId: string, newStatusId: string];
}>();

const toast = useToast();

const grouped = ref<Record<string, Task[]>>({});
const draggingItem = ref(false);
const preDragSnapshot = ref<Record<string, Task[]> | null>(null);

const onDragStart = () => {
    draggingItem.value = true;
    const snapshot: Record<string, Task[]> = {};
    for (const [sid, tasks] of Object.entries(grouped.value)) {
        snapshot[sid] = [...tasks];
    }
    preDragSnapshot.value = snapshot;
};

const inProgressDialog = ref<{
    visible: boolean;
    task: Task | null;
    newStatusId: string | null;
    dueDate: Date | null;
    snapshot: Record<string, Task[]> | null;
}>({
    visible: false,
    task: null,
    newStatusId: null,
    dueDate: null,
    snapshot: null,
});
const inProgressLoading = ref(false);

const getGroupedTasks = () => {
    const groupedTasks: Record<string, Task[]> = {};
    for (const status of props.statuses) {
        groupedTasks[status.id] = [];
    }
    props.tasks.forEach((task) => {
        const status = props.statuses.find((s) => s.id === task.status?.id);
        if (!status) return;
        if (!groupedTasks[status.id]) groupedTasks[status.id] = [];
        groupedTasks[status.id].push(task);
    });
    return groupedTasks;
};

const getStatusMeta = (statusId: string) => {
    const status = props.statuses.find((s) => s.id === statusId);
    return severityClasses[status?.severity ?? 'default'] ?? severityClasses['default'];
};

const getStatusName = (statusId: string) => {
    return props.statuses.find((s) => s.id === statusId)?.name ?? 'Unknown';
};

const requiresDueDateForStatus = (statusName?: string) => !!statusName && !['To Do', 'Blocked'].includes(statusName);

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
        const response = await axios.post(route('task.status.update', task.id), {
            _method: 'PUT',
            status_id: newStatusId,
            ...(dueDate ? { due_date: dueDate } : {}),
        });
        if (response.status === 200) {
            emit('statusUpdate', task.id, newStatusId);
        }
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Gagal',
            detail: getErrorMessage(error, 'Gagal memperbarui status task.'),
            life: 3000,
        });
        grouped.value = preDragSnapshot.value ?? getGroupedTasks();
    }
};

const onGroupChange = async (task: Task, newStatusId: string) => {
    const targetStatus = props.statuses.find((s) => s.id === newStatusId);
    const dueDateMissing = !task.due_date;
    if (requiresDueDateForStatus(targetStatus?.name) && dueDateMissing) {
        inProgressDialog.value = { visible: true, task, newStatusId, dueDate: null, snapshot: preDragSnapshot.value };
        return;
    }
    await doStatusUpdate(task, newStatusId, null);
};

const submitInProgressDialog = async () => {
    if (!inProgressDialog.value.dueDate) {
        toast.add({
            severity: 'warn',
            summary: 'Due Date Wajib Diisi',
            detail: 'Pilih due date sebelum memindahkan task ke status ini.',
            life: 3000,
        });
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

const dueDateClasses = (task: Task) => {
    if (!task.due_date) return 'text-surface-400 dark:text-surface-500';
    if (task.is_overdue) return 'text-rose-500 dark:text-rose-400';
    const daysLeft = moment(task.due_date).diff(moment(), 'days');
    if (daysLeft <= 3) return 'text-amber-500 dark:text-amber-400';
    return 'text-surface-500 dark:text-surface-400';
};

const subtaskCounts = (task: Task) => {
    const subs = task.sub_task ?? [];
    const done = subs.filter((s) => s.completed_at !== null).length;
    return { total: subs.length, done };
};

onMounted(() => {
    grouped.value = getGroupedTasks();
});

watch(
    () => props.tasks,
    () => {
        if (!draggingItem.value) {
            grouped.value = getGroupedTasks();
        }
    },
    { deep: true },
);
</script>

<template>
    <div class="overflow-x-auto py-3">
        <div class="flex flex-nowrap gap-3 pb-1">
            <!-- Column -->
            <div
                v-for="(group, statusId) in grouped"
                :key="statusId"
                class="flex w-[300px] shrink-0 flex-col rounded-xl border transition-colors duration-150"
                :class="[getStatusMeta(statusId).colBg, getStatusMeta(statusId).colBorder]"
            >
                <!-- Column header -->
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <span class="h-2 w-2 shrink-0 rounded-full" :class="getStatusMeta(statusId).dot" />
                    <span class="flex-1 truncate text-[11px] font-semibold uppercase tracking-wider" :class="getStatusMeta(statusId).headerText">
                        {{ getStatusName(statusId) }}
                    </span>
                    <span
                        class="min-w-[20px] rounded-full bg-surface-200/80 px-1.5 py-0.5 text-center text-[11px] font-bold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                    >
                        {{ group.length }}
                    </span>
                </div>

                <!-- Drop zone + cards (bounded scroll: kolom tidak memanjang tanpa batas) -->
                <VueDraggable
                    v-model="grouped[statusId]"
                    class="kanban-col-scroll flex max-h-[70vh] min-h-[80px] flex-col gap-2 overflow-y-auto px-2 pb-2"
                    :animation="150"
                    ghostClass="opacity-40"
                    group="kanban-personal"
                    :scroll="true"
                    :scrollSensitivity="80"
                    :scrollSpeed="14"
                    :bubbleScroll="true"
                    @add="(e: DraggableEvent<Task>) => onGroupChange(e.data, statusId)"
                    @start="onDragStart"
                    @end="draggingItem = false"
                >
                    <!-- Empty state -->
                    <div
                        v-if="group.length === 0"
                        class="flex flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed py-7 text-center transition-colors duration-150"
                        :class="[getStatusMeta(statusId).colBorder]"
                    >
                        <i class="pi pi-inbox text-lg text-surface-300 dark:text-surface-600" />
                        <span class="text-[11px] text-surface-400 dark:text-surface-500">Tidak ada task</span>
                    </div>

                    <!-- Card -->
                    <div
                        v-for="item in group"
                        :key="item.id"
                        class="group relative cursor-grab rounded-lg border bg-white shadow-sm transition-all duration-150 active:cursor-grabbing dark:bg-surface-800"
                        :class="[
                            item.is_overdue ? 'border-rose-200 dark:border-rose-800/50' : 'border-surface-200 dark:border-surface-700',
                            draggingItem ? '' : 'hover:border-blue-300 hover:shadow-md dark:hover:border-blue-700',
                        ]"
                        @click="router.get(route('task.show', item.id))"
                    >
                        <div class="p-2.5">
                            <!-- Row 1: type + priority + overdue badge -->
                            <div class="mb-1.5 flex flex-wrap items-center gap-1">
                                <span
                                    v-if="item.type"
                                    class="inline-flex items-center rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                                >
                                    {{ item.type.name }}
                                </span>
                                <span
                                    v-if="item.priority"
                                    class="inline-flex items-center rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                                >
                                    {{ item.priority.name }}
                                </span>
                                <span
                                    v-if="item.is_overdue"
                                    class="ml-auto inline-flex items-center gap-0.5 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-500 dark:bg-rose-950/40 dark:text-rose-400"
                                >
                                    <i class="pi pi-clock text-[9px]" /> Overdue
                                </span>
                            </div>

                            <!-- Title -->
                            <p class="mb-2 line-clamp-2 text-[13px] font-medium leading-snug text-surface-800 dark:text-surface-100">
                                {{ item.title }}
                            </p>

                            <!-- Project link -->
                            <Link
                                v-if="item.project"
                                :href="route('project.show', item.project.id)"
                                class="mb-2 inline-flex items-center gap-1 text-[11px] text-surface-400 transition-colors hover:text-surface-700 dark:text-surface-500 dark:hover:text-surface-300"
                                @click.stop
                            >
                                <i class="pi pi-folder text-[9px]" />
                                <span class="truncate">{{ item.project.title }}</span>
                            </Link>

                            <!-- Due date -->
                            <div v-if="item.due_date" class="mb-2 flex items-center gap-1 text-[11px]" :class="dueDateClasses(item)">
                                <i class="pi pi-calendar text-[10px]" />
                                <span>{{ moment(item.due_date).format('DD MMM') }}</span>
                                <span class="opacity-70">· {{ moment(item.due_date).fromNow() }}</span>
                            </div>

                            <!-- Subtask progress bar -->
                            <div v-if="subtaskCounts(item).total > 0" class="mb-2">
                                <div class="mb-1 flex items-center justify-between text-[10px] text-surface-400 dark:text-surface-500">
                                    <span class="flex items-center gap-0.5"> <i class="pi pi-sitemap text-[9px]" /> Subtask </span>
                                    <span>{{ subtaskCounts(item).done }}/{{ subtaskCounts(item).total }}</span>
                                </div>
                                <div class="h-1 w-full overflow-hidden rounded-full bg-surface-100 dark:bg-surface-700">
                                    <div
                                        class="h-full rounded-full bg-emerald-400 transition-all duration-300 dark:bg-emerald-500"
                                        :style="`width:${Math.round((subtaskCounts(item).done / subtaskCounts(item).total) * 100)}%`"
                                    />
                                </div>
                            </div>

                            <!-- Bottom: task id + avatars -->
                            <div class="flex items-center justify-between gap-1">
                                <span v-if="item.sequence_number" class="text-[10px] text-surface-300 dark:text-surface-600">
                                    #{{ item.sequence_number }}
                                </span>
                                <div v-else class="flex-1" />
                                <div class="flex -space-x-1.5">
                                    <UserAvatar
                                        v-for="u in (item.users ?? []).slice(0, 3)"
                                        :key="u.id"
                                        :user="u"
                                        size="!h-5 !w-5 border border-white dark:border-surface-800"
                                        fontSize=".6rem"
                                    />
                                    <span
                                        v-if="(item.users ?? []).length > 3"
                                        class="flex h-5 w-5 items-center justify-center rounded-full border border-white bg-surface-200 text-[9px] font-bold text-surface-600 dark:border-surface-800 dark:bg-surface-700"
                                    >
                                        +{{ (item.users ?? []).length - 3 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Hover action strip -->
                        <div
                            class="hidden items-center justify-end gap-0.5 rounded-b-lg border-t border-surface-100 bg-surface-50/80 px-2 py-1 group-hover:flex dark:border-surface-700 dark:bg-surface-900/40"
                        >
                            <Link :href="route('task.show', item.id)" @click.stop>
                                <button
                                    class="rounded p-1 text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700 dark:hover:text-surface-200"
                                    title="Buka task"
                                >
                                    <i class="pi pi-external-link text-[11px]" />
                                </button>
                            </Link>
                        </div>
                    </div>
                </VueDraggable>
            </div>
        </div>
    </div>

    <!-- ── Due Date Dialog ───────────────────────────────────────────────── -->
    <Dialog v-model:visible="inProgressDialog.visible" modal :closable="false" :draggable="false" class="w-full max-w-md">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                    <i class="pi pi-calendar-clock text-blue-600 dark:text-blue-300" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-surface-800 dark:text-surface-100">Set Due Date</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Wajib diisi untuk pindah ke status ini</p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4 py-2">
            <p class="text-sm text-surface-600 dark:text-surface-300">
                <span class="font-medium text-surface-800 dark:text-surface-100">"{{ inProgressDialog.task?.title }}"</span>
                belum memiliki due date. Tetapkan sebelum memindahkan ke status
                <span class="font-semibold text-blue-600 dark:text-blue-400">{{
                    inProgressDialog.newStatusId ? getStatusName(inProgressDialog.newStatusId) : 'ini'
                }}</span
                >.
            </p>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-surface-500 dark:text-surface-400">
                    <i class="pi pi-calendar-times mr-1 text-rose-500" />DUE DATE
                    <span class="text-rose-500">*</span>
                </label>
                <DatePicker
                    v-model="inProgressDialog.dueDate"
                    dateFormat="dd M yy"
                    class="w-full"
                    showIcon
                    placeholder="Pilih due date"
                    :minDate="new Date()"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 pt-2">
                <Button label="Batal" severity="secondary" text @click="cancelInProgressDialog" />
                <Button
                    label="Konfirmasi & Pindahkan"
                    icon="pi pi-check"
                    :disabled="!inProgressDialog.dueDate"
                    :loading="inProgressLoading"
                    @click="submitInProgressDialog"
                />
            </div>
        </template>
    </Dialog>
</template>

<style>
/* Scrollbar tipis untuk area scroll tiap kolom — adaptif light & dark */
.kanban-col-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.4) transparent;
}
.kanban-col-scroll::-webkit-scrollbar {
    width: 6px;
}
.kanban-col-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.kanban-col-scroll::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background-color: rgba(148, 163, 184, 0.4);
}
.kanban-col-scroll::-webkit-scrollbar-thumb:hover {
    background-color: rgba(148, 163, 184, 0.65);
}
</style>
