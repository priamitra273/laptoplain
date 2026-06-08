<script setup lang="ts">
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

// ─── In Progress Dialog ───────────────────────────────────────────────────────
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

const cardClasses: Record<string, string> = {
    primary: 'bg-primary-100/50',
    secondary: 'bg-gray-100/50',
    success: 'bg-green-100/50',
    info: 'bg-blue-100/50',
    warn: 'bg-orange-100/50',
    danger: 'bg-red-100/50',
    contrast: 'bg-gray-100/50',
    default: 'bg-gray-100/50',
};

const getGroupedTasks = () => {
    const groupedTasks: Record<string, Task[]> = {};

    for (const status of props.statuses) {
        groupedTasks[status.id] = [];
    }

    props.tasks.forEach((task) => {
        const status = props.statuses.find((s) => s.id === task.status?.id);

        if (!status) return;

        const group = status.id;

        if (!groupedTasks[group]) {
            groupedTasks[group] = [];
        }

        groupedTasks[group].push(task);
    });

    return groupedTasks;
};

const getSeverityByStatus = (id: string) => {
    const status = props.statuses.find((s) => s.id === id);

    if (status) return status.severity;

    return 'primary';
};

const getStatusName = (id: string) => {
    const status = props.statuses.find((s) => s.id === id);

    if (status) return status.name;

    return 'Unknown';
};

/** Perform the actual API call, optionally with a due_date */
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
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memperbarui status task.', life: 3000 });
        grouped.value = getGroupedTasks();
    }
};

const onGroupChange = async (task: Task, newStatusId: string) => {
    const targetStatus = props.statuses.find((s) => s.id === newStatusId);
    const isInProgress = targetStatus?.name === 'In Progress';
    const dueDateMissing = !task.due_date;

    if (isInProgress && dueDateMissing) {
        // // Revert visual ke posisi sebelum drag menggunakan snapshot pre-drag
        // if (preDragSnapshot.value) {
        //     grouped.value = preDragSnapshot.value;
        // }

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

const submitInProgressDialog = async () => {
    if (!inProgressDialog.value.dueDate) {
        toast.add({
            severity: 'warn',
            summary: 'Due Date Wajib Diisi',
            detail: 'Pilih due date sebelum memindahkan task ke In Progress.',
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
    // Restore snapshot agar card tidak hilang
    if (inProgressDialog.value.snapshot) {
        grouped.value = inProgressDialog.value.snapshot;
    }
    inProgressDialog.value = {
        visible: false,
        task: null,
        newStatusId: null,
        dueDate: null,
        snapshot: null,
    };
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
        <div class="flex flex-nowrap gap-4">
            <div v-for="(group, index) in grouped" :key="index" class="h-fit min-h-48 rounded-lg bg-gray-100/50 p-4">
                <Tag icon="pi pi-circle-fill" :value="getStatusName(index)" :severity="getSeverityByStatus(index)" class="mb-4" />

                <VueDraggable
                    class="flex h-full w-[300px] flex-col gap-2 overflow-hidden"
                    v-model="grouped[index]"
                    :animation="150"
                    ghostClass="ghost"
                    group="people"
                    @add="(e: DraggableEvent<Task>) => onGroupChange(e.data, index)"
                    @start="onDragStart"
                    @end="draggingItem = false"
                >
                    <Card
                        v-for="item in group"
                        :key="item.id"
                        class="!rounded-lg border !shadow-none"
                        :class="[draggingItem ? 'cursor-grabbing' : 'cursor-pointer']"
                        @click="router.get(route('task.show', item.id))"
                    >
                        <template #subtitle>
                            <Link :href="route('project.show', item.project?.id)" @click.stop>
                                <span class="hover:underline">
                                    {{ item.project?.title }}
                                </span>
                            </Link>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="break-all">
                                    <Link :href="route('task.show', item.id)" @click.stop>
                                        <span class="hover:underline">
                                            {{ item.title }}
                                        </span>
                                    </Link>
                                </div>
                                <div class="flex justify-between gap-1">
                                    <Tag
                                        icon="pi pi-flag"
                                        :value="moment(item.due_date).fromNow()"
                                        class="!border !bg-transparent text-xs"
                                        style="color: var(--text-color)"
                                    />

                                    <div class="flex gap-1">
                                        <Tag :value="item.type?.name" :severity="item.type?.severity" class="text-xs" />
                                        <Tag :value="item.priority?.name" :severity="item.priority?.severity" class="text-xs" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </VueDraggable>
            </div>
        </div>
    </div>

    <!-- ── In Progress: Due Date Dialog ─────────────────────────────────────── -->
    <Dialog v-model:visible="inProgressDialog.visible" modal :closable="false" :draggable="false" class="w-full max-w-md">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="pi pi-calendar-clock text-blue-600 dark:text-blue-300"></i>
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-800 dark:text-white">Set Due Date</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Required to move task to In Progress</p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4 py-2">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <span class="font-medium text-surface-800 dark:text-surface-100"> "{{ inProgressDialog.task?.title }}" </span>
                doesn't have a due date yet. Please set one before moving it to
                <span class="font-semibold text-blue-600 dark:text-blue-400">In Progress</span>.
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
                    placeholder="Pilih due date"
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
</template>
