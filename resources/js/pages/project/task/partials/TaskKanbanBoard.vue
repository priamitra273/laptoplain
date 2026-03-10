<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import MultiSelect from 'primevue/multiselect';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { DraggableEvent, VueDraggable } from 'vue-draggable-plus';
import type { Task, TaskStatus, TaskPriority, TaskType } from '../..';

interface AssignableUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

interface Props {
    projectId: string;
    tasks: Task[];
    statuses: TaskStatus[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    isMember: boolean;
    hasPermission: boolean;
    assignableUsers?: AssignableUser[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    statusUpdate: [taskId: string, newStatusId: string];
    add: [parentId: string | null, statusId?: string];
    edit: [task: Task, parentId: string | null];
}>();

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

// ─── State ────────────────────────────────────────────────────────────────────
const grouped = ref<Record<string, Task[]>>({});
const draggingItem = ref(false);
const draggingTaskId = ref<string | null>(null);
const searchQuery = ref('');
const filterAssignee = ref<string | null>(null);
const filterPriority = ref<string | null>(null);
const filterType = ref<string | null>(null);
const collapsedCols = ref<Set<string>>(new Set());

// Quick-add per column
const quickAddStatus = ref<string | null>(null);
const quickAddLoading = ref(false);
const quickAddTitleRef = ref<HTMLTextAreaElement | null>(null);

// Quick-add form model
const quickForm = ref({
    title: '',
    type_id: null as TaskType | null,
    priority_id: null as TaskPriority | null,
    assign_users: [] as AssignableUser[],
    due_date: null as Date | null,
});
const quickErrors = ref<Record<string, string>>({});

// Detail slide-over
const detailPanel = ref<{ visible: boolean; task: Task | null }>({ visible: false, task: null });

// Context menu
const cardMenu = ref();
const cardMenuTask = ref<Task | null>(null);
const cardMenuItems = computed(() => [
    { label: 'View Detail', icon: 'pi pi-eye', command: () => openDetail(cardMenuTask.value!) },
    {
        label: 'Edit Task',
        icon: 'pi pi-pencil',
        command: () => emit('edit', cardMenuTask.value!, cardMenuTask.value!.parent_id),
        disabled: !canAct.value,
    },
    { label: 'Add Subtask', icon: 'pi pi-sitemap', command: () => emit('add', cardMenuTask.value!.id), disabled: !canAct.value },
    { separator: true },
    { label: 'Delete Task', icon: 'pi pi-trash', command: () => deleteTask(cardMenuTask.value!), disabled: !canAct.value },
]);

// ─── Computed ─────────────────────────────────────────────────────────────────
const canAct = computed(() => props.isMember || props.hasPermission);

const currentUser = computed(() => page.props.auth?.user as AssignableUser | undefined);

const allAssignees = computed(() => {
    const map = new Map<string, AssignableUser>();
    props.tasks.forEach((t) => (t.users || []).forEach((u) => map.set(u.id, u)));
    return Array.from(map.values());
});

const userOptions = computed<AssignableUser[]>(() => {
    if (props.assignableUsers?.length) return props.assignableUsers;
    // fallback: collect from tasks
    return allAssignees.value;
});

const filteredGrouped = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    const result: Record<string, Task[]> = {};
    for (const [sid, tasks] of Object.entries(grouped.value)) {
        result[sid] = tasks.filter((t) => {
            const matchQ = !q || t.title.toLowerCase().includes(q);
            const matchA = !filterAssignee.value || (t.users || []).some((u) => u.id === filterAssignee.value);
            const matchP = !filterPriority.value || t.priority?.id === filterPriority.value;
            const matchT = !filterType.value || t.type?.id === filterType.value;
            return matchQ && matchA && matchP && matchT;
        });
    }
    return result;
});

const hasActiveFilter = computed(() => !!searchQuery.value || !!filterAssignee.value || !!filterPriority.value || !!filterType.value);

const totalTasks = computed(() => props.tasks.length);
const doneTasks = computed(() => {
    const doneStatus = props.statuses.find((s) => s.name?.toLowerCase().includes('done') || s.name?.toLowerCase().includes('complete'));
    return doneStatus ? (grouped.value[doneStatus.id] || []).length : 0;
});
const boardProgress = computed(() => (totalTasks.value ? Math.round((doneTasks.value / totalTasks.value) * 100) : 0));

// ─── Severity meta ────────────────────────────────────────────────────────────
const severityMeta: Record<string, { colBg: string; colBorder: string; headerText: string; dot: string }> = {
    primary: {
        colBg: 'bg-slate-50 dark:bg-slate-900/40',
        colBorder: 'border-slate-200 dark:border-slate-700/60',
        headerText: 'text-violet-600 dark:text-violet-400',
        dot: 'bg-violet-500',
    },
    secondary: {
        colBg: 'bg-slate-50 dark:bg-slate-900/40',
        colBorder: 'border-slate-200 dark:border-slate-700/60',
        headerText: 'text-slate-600 dark:text-slate-400',
        dot: 'bg-slate-400',
    },
    success: {
        colBg: 'bg-emerald-50/40 dark:bg-emerald-950/20',
        colBorder: 'border-emerald-200 dark:border-emerald-800/50',
        headerText: 'text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    },
    info: {
        colBg: 'bg-sky-50/40 dark:bg-sky-950/20',
        colBorder: 'border-sky-200 dark:border-sky-800/50',
        headerText: 'text-sky-600 dark:text-sky-400',
        dot: 'bg-sky-500',
    },
    warn: {
        colBg: 'bg-amber-50/40 dark:bg-amber-950/20',
        colBorder: 'border-amber-200 dark:border-amber-800/50',
        headerText: 'text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    warning: {
        colBg: 'bg-amber-50/40 dark:bg-amber-950/20',
        colBorder: 'border-amber-200 dark:border-amber-800/50',
        headerText: 'text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    danger: {
        colBg: 'bg-rose-50/40 dark:bg-rose-950/20',
        colBorder: 'border-rose-200 dark:border-rose-800/50',
        headerText: 'text-rose-600 dark:text-rose-400',
        dot: 'bg-rose-500',
    },
    contrast: {
        colBg: 'bg-gray-50 dark:bg-gray-900/40',
        colBorder: 'border-gray-200 dark:border-gray-700/60',
        headerText: 'text-gray-700 dark:text-gray-300',
        dot: 'bg-gray-600',
    },
    default: {
        colBg: 'bg-gray-50 dark:bg-gray-900/40',
        colBorder: 'border-gray-200 dark:border-gray-700/60',
        headerText: 'text-gray-600 dark:text-gray-400',
        dot: 'bg-gray-400',
    },
};
const getMeta = (sid: string) => severityMeta[props.statuses.find((s) => s.id === sid)?.severity || 'default'] || severityMeta.default;
const getStatusName = (id: string) => props.statuses.find((s) => s.id === id)?.name || 'Unknown';

// ─── Card helpers ─────────────────────────────────────────────────────────────
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const avatarColor = (id: string) => {
    const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f43f5e', '#3b82f6'];
    let h = 0;
    for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) % colors.length;
    return colors[h];
};

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

const priorityIconMap: Record<string, string> = {
    highest: 'pi-angle-double-up',
    high: 'pi-angle-up',
    medium: 'pi-minus',
    low: 'pi-angle-down',
    lowest: 'pi-angle-double-down',
};
const getPriorityIcon = (name?: string) => priorityIconMap[name?.toLowerCase() || ''] || 'pi-minus';
const getPriorityColor = (sev?: string) =>
    sev === 'danger' ? '#ef4444' : sev === 'warn' || sev === 'warning' ? '#f59e0b' : sev === 'success' ? '#10b981' : '#94a3b8';

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
    try {
        await axios.post(route('task.status.update', task.id), { _method: 'PUT', status_id: newStatusId });
        emit('statusUpdate', task.id, newStatusId);
        toast.add({ severity: 'success', summary: 'Status updated', life: 1800 });
    } catch {
        toast.add({ severity: 'error', summary: 'Failed to update status', life: 3000 });
        grouped.value = buildGrouped();
    }
};

// ─── Delete ───────────────────────────────────────────────────────────────────
const deleteTask = (task: Task) => {
    confirm.require({
        message: `Delete "${task.title}"? This cannot be undone.`,
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
const resetQuickForm = () => {
    quickForm.value = {
        title: '',
        type_id: null,
        priority_id: null,
        assign_users: currentUser.value ? [currentUser.value as AssignableUser] : [],
        due_date: null,
    };
    quickErrors.value = {};
};

const startQuickAdd = async (statusId: string) => {
    if (!canAct.value) return;
    resetQuickForm();
    quickAddStatus.value = statusId;
    await nextTick();
    quickAddTitleRef.value?.focus();
};

const cancelQuickAdd = () => {
    quickAddStatus.value = null;
    resetQuickForm();
};

const validateQuickForm = (): boolean => {
    const errs: Record<string, string> = {};
    if (!quickForm.value.title.trim()) errs.title = 'Title is required.';
    if (!quickForm.value.type_id) errs.type_id = 'Type is required.';
    if (!quickForm.value.priority_id) errs.priority_id = 'Priority is required.';
    if (!quickForm.value.assign_users.length) errs.assign_users = 'At least one assignee is required.';
    quickErrors.value = errs;
    return Object.keys(errs).length === 0;
};

const submitQuickAdd = async () => {
    if (!validateQuickForm() || !quickAddStatus.value) return;

    quickAddLoading.value = true;
    try {
        await axios.post(route('project.tasks.store', props.projectId), {
            title: quickForm.value.title.trim(),
            status_id: quickAddStatus.value,
            type_id: quickForm.value.type_id?.id,
            priority_id: quickForm.value.priority_id?.id,
            assign_users: quickForm.value.assign_users.map((u) => u.id),
            due_date: quickForm.value.due_date ? moment(quickForm.value.due_date).format('YYYY-MM-DD') : null,
        });
        toast.add({ severity: 'success', summary: 'Task created', life: 1800 });
        cancelQuickAdd();
        router.reload({ only: ['tasks'] });
    } catch (err: any) {
        // Surface Laravel validation errors if present
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

// ─── Detail panel ─────────────────────────────────────────────────────────────
const openDetail = (task: Task) => {
    detailPanel.value = { visible: true, task };
};

// ─── Context menu ─────────────────────────────────────────────────────────────
const openCardMenu = (e: MouseEvent, task: Task) => {
    e.preventDefault();
    e.stopPropagation();
    cardMenuTask.value = task;
    cardMenu.value.show(e);
};

// ─── Filters ─────────────────────────────────────────────────────────────────
const clearFilters = () => {
    searchQuery.value = '';
    filterAssignee.value = null;
    filterPriority.value = null;
    filterType.value = null;
};
</script>

<template>
    <div class="flex h-full select-none flex-col gap-3">
        <!-- ── Toolbar ─────────────────────────────────────────────────────── -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i class="pi pi-search pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-surface-400" />
                <InputText v-model="searchQuery" placeholder="Search tasks…" class="!pl-7 !text-sm" style="height: 32px; width: 200px" />
            </div>

            <!-- Assignee chips -->
            <div class="flex items-center gap-1">
                <button
                    v-for="u in allAssignees.slice(0, 5)"
                    :key="u.id"
                    @click="filterAssignee = filterAssignee === u.id ? null : u.id"
                    class="rounded-full transition-all"
                    :title="u.name"
                    :class="filterAssignee === u.id ? 'ring-2 ring-blue-500 ring-offset-1' : 'opacity-70 hover:opacity-100'"
                >
                    <Avatar
                        :image="u.avatar_url && u.avatar_url !== '/images/default-avatar.png' ? u.avatar_url : undefined"
                        :label="!u.avatar_url || u.avatar_url === '/images/default-avatar.png' ? getInitials(u.name) : undefined"
                        shape="circle"
                        size="small"
                        :style="`background:${avatarColor(u.id)};color:white;font-size:.65rem;font-weight:600`"
                        class="!h-7 !w-7"
                    />
                </button>
            </div>

            <!-- Priority pills -->
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="p in taskPriorities"
                    :key="p.id"
                    @click="filterPriority = filterPriority === p.id ? null : p.id"
                    class="flex h-7 items-center gap-1 rounded-full border px-2 text-xs transition-all"
                    :class="
                        filterPriority === p.id
                            ? 'border-blue-400 bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300'
                            : 'border-surface-200 bg-white text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-400'
                    "
                >
                    <i :class="`pi ${getPriorityIcon(p.name)} text-xs`" /> {{ p.name }}
                </button>
            </div>

            <!-- Type pills -->
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="tp in taskTypes"
                    :key="tp.id"
                    @click="filterType = filterType === tp.id ? null : tp.id"
                    class="h-7 rounded-full border px-2 text-xs transition-all"
                    :class="
                        filterType === tp.id
                            ? 'border-blue-400 bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300'
                            : 'border-surface-200 bg-white text-surface-600 hover:border-surface-300 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-400'
                    "
                >
                    {{ tp.name }}
                </button>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <button
                    v-if="hasActiveFilter"
                    @click="clearFilters"
                    class="flex h-7 items-center gap-1 rounded-full border border-rose-300 bg-rose-50 px-2 text-xs text-rose-500 hover:bg-rose-100 dark:border-rose-700 dark:bg-rose-950/40"
                >
                    <i class="pi pi-filter-slash text-xs" /> Clear
                </button>
                <div class="flex items-center gap-2 text-xs text-surface-500 dark:text-surface-400">
                    <span>{{ doneTasks }}/{{ totalTasks }}</span>
                    <ProgressBar :value="boardProgress" style="width: 80px; height: 6px" :showValue="false" />
                    <span>{{ boardProgress }}%</span>
                </div>
            </div>
        </div>

        <!-- ── Board ───────────────────────────────────────────────────────── -->
        <div class="flex flex-nowrap gap-3 overflow-x-auto pb-4" style="min-height: 500px; align-items: flex-start">
            <div
                v-for="(_, statusId) in grouped"
                :key="statusId"
                class="flex shrink-0 flex-col rounded-xl border transition-all duration-200"
                :class="[getMeta(statusId).colBg, getMeta(statusId).colBorder, collapsedCols.has(statusId) ? 'w-12' : 'w-[280px]']"
            >
                <!-- Column header -->
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <template v-if="!collapsedCols.has(statusId)">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="getMeta(statusId).dot" />
                        <span class="flex-1 truncate text-xs font-semibold uppercase tracking-wider" :class="getMeta(statusId).headerText">
                            {{ getStatusName(statusId) }}
                        </span>
                        <span
                            class="min-w-[20px] rounded-full bg-surface-200/80 px-1.5 py-0.5 text-center text-xs font-bold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                        >
                            {{ filteredGrouped[statusId]?.length ?? 0 }}
                        </span>
                        <button
                            v-if="canAct"
                            @click="startQuickAdd(statusId)"
                            class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                            title="Quick add"
                        >
                            <i class="pi pi-plus text-xs" />
                        </button>
                        <button
                            @click="toggleCollapse(statusId)"
                            class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                            title="Collapse"
                        >
                            <i class="pi pi-chevron-left text-xs" />
                        </button>
                    </template>
                    <template v-else>
                        <div class="flex w-full flex-col items-center gap-2 py-1">
                            <button
                                @click="toggleCollapse(statusId)"
                                class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200"
                                title="Expand"
                            >
                                <i class="pi pi-chevron-right text-xs" />
                            </button>
                            <div
                                class="whitespace-nowrap text-xs font-semibold uppercase tracking-widest"
                                :class="getMeta(statusId).headerText"
                                style="writing-mode: vertical-rl; transform: rotate(180deg)"
                            >
                                {{ getStatusName(statusId) }}
                            </div>
                            <span
                                class="rounded-full bg-surface-200/80 px-1 py-0.5 text-center text-xs font-bold text-surface-600 dark:bg-surface-700"
                            >
                                {{ filteredGrouped[statusId]?.length ?? 0 }}
                            </span>
                        </div>
                    </template>
                </div>

                <!-- Cards + quick-add -->
                <div v-show="!collapsedCols.has(statusId)" class="flex flex-col px-2 pb-2">
                    <!-- ── Quick-add inline form ────────────────────────── -->
                    <div
                        v-if="quickAddStatus === statusId"
                        class="mb-2 rounded-lg border border-blue-300 bg-white shadow-md dark:border-blue-700 dark:bg-surface-800"
                    >
                        <div class="flex flex-col gap-2 p-2.5">
                            <!-- Title -->
                            <div>
                                <Textarea
                                    ref="quickAddTitleRef"
                                    v-model="quickForm.title"
                                    placeholder="Task title…"
                                    rows="2"
                                    class="w-full !resize-none !text-sm"
                                    :class="quickErrors.title ? '!border-rose-400' : ''"
                                    @keydown.escape="cancelQuickAdd"
                                    autoResize
                                />
                                <p v-if="quickErrors.title" class="mt-0.5 text-[10px] text-rose-500">{{ quickErrors.title }}</p>
                            </div>

                            <!-- Type -->
                            <div>
                                <Select
                                    v-model="quickForm.type_id"
                                    :options="taskTypes"
                                    optionLabel="name"
                                    placeholder="Type *"
                                    class="w-full !text-xs"
                                    :class="quickErrors.type_id ? '!border-rose-400' : ''"
                                >
                                    <template #value="{ value }">
                                        <Tag v-if="value" :value="value.name" :severity="value.severity" class="!text-xs" />
                                        <span v-else class="text-xs text-surface-400">Type *</span>
                                    </template>
                                    <template #option="{ option }">
                                        <Tag :value="option.name" :severity="option.severity" class="!text-xs" />
                                    </template>
                                </Select>
                                <p v-if="quickErrors.type_id" class="mt-0.5 text-[10px] text-rose-500">{{ quickErrors.type_id }}</p>
                            </div>

                            <!-- Priority -->
                            <div>
                                <Select
                                    v-model="quickForm.priority_id"
                                    :options="taskPriorities"
                                    optionLabel="name"
                                    placeholder="Priority *"
                                    class="w-full !text-xs"
                                    :class="quickErrors.priority_id ? '!border-rose-400' : ''"
                                >
                                    <template #value="{ value }">
                                        <div v-if="value" class="flex items-center gap-1.5">
                                            <i
                                                :class="`pi ${getPriorityIcon(value.name)} text-xs`"
                                                :style="`color:${getPriorityColor(value.severity)}`"
                                            />
                                            <Tag :value="value.name" :severity="value.severity" class="!text-xs" />
                                        </div>
                                        <span v-else class="text-xs text-surface-400">Priority *</span>
                                    </template>
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-1.5">
                                            <i
                                                :class="`pi ${getPriorityIcon(option.name)} text-xs`"
                                                :style="`color:${getPriorityColor(option.severity)}`"
                                            />
                                            <Tag :value="option.name" :severity="option.severity" class="!text-xs" />
                                        </div>
                                    </template>
                                </Select>
                                <p v-if="quickErrors.priority_id" class="mt-0.5 text-[10px] text-rose-500">{{ quickErrors.priority_id }}</p>
                            </div>

                            <!-- Assignees -->
                            <div>
                                <MultiSelect
                                    v-model="quickForm.assign_users"
                                    :options="userOptions"
                                    optionLabel="name"
                                    placeholder="Assign to *"
                                    class="w-full !text-xs"
                                    :class="quickErrors.assign_users ? '!border-rose-400' : ''"
                                    :maxSelectedLabels="2"
                                    display="chip"
                                >
                                    <template #option="{ option }">
                                        <div class="flex items-center gap-2">
                                            <Avatar
                                                :image="
                                                    option.avatar_url && option.avatar_url !== '/images/default-avatar.png'
                                                        ? option.avatar_url
                                                        : undefined
                                                "
                                                :label="
                                                    !option.avatar_url || option.avatar_url === '/images/default-avatar.png'
                                                        ? getInitials(option.name)
                                                        : undefined
                                                "
                                                shape="circle"
                                                :style="`background:${avatarColor(option.id)};color:white;font-size:.6rem;font-weight:600`"
                                                class="!h-5 !w-5"
                                            />
                                            <span class="text-xs">{{ option.name }}</span>
                                        </div>
                                    </template>
                                </MultiSelect>
                                <p v-if="quickErrors.assign_users" class="mt-0.5 text-[10px] text-rose-500">{{ quickErrors.assign_users }}</p>
                            </div>

                            <!-- Due date (optional unless status requires) -->
                            <div>
                                <DatePicker
                                    v-model="quickForm.due_date"
                                    placeholder="Due date (optional)"
                                    dateFormat="dd M yy"
                                    class="w-full !text-xs"
                                    :class="quickErrors.due_date ? '!border-rose-400' : ''"
                                    showIcon
                                    iconDisplay="input"
                                />
                                <p v-if="quickErrors.due_date" class="mt-0.5 text-[10px] text-rose-500">{{ quickErrors.due_date }}</p>
                            </div>
                        </div>

                        <!-- Form actions -->
                        <div class="flex items-center gap-1 border-t border-surface-100 px-2.5 py-2 dark:border-surface-700">
                            <Button label="Create" size="small" :loading="quickAddLoading" @click="submitQuickAdd" class="!text-xs" />
                            <Button label="Cancel" size="small" severity="secondary" text @click="cancelQuickAdd" class="!text-xs" />
                            <Button
                                icon="pi pi-external-link"
                                size="small"
                                severity="secondary"
                                text
                                v-tooltip.top="'Open full form'"
                                class="ml-auto !text-xs"
                                @click="
                                    emit('add', null, statusId);
                                    cancelQuickAdd();
                                "
                            />
                        </div>
                    </div>

                    <!-- ── Draggable cards ──────────────────────────────── -->
                    <VueDraggable
                        class="flex flex-col gap-2 overflow-y-auto"
                        style="max-height: calc(100vh - 320px); min-height: 48px"
                        v-model="grouped[statusId]"
                        :animation="180"
                        ghostClass="kanban-ghost"
                        group="kanban"
                        @add="(e: DraggableEvent<Task>) => onGroupChange(e.data, statusId)"
                        @start="
                            (e: any) => {
                                draggingItem = true;
                                draggingTaskId = e.item?.dataset?.taskId || null;
                            }
                        "
                        @end="
                            () => {
                                draggingItem = false;
                                draggingTaskId = null;
                            }
                        "
                    >
                        <!-- Task card -->
                        <div
                            v-for="task in filteredGrouped[statusId]"
                            :key="task.id"
                            :data-task-id="task.id"
                            class="group relative cursor-grab rounded-lg border bg-white shadow-sm transition-all duration-150 active:cursor-grabbing active:shadow-lg dark:bg-surface-800"
                            :class="[
                                isOverdue(task)
                                    ? 'border-l-[3px] border-surface-200 border-l-rose-400 dark:border-surface-700'
                                    : 'border-surface-200 dark:border-surface-700',
                                draggingTaskId === task.id ? 'opacity-50' : 'hover:border-blue-300 hover:shadow-md dark:hover:border-blue-600',
                            ]"
                            @click="openDetail(task)"
                            @contextmenu="openCardMenu($event, task)"
                        >
                            <div class="p-2.5">
                                <!-- Row 1: type + priority + overdue + kebab -->
                                <div class="mb-1.5 flex items-center gap-1">
                                    <span
                                        v-if="task.type"
                                        class="inline-flex items-center rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                                    >
                                        {{ task.type.name }}
                                    </span>
                                    <i
                                        v-if="task.priority"
                                        :class="`pi ${getPriorityIcon(task.priority.name)} text-xs`"
                                        :style="`color:${getPriorityColor(task.priority.severity)}`"
                                        :title="task.priority.name"
                                    />
                                    <span v-if="isOverdue(task)" class="ml-auto flex items-center gap-0.5 text-[10px] font-semibold text-rose-500">
                                        <i class="pi pi-clock text-[10px]" /> Overdue
                                    </span>
                                    <button
                                        class="ml-auto hidden rounded p-0.5 text-surface-400 hover:bg-surface-100 hover:text-surface-700 group-hover:block dark:hover:bg-surface-700"
                                        @click.stop="openCardMenu($event, task)"
                                    >
                                        <i class="pi pi-ellipsis-h text-xs" />
                                    </button>
                                </div>

                                <!-- Title -->
                                <p class="mb-2 line-clamp-3 text-[13px] font-medium leading-snug text-surface-800 dark:text-surface-100">
                                    {{ task.title }}
                                </p>

                                <!-- Due date -->
                                <div
                                    v-if="task.due_date"
                                    class="mb-2 flex items-center gap-1 text-[11px]"
                                    :class="isOverdue(task) ? 'text-rose-500' : 'text-surface-400'"
                                >
                                    <i class="pi pi-calendar text-[10px]" />
                                    {{ moment(task.due_date).format('DD MMM') }}
                                    <span class="text-surface-300 dark:text-surface-600">· {{ moment(task.due_date).fromNow() }}</span>
                                </div>

                                <!-- Subtask progress -->
                                <div v-if="subtaskCount(task) > 0" class="mb-2">
                                    <div class="mb-0.5 flex items-center justify-between text-[10px] text-surface-400">
                                        <span>Subtasks</span>
                                        <span>{{ doneSubtaskCount(task) }}/{{ subtaskCount(task) }}</span>
                                    </div>
                                    <div class="h-1 w-full overflow-hidden rounded-full bg-surface-100 dark:bg-surface-700">
                                        <div
                                            class="h-full rounded-full bg-emerald-400 transition-all"
                                            :style="`width:${Math.round((doneSubtaskCount(task) / subtaskCount(task)) * 100)}%`"
                                        />
                                    </div>
                                </div>

                                <!-- Bottom: subtask chip + avatars -->
                                <div class="flex items-center justify-between gap-1">
                                    <button
                                        v-if="subtaskCount(task) > 0"
                                        class="flex items-center gap-0.5 rounded px-1 py-0.5 text-[10px] text-surface-500 hover:bg-surface-100 dark:hover:bg-surface-700"
                                        @click.stop="openDetail(task)"
                                    >
                                        <i class="pi pi-sitemap text-[10px]" /> {{ subtaskCount(task) }}
                                    </button>
                                    <div v-else class="flex-1" />
                                    <div class="flex -space-x-1.5">
                                        <Avatar
                                            v-for="u in (task.users || []).slice(0, 3)"
                                            :key="u.id"
                                            :image="u.avatar_url && u.avatar_url !== '/images/default-avatar.png' ? u.avatar_url : undefined"
                                            :label="!u.avatar_url || u.avatar_url === '/images/default-avatar.png' ? getInitials(u.name) : undefined"
                                            shape="circle"
                                            :style="`background:${avatarColor(u.id)};color:white;font-size:.6rem;font-weight:600`"
                                            :title="u.name"
                                            class="!h-5 !w-5 border border-white dark:border-surface-800"
                                        />
                                        <span
                                            v-if="(task.users || []).length > 3"
                                            class="flex h-5 w-5 items-center justify-center rounded-full border border-white bg-surface-200 text-[9px] font-bold text-surface-600 dark:border-surface-800 dark:bg-surface-700"
                                        >
                                            +{{ (task.users || []).length - 3 }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hover action strip -->
                            <div
                                class="hidden items-center justify-end gap-0.5 rounded-b-lg border-t border-surface-100 bg-surface-50 px-2 py-1 group-hover:flex dark:border-surface-700 dark:bg-surface-800/60"
                            >
                                <Link :href="route('task.show', task.id)" @click.stop>
                                    <button
                                        class="rounded p-1 text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                                        title="Open"
                                    >
                                        <i class="pi pi-eye text-[11px]" />
                                    </button>
                                </Link>
                                <button
                                    v-if="canAct"
                                    class="rounded p-1 text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                                    title="Add subtask"
                                    @click.stop="emit('add', task.id)"
                                >
                                    <i class="pi pi-sitemap text-[11px]" />
                                </button>
                                <button
                                    v-if="canAct"
                                    class="rounded p-1 text-surface-400 hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-900/30"
                                    title="Edit"
                                    @click.stop="emit('edit', task, task.parent_id)"
                                >
                                    <i class="pi pi-pencil text-[11px]" />
                                </button>
                                <button
                                    v-if="canAct"
                                    class="rounded p-1 text-surface-400 hover:bg-rose-100 hover:text-rose-500 dark:hover:bg-rose-900/30"
                                    title="Delete"
                                    @click.stop="deleteTask(task)"
                                >
                                    <i class="pi pi-trash text-[11px]" />
                                </button>
                            </div>
                        </div>
                    </VueDraggable>

                    <!-- Empty drop zone -->
                    <div
                        v-if="(filteredGrouped[statusId]?.length ?? 0) === 0 && quickAddStatus !== statusId"
                        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-surface-200 py-6 text-surface-300 dark:border-surface-700"
                    >
                        <i class="pi pi-inbox mb-1.5 text-xl" />
                        <p class="text-xs">{{ hasActiveFilter ? 'No match' : 'Drop here' }}</p>
                    </div>

                    <!-- Column footer -->
                    <button
                        v-if="canAct && quickAddStatus !== statusId"
                        class="mt-2 flex w-full items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs text-surface-400 transition hover:bg-surface-200/60 hover:text-surface-600 dark:hover:bg-surface-700/60"
                        @click="startQuickAdd(statusId)"
                    >
                        <i class="pi pi-plus text-xs" /> Create issue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Context Menu ──────────────────────────────────────────────────────── -->
    <Menu ref="cardMenu" :model="cardMenuItems" popup />

    <!-- ── Detail Slide-over ─────────────────────────────────────────────────── -->
    <Dialog
        v-model:visible="detailPanel.visible"
        position="right"
        modal
        :style="{ width: '460px', height: '100dvh', margin: 0, borderRadius: 0 }"
        :contentStyle="{ padding: 0, height: '100%' }"
        :showHeader="false"
    >
        <div v-if="detailPanel.task" class="flex h-full flex-col overflow-hidden">
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-surface-100 p-4 dark:border-surface-700">
                <div class="flex flex-wrap items-center gap-2">
                    <Tag
                        v-if="detailPanel.task.type"
                        :value="detailPanel.task.type?.name"
                        :severity="detailPanel.task.type?.severity"
                        class="!text-xs"
                    />
                    <Tag
                        v-if="detailPanel.task.status"
                        :value="detailPanel.task.status?.name"
                        :severity="detailPanel.task.status?.severity"
                        class="!text-xs"
                    />
                    <Tag
                        v-if="detailPanel.task.priority"
                        :value="detailPanel.task.priority?.name"
                        :severity="detailPanel.task.priority?.severity"
                        class="!text-xs"
                    />
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <Link :href="route('task.show', detailPanel.task.id)">
                        <Button icon="pi pi-external-link" text rounded size="small" severity="secondary" v-tooltip.top="'Open full page'" />
                    </Link>
                    <Button
                        v-if="canAct"
                        icon="pi pi-pencil"
                        text
                        rounded
                        size="small"
                        severity="secondary"
                        v-tooltip.top="'Edit'"
                        @click="
                            emit('edit', detailPanel.task!, detailPanel.task!.parent_id);
                            detailPanel.visible = false;
                        "
                    />
                    <Button icon="pi pi-times" text rounded size="small" severity="secondary" @click="detailPanel.visible = false" />
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-4">
                <h2 class="mb-4 text-base font-semibold leading-snug text-surface-800 dark:text-surface-100">
                    {{ detailPanel.task.title }}
                </h2>

                <div class="mb-4 grid grid-cols-2 gap-x-4 gap-y-3">
                    <div>
                        <p class="mb-0.5 text-[10px] font-semibold uppercase text-surface-400">Due Date</p>
                        <span
                            :class="isOverdue(detailPanel.task) ? 'font-medium text-rose-500' : 'text-surface-700 dark:text-surface-300'"
                            class="text-sm"
                        >
                            {{ detailPanel.task.due_date ? moment(detailPanel.task.due_date).format('DD MMM YYYY') : '—' }}
                        </span>
                    </div>
                    <div>
                        <p class="mb-0.5 text-[10px] font-semibold uppercase text-surface-400">Start Date</p>
                        <span class="text-sm text-surface-700 dark:text-surface-300">
                            {{ detailPanel.task.start_date ? moment(detailPanel.task.start_date).format('DD MMM YYYY') : '—' }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <p class="mb-1 text-[10px] font-semibold uppercase text-surface-400">Progress</p>
                        <div class="flex items-center gap-2">
                            <ProgressBar :value="detailPanel.task.progress || 0" class="flex-1" style="height: 6px" :showValue="false" />
                            <span class="text-xs text-surface-500">{{ detailPanel.task.progress || 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Assignees -->
                <div class="mb-4">
                    <p class="mb-1.5 text-[10px] font-semibold uppercase text-surface-400">Assignees</p>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="u in detailPanel.task.users || []"
                            :key="u.id"
                            class="flex items-center gap-1.5 rounded-full bg-surface-100 px-2 py-1 text-xs dark:bg-surface-700"
                        >
                            <Avatar
                                :image="u.avatar_url && u.avatar_url !== '/images/default-avatar.png' ? u.avatar_url : undefined"
                                :label="!u.avatar_url || u.avatar_url === '/images/default-avatar.png' ? getInitials(u.name) : undefined"
                                shape="circle"
                                :style="`background:${avatarColor(u.id)};color:white;font-size:.6rem;font-weight:600`"
                                class="!h-5 !w-5"
                            />
                            <span class="text-surface-700 dark:text-surface-200">{{ u.name }}</span>
                        </div>
                        <span v-if="!(detailPanel.task.users || []).length" class="text-sm text-surface-400">Unassigned</span>
                    </div>
                </div>

                <!-- Subtasks -->
                <div class="mb-4">
                    <div class="mb-1.5 flex items-center justify-between">
                        <p class="text-[10px] font-semibold uppercase text-surface-400">
                            Subtasks
                            <span v-if="subtaskCount(detailPanel.task)"
                                >({{ doneSubtaskCount(detailPanel.task) }}/{{ subtaskCount(detailPanel.task) }})</span
                            >
                        </p>
                        <Button
                            label="Add subtask"
                            icon="pi pi-plus"
                            size="small"
                            text
                            :disabled="!canAct"
                            class="!text-xs"
                            @click="
                                emit('add', detailPanel.task!.id);
                                detailPanel.visible = false;
                            "
                        />
                    </div>
                    <div v-if="subtaskCount(detailPanel.task) > 0" class="flex flex-col gap-1.5">
                        <div
                            v-for="sub in detailPanel.task.sub_task_recursive"
                            :key="sub.id"
                            class="flex items-center justify-between rounded-lg border border-surface-100 bg-surface-50 px-3 py-2 dark:border-surface-700 dark:bg-surface-800"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <div
                                    class="h-2 w-2 shrink-0 rounded-full"
                                    :class="sub.status?.name?.toLowerCase().includes('done') ? 'bg-emerald-400' : 'bg-surface-300'"
                                />
                                <span class="truncate text-xs text-surface-700 dark:text-surface-200">{{ sub.title }}</span>
                            </div>
                            <div class="ml-2 flex shrink-0 items-center gap-1">
                                <Tag v-if="sub.status" :value="sub.status?.name" :severity="sub.status?.severity" class="!text-[10px]" />
                                <button
                                    v-if="canAct"
                                    class="rounded p-0.5 text-surface-400 hover:text-amber-500"
                                    @click="
                                        emit('edit', sub, sub.parent_id);
                                        detailPanel.visible = false;
                                    "
                                >
                                    <i class="pi pi-pencil text-[10px]" />
                                </button>
                                <Link :href="route('task.show', sub.id)">
                                    <button class="rounded p-0.5 text-surface-400 hover:text-surface-700">
                                        <i class="pi pi-external-link text-[10px]" />
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-1 text-xs text-surface-400">No subtasks yet.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between border-t border-surface-100 px-4 py-3 dark:border-surface-700">
                <Button
                    v-if="canAct"
                    label="Edit Task"
                    icon="pi pi-pencil"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="
                        emit('edit', detailPanel.task!, detailPanel.task!.parent_id);
                        detailPanel.visible = false;
                    "
                />
                <Button
                    v-if="canAct"
                    label="Delete"
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    text
                    @click="
                        deleteTask(detailPanel.task!);
                        detailPanel.visible = false;
                    "
                />
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.kanban-ghost {
    opacity: 0.3;
    background: #dbeafe;
    border: 1.5px dashed #93c5fd;
    border-radius: 8px;
}
</style>
