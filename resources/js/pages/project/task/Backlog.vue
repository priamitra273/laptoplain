<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { Sprint, Task, TaskCategory, TaskPriority, TaskStatus, TaskType, User } from './type';

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    projectId: string;
    sprints: Sprint[];
    backlog: Task[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    taskCategories: TaskCategory[];
    isMember: boolean;
    hasPermission: boolean;
    assignableUsers: User[];
}>();

const emit = defineEmits<{
    add: [parentId: string | null, statusId?: string];
    edit: [task: Task, parentId: string | null];
}>();

// ─── Composables ──────────────────────────────────────────────────────────────
const toast = useToast();
const confirm = useConfirm();
const page = usePage();

// ─── Local state ──────────────────────────────────────────────────────────────
const localSprints = ref<Sprint[]>([]);
const localBacklog = ref<Task[]>([]);

watch(
    () => props.sprints,
    (v) => {
        localSprints.value = v.map((s) => ({ ...s, tasks: [...(s.tasks ?? [])] }));
    },
    { immediate: true, deep: true },
);

watch(
    () => props.backlog,
    (v) => {
        localBacklog.value = [...v];
    },
    { immediate: true, deep: true },
);

// ─── UI state ─────────────────────────────────────────────────────────────────
const collapsedSections = ref<Set<string>>(new Set());
const searchQuery = ref('');
const quickAddSection = ref<string | null>(null); // sprint.id | 'backlog' | null
const quickAddTitle = ref('');
const quickAddLoading = ref(false);
const quickTitleInputRef = ref<HTMLInputElement | null>(null);

// Sprint dialog
const showSprintDialog = ref(false);
const editingSprint = ref<Sprint | null>(null);
const sprintLoading = ref(false);
const sprintForm = ref({ name: '', goal: '', duration: '2 weeks', start_date: null as Date | null, end_date: null as Date | null });
const sprintErrors = ref<Record<string, string>>({});

// Complete sprint dialog
const completingSprint = ref<Sprint | null>(null);
const completeLoading = ref(false);
const completeForm = ref({ retrospective: '', move_incomplete_to: null as string | null });

// ─── Computed ─────────────────────────────────────────────────────────────────
const canAct = computed(() => props.isMember || props.hasPermission);
const currentUser = computed(() => page.props.auth?.user as User | undefined);

const activeSprints = computed(() => localSprints.value.filter((s) => s.status?.name === 'Active'));
const planningSprints = computed(() => localSprints.value.filter((s) => s.status?.name === 'Planning'));
const completedSprints = computed(() => localSprints.value.filter((s) => s.status?.name === 'Completed'));
const allActiveSprints = computed(() => [...activeSprints.value, ...planningSprints.value]);

const filteredBacklog = computed(() => {
    if (!searchQuery.value) return localBacklog.value;
    const q = searchQuery.value.toLowerCase();
    return localBacklog.value.filter((t) => t.title.toLowerCase().includes(q));
});

const sprintTaskCount = (sprint: Sprint) => (sprint.tasks ?? []).length;
const sprintDoneCount = (sprint: Sprint) => (sprint.tasks ?? []).filter((t) => t.status?.name?.toLowerCase().match(/complete|done|finished/)).length;

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const avatarBg = (id: string) => {
    const p = ['#5E6AD2', '#26B5CE', '#4EA7FC', '#F2C94C', '#6FCF97', '#EB5757', '#BB87FC', '#F7936F'];
    let h = 0;
    for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) % p.length;
    return p[h];
};

const priorityIcon: Record<string, { icon: string; color: string }> = {
    highest: { icon: 'pi-angle-double-up', color: '#EF4444' },
    high: { icon: 'pi-angle-up', color: '#F59E0B' },
    medium: { icon: 'pi-minus', color: '#6B7280' },
    low: { icon: 'pi-angle-down', color: '#3B82F6' },
    lowest: { icon: 'pi-angle-double-down', color: '#9CA3AF' },
};

const getPriorityMeta = (name?: string) => priorityIcon[name?.toLowerCase() ?? ''] ?? priorityIcon.medium;

const isOverdue = (task: Task) =>
    !!task.due_date && moment(task.due_date).isBefore(moment(), 'day') && !task.status?.name?.toLowerCase().match(/complete|done|finished/);

const isDone = (task: Task) => !!task.status?.name?.toLowerCase().match(/complete|done|finished/);

const sprintDateLabel = (sprint: Sprint) => {
    const s = sprint.start_date ? moment(sprint.start_date).format('D MMM') : null;
    const e = sprint.end_date ? moment(sprint.end_date).format('D MMM') : null;
    if (s && e) return `${s} – ${e}`;
    if (e) return `Due ${e}`;
    return null;
};

const daysLeft = (sprint: Sprint) => {
    if (!sprint.end_date || sprint.status?.name !== 'Active') return null;
    return moment(sprint.end_date).diff(moment(), 'days');
};

const toggleSection = (id: string) => {
    const s = new Set(collapsedSections.value);
    s.has(id) ? s.delete(id) : s.add(id);
    collapsedSections.value = s;
};

const isCollapsed = (id: string) => collapsedSections.value.has(id);

// ─── Sprint CRUD ──────────────────────────────────────────────────────────────
const openCreateSprint = () => {
    editingSprint.value = null;
    sprintForm.value = { name: '', goal: '', duration: '2 weeks', start_date: null, end_date: null };
    sprintErrors.value = {};
    showSprintDialog.value = true;
};

const openEditSprint = (sprint: Sprint) => {
    editingSprint.value = sprint;
    sprintForm.value = {
        name: sprint.name ?? '',
        goal: sprint.goal ?? '',
        duration: sprint.duration ?? '2 weeks',
        start_date: sprint.start_date ? new Date(sprint.start_date) : null,
        end_date: sprint.end_date ? new Date(sprint.end_date) : null,
    };
    sprintErrors.value = {};
    showSprintDialog.value = true;
};

const submitSprint = async () => {
    sprintErrors.value = {};
    if (!sprintForm.value.name.trim()) {
        sprintErrors.value.name = 'Nama sprint wajib diisi.';
        return;
    }
    sprintLoading.value = true;

    const payload = {
        name: sprintForm.value.name.trim(),
        goal: sprintForm.value.goal || null,
        duration: sprintForm.value.duration,
        start_date: sprintForm.value.start_date ? moment(sprintForm.value.start_date).format('YYYY-MM-DD') : null,
        end_date: sprintForm.value.end_date ? moment(sprintForm.value.end_date).format('YYYY-MM-DD') : null,
    };

    try {
        if (editingSprint.value) {
            await axios.put(route('project.sprints.update', { projectEncoded: props.projectId, sprintEncoded: editingSprint.value.id }), payload);
        } else {
            await axios.post(route('project.sprints.store', { projectEncoded: props.projectId }), payload);
        }
        showSprintDialog.value = false;
        toast.add({ severity: 'success', summary: editingSprint.value ? 'Sprint diperbarui' : 'Sprint dibuat', life: 2000 });
        router.reload({ only: ['sprints'] });
    } catch (err: any) {
        const errs = err?.response?.data?.errors as Record<string, string[]> | undefined;
        if (errs) Object.entries(errs).forEach(([k, v]) => (sprintErrors.value[k] = v[0]));
        else toast.add({ severity: 'error', summary: 'Gagal menyimpan sprint', life: 3000 });
    } finally {
        sprintLoading.value = false;
    }
};

const startSprint = (sprint: Sprint) => {
    confirm.require({
        message: `Mulai "${sprint.name}"?`,
        header: 'Mulai Sprint',
        icon: 'pi pi-play',
        acceptLabel: 'Mulai',
        rejectLabel: 'Batal',
        accept: async () => {
            try {
                await axios.patch(route('project.sprints.start', { projectEncoded: props.projectId, sprintEncoded: sprint.id }));
                toast.add({ severity: 'success', summary: 'Sprint dimulai!', life: 2000 });
                router.reload({ only: ['sprints'] });
            } catch (err: any) {
                toast.add({ severity: 'error', summary: err?.response?.data?.message ?? 'Gagal memulai sprint', life: 4000 });
            }
        },
    });
};

const openCompleteSprint = (sprint: Sprint) => {
    completeForm.value = { retrospective: '', move_incomplete_to: null };
    completingSprint.value = sprint;
};

const submitComplete = async () => {
    if (!completingSprint.value) return;
    completeLoading.value = true;
    try {
        await axios.patch(route('project.sprints.complete', { projectEncoded: props.projectId, sprintEncoded: completingSprint.value.id }), {
            retrospective: completeForm.value.retrospective || null,
            move_incomplete_to: completeForm.value.move_incomplete_to,
        });
        toast.add({ severity: 'success', summary: 'Sprint selesai!', life: 2000 });
        completingSprint.value = null;
        router.reload({ only: ['sprints', 'backlog'] });
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal menyelesaikan sprint', life: 3000 });
    } finally {
        completeLoading.value = false;
    }
};

const deleteSprint = (sprint: Sprint) => {
    confirm.require({
        message: `Hapus "${sprint.name}"? Task akan kembali ke backlog.`,
        header: 'Hapus Sprint',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Hapus',
        rejectLabel: 'Batal',
        accept: async () => {
            try {
                await axios.delete(route('project.sprints.destroy', { projectEncoded: props.projectId, sprintEncoded: sprint.id }));
                toast.add({ severity: 'success', summary: 'Sprint dihapus', life: 2000 });
                router.reload({ only: ['sprints', 'backlog'] });
            } catch {
                toast.add({ severity: 'error', summary: 'Gagal menghapus sprint', life: 3000 });
            }
        },
    });
};

// ─── Quick Add ────────────────────────────────────────────────────────────────
const startQuickAdd = async (section: string) => {
    if (!canAct.value) return;
    quickAddSection.value = section;
    quickAddTitle.value = '';
    await nextTick();
    quickTitleInputRef.value?.focus();
};

const cancelQuickAdd = () => {
    quickAddSection.value = null;
    quickAddTitle.value = '';
};

const submitQuickAdd = async () => {
    const title = quickAddTitle.value.trim();
    if (!title) {
        cancelQuickAdd();
        return;
    }

    quickAddLoading.value = true;
    const isBacklog = quickAddSection.value === 'backlog';
    const sprintId = isBacklog ? null : quickAddSection.value;

    try {
        const defaultStatus = props.taskStatuses[0];
        const defaultPriority = props.taskPriorities.find((p) => p.name?.toLowerCase() === 'medium') ?? props.taskPriorities[0];
        const defaultType = props.taskTypes[0];

        const res = await axios.post(route('project.tasks.store', { projectEncoded: props.projectId }), {
            title,
            status_id: defaultStatus?.id,
            priority_id: defaultPriority?.id,
            type_id: defaultType?.id,
            assign_users: currentUser.value ? [currentUser.value.id] : [],
        });

        if (sprintId && res.data?.task?.id) {
            await axios.post(route('project.sprints.tasks.assign', { projectEncoded: props.projectId, sprintEncoded: sprintId }), {
                task_ids: [res.data.task.id],
            });
        }

        toast.add({ severity: 'success', summary: 'Task dibuat', life: 1500 });
        cancelQuickAdd();
        router.reload({ only: ['sprints', 'backlog'] });
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal membuat task', life: 3000 });
    } finally {
        quickAddLoading.value = false;
    }
};

// ─── Drag & Drop ──────────────────────────────────────────────────────────────
const onDropToSprint = async (task: Task, sprintId: string) => {
    try {
        await axios.post(route('project.sprints.tasks.assign', { projectEncoded: props.projectId, sprintEncoded: sprintId }), {
            task_ids: [task.id],
        });
    } catch (err: any) {
        toast.add({ severity: 'error', summary: err?.response?.data?.message ?? 'Gagal memindahkan task', life: 3000 });
        router.reload({ only: ['sprints', 'backlog'] });
    }
};

const onDropToBacklog = async (task: Task, sprintId: string) => {
    try {
        await axios.delete(route('project.sprints.tasks.remove', { projectEncoded: props.projectId, sprintEncoded: sprintId, taskEncoded: task.id }));
    } catch {
        toast.add({ severity: 'error', summary: 'Gagal memindahkan task', life: 3000 });
        router.reload({ only: ['sprints', 'backlog'] });
    }
};

const incompleteCount = (sprint: Sprint) => (sprint.tasks ?? []).filter((t) => !isDone(t)).length;
</script>

<template>
    <div class="flex flex-col" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif">
        <!-- ── Toolbar ──────────────────────────────────────────────────────── -->
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="relative">
                    <i class="pi pi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] text-[#8993A4]" />
                    <input
                        v-model="searchQuery"
                        placeholder="Search backlog"
                        class="h-7 rounded border border-[#DFE1E6] bg-[#F4F5F7] pl-7 pr-3 text-xs text-[#172B4D] placeholder-[#8993A4] outline-none transition focus:border-[#4C9AFF] focus:bg-white dark:border-[#3B4559] dark:bg-[#1D2125] dark:text-[#B8C0CC] dark:focus:bg-[#22272B]"
                        style="width: 180px"
                    />
                </div>
                <!-- Assignee filter avatars placeholder -->
                <div class="flex -space-x-1">
                    <button
                        v-for="u in assignableUsers.slice(0, 4)"
                        :key="u.id"
                        class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-[9px] font-bold text-white transition hover:z-10 hover:scale-110 dark:border-[#1D2125]"
                        :style="`background:${avatarBg(u.id)}`"
                        :title="u.name"
                    >
                        {{ getInitials(u.name) }}
                    </button>
                </div>
            </div>

            <Button v-if="canAct" label="Create Sprint" icon="pi pi-plus" size="small" class="!h-7 !text-xs" @click="openCreateSprint" />
        </div>

        <!-- ── Active & Planning Sprints ───────────────────────────────────── -->
        <div
            v-for="sprint in allActiveSprints"
            :key="sprint.id"
            class="mb-3 overflow-hidden rounded"
            style="border: 1px solid #dfe1e6"
            :class="{ 'dark:border-[#3B4559]': true }"
        >
            <!-- Sprint Header -->
            <div
                class="flex cursor-pointer select-none items-center gap-2 px-3 py-2"
                style="background: #f4f5f7"
                :class="sprint.status?.name === 'Active' ? 'dark:bg-[#1C3A2E]' : 'dark:bg-[#22272B]'"
                @click="toggleSection(sprint.id)"
            >
                <!-- Chevron -->
                <i :class="`pi ${isCollapsed(sprint.id) ? 'pi-chevron-right' : 'pi-chevron-down'} text-[10px] text-[#5E6C84] dark:text-[#8993A4]`" />

                <!-- Sprint name -->
                <span class="text-sm font-semibold text-[#172B4D] dark:text-[#B8C0CC]">
                    {{ sprint.name }}
                </span>

                <!-- Date range -->
                <span v-if="sprintDateLabel(sprint)" class="text-xs text-[#5E6C84] dark:text-[#6B778C]">
                    {{ sprintDateLabel(sprint) }}
                </span>

                <!-- Days left badge (active only) -->
                <span
                    v-if="sprint.status?.name === 'Active' && daysLeft(sprint) !== null"
                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                    :class="
                        (daysLeft(sprint) ?? 0) < 0
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'
                            : (daysLeft(sprint) ?? 0) <= 2
                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                              : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
                    "
                >
                    {{ (daysLeft(sprint) ?? 0) < 0 ? `${Math.abs(daysLeft(sprint) ?? 0)}d overdue` : `${daysLeft(sprint)}d left` }}
                </span>

                <!-- Work items count -->
                <span class="text-xs text-[#5E6C84] dark:text-[#6B778C]">
                    ({{ sprintTaskCount(sprint) }} work item{{ sprintTaskCount(sprint) !== 1 ? 's' : '' }})
                </span>

                <!-- Progress: done/total -->
                <span v-if="sprint.status?.name === 'Active'" class="ml-1 text-[10px] text-[#5E6C84] dark:text-[#6B778C]">
                    {{ sprintDoneCount(sprint) }}/{{ sprintTaskCount(sprint) }} done
                </span>

                <!-- Spacer -->
                <div class="flex-1" />

                <!-- Action buttons -->
                <div class="flex items-center gap-1" @click.stop>
                    <!-- Start sprint -->
                    <button
                        v-if="canAct && sprint.status?.name === 'Planning'"
                        class="rounded px-2.5 py-1 text-xs font-medium text-[#0052CC] transition hover:bg-[#0052CC] hover:text-white dark:text-[#4C9AFF] dark:hover:bg-[#0065FF] dark:hover:text-white"
                        style="border: 1px solid currentColor"
                        @click="startSprint(sprint)"
                    >
                        Start sprint
                    </button>

                    <!-- Complete sprint -->
                    <button
                        v-if="canAct && sprint.status?.name === 'Active'"
                        class="rounded px-2.5 py-1 text-xs font-medium text-[#0052CC] transition hover:bg-[#0052CC] hover:text-white dark:text-[#4C9AFF] dark:hover:bg-[#0065FF] dark:hover:text-white"
                        style="border: 1px solid currentColor"
                        @click="openCompleteSprint(sprint)"
                    >
                        Complete sprint
                    </button>

                    <!-- ⋯ Menu -->
                    <div class="group/menu relative">
                        <button
                            class="flex h-6 w-6 items-center justify-center rounded text-[#5E6C84] hover:bg-[#DFE1E6] dark:text-[#8993A4] dark:hover:bg-[#2C333A]"
                            @click.stop
                        >
                            <i class="pi pi-ellipsis-h text-xs" />
                        </button>
                        <!-- Dropdown -->
                        <div
                            class="absolute right-0 top-full z-50 mt-0.5 hidden w-36 overflow-hidden rounded border border-[#DFE1E6] bg-white shadow-lg group-hover/menu:block dark:border-[#3B4559] dark:bg-[#22272B]"
                        >
                            <button
                                v-if="canAct"
                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-[#172B4D] hover:bg-[#F4F5F7] dark:text-[#B8C0CC] dark:hover:bg-[#2C333A]"
                                @click.stop="openEditSprint(sprint)"
                            >
                                <i class="pi pi-pencil text-[10px]" /> Edit sprint
                            </button>
                            <button
                                v-if="canAct && sprint.status?.name === 'Planning'"
                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-red-600 hover:bg-[#F4F5F7] dark:hover:bg-[#2C333A]"
                                @click.stop="deleteSprint(sprint)"
                            >
                                <i class="pi pi-trash text-[10px]" /> Delete sprint
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sprint Body -->
            <div v-show="!isCollapsed(sprint.id)" class="bg-white dark:bg-[#1D2125]">
                <!-- Task rows (draggable) -->
                <VueDraggable
                    :model-value="sprint.tasks ?? []"
                    @update:model-value="() => {}"
                    :animation="150"
                    ghost-class="jira-ghost"
                    group="backlog"
                    class="min-h-[4px]"
                    @add="(e: any) => onDropToSprint(e.data, sprint.id)"
                >
                    <!-- Task row -->
                    <div
                        v-for="task in (sprint.tasks ?? []).filter((t) => !searchQuery || t.title.toLowerCase().includes(searchQuery.toLowerCase()))"
                        :key="task.id"
                        class="group/row flex cursor-grab items-center gap-2 border-b border-[#F4F5F7] px-3 py-1.5 hover:bg-[#F8F9FA] active:cursor-grabbing dark:border-[#2C333A] dark:hover:bg-[#22272B]"
                    >
                        <!-- Type icon (small colored square like Jira) -->
                        <div
                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm text-[8px] font-bold text-white"
                            :style="`background: ${task.category?.name === 'Epic' ? '#7B61FF' : task.category?.name === 'Story' ? '#36B37E' : '#0065FF'}`"
                            :title="task.category?.name ?? task.type?.name"
                        >
                            {{ (task.category?.name ?? task.type?.name ?? 'T')[0] }}
                        </div>

                        <!-- Task key (if any) + title -->
                        <a
                            :href="route('task.show', task.id)"
                            class="min-w-0 flex-1 text-sm text-[#172B4D] hover:text-[#0052CC] hover:underline dark:text-[#B8C0CC] dark:hover:text-[#4C9AFF]"
                            :class="{ 'line-through opacity-50': isDone(task) }"
                            @click.stop
                        >
                            {{ task.title }}
                        </a>

                        <!-- Right side meta -->
                        <div class="flex shrink-0 items-center gap-2 opacity-0 transition-opacity group-hover/row:opacity-100">
                            <!-- Edit -->
                            <button
                                v-if="canAct"
                                class="flex h-5 w-5 items-center justify-center rounded text-[#5E6C84] hover:bg-[#DFE1E6] dark:text-[#8993A4] dark:hover:bg-[#2C333A]"
                                @click.stop="emit('edit', task, task.parent_id ?? null)"
                            >
                                <i class="pi pi-pencil text-[9px]" />
                            </button>
                        </div>

                        <!-- Status badge -->
                        <span
                            class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                            :class="
                                isDone(task)
                                    ? 'bg-[#E3FCEF] text-[#006644] dark:bg-[#1C3A2E] dark:text-[#57D9A3]'
                                    : 'bg-[#DFE1E6] text-[#42526E] dark:bg-[#2C333A] dark:text-[#8993A4]'
                            "
                        >
                            {{ task.status?.name ?? '—' }}
                        </span>

                        <!-- Due date -->
                        <span
                            v-if="task.due_date"
                            class="shrink-0 text-xs"
                            :class="isOverdue(task) ? 'font-medium text-red-600 dark:text-red-400' : 'text-[#5E6C84] dark:text-[#6B778C]'"
                        >
                            {{ moment(task.due_date).format('D MMM') }}
                        </span>

                        <!-- Priority icon -->
                        <i
                            v-if="task.priority"
                            :class="`pi ${getPriorityMeta(task.priority.name).icon} text-xs`"
                            :style="`color: ${getPriorityMeta(task.priority.name).color}`"
                            :title="task.priority.name"
                        />

                        <!-- Assignee avatars -->
                        <div class="flex -space-x-1">
                            <div
                                v-for="u in (task.users ?? []).slice(0, 2)"
                                :key="u.id"
                                class="flex h-5 w-5 items-center justify-center rounded-full border border-white text-[8px] font-bold text-white dark:border-[#1D2125]"
                                :style="`background: ${avatarBg(u.id)}`"
                                :title="u.name"
                            >
                                {{ getInitials(u.name) }}
                            </div>
                        </div>
                    </div>
                </VueDraggable>

                <!-- Quick add row -->
                <div v-if="quickAddSection === sprint.id" class="border-b border-[#F4F5F7] px-3 py-1.5 dark:border-[#2C333A]">
                    <div class="flex items-center gap-2">
                        <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm bg-[#0065FF] text-[8px] font-bold text-white">T</div>
                        <input
                            ref="quickTitleInputRef"
                            v-model="quickAddTitle"
                            placeholder="What needs to be done?"
                            class="flex-1 bg-transparent text-sm text-[#172B4D] placeholder-[#8993A4] outline-none dark:text-[#B8C0CC]"
                            @keyup.enter="submitQuickAdd"
                            @keyup.escape="cancelQuickAdd"
                        />
                        <button
                            class="rounded bg-[#0052CC] px-2.5 py-1 text-xs font-medium text-white hover:bg-[#0065FF]"
                            :class="quickAddLoading ? 'pointer-events-none opacity-60' : ''"
                            @click="submitQuickAdd"
                        >
                            {{ quickAddLoading ? '...' : 'Create' }}
                        </button>
                        <button class="text-xs text-[#5E6C84] hover:text-[#172B4D] dark:text-[#8993A4]" @click="cancelQuickAdd">Cancel</button>
                    </div>
                </div>

                <!-- Footer: + Create -->
                <button
                    v-if="canAct && quickAddSection !== sprint.id"
                    class="flex w-full items-center gap-1.5 px-3 py-2 text-xs text-[#5E6C84] transition-colors hover:bg-[#F4F5F7] hover:text-[#172B4D] dark:text-[#6B778C] dark:hover:bg-[#22272B] dark:hover:text-[#B8C0CC]"
                    @click="startQuickAdd(sprint.id)"
                >
                    <i class="pi pi-plus text-[10px]" />
                    Create
                </button>
            </div>
        </div>

        <!-- ── Backlog Section ───────────────────────────────────────────────── -->
        <div class="overflow-hidden rounded" style="border: 1px solid #dfe1e6" :class="{ 'dark:border-[#3B4559]': true }">
            <!-- Backlog Header -->
            <div
                class="flex cursor-pointer select-none items-center gap-2 px-3 py-2"
                style="background: #f4f5f7"
                :class="'dark:bg-[#22272B]'"
                @click="toggleSection('backlog')"
            >
                <i :class="`pi ${isCollapsed('backlog') ? 'pi-chevron-right' : 'pi-chevron-down'} text-[10px] text-[#5E6C84] dark:text-[#8993A4]`" />
                <span class="text-sm font-semibold text-[#172B4D] dark:text-[#B8C0CC]">Backlog</span>
                <span class="text-xs text-[#5E6C84] dark:text-[#6B778C]">
                    ({{ filteredBacklog.length }} work item{{ filteredBacklog.length !== 1 ? 's' : '' }})
                </span>

                <div class="flex-1" />

                <div class="flex items-center gap-2" @click.stop>
                    <button
                        v-if="canAct"
                        class="rounded px-2.5 py-1 text-xs font-medium text-[#0052CC] transition hover:bg-[#0052CC] hover:text-white dark:text-[#4C9AFF] dark:hover:bg-[#0065FF] dark:hover:text-white"
                        style="border: 1px solid currentColor"
                        @click="openCreateSprint"
                    >
                        Create Sprint
                    </button>
                </div>
            </div>

            <!-- Backlog Body -->
            <div v-show="!isCollapsed('backlog')" class="bg-white dark:bg-[#1D2125]">
                <!-- Draggable task list -->
                <VueDraggable v-model="localBacklog" :animation="150" ghost-class="jira-ghost" group="backlog" class="min-h-[40px]">
                    <div
                        v-for="task in filteredBacklog"
                        :key="task.id"
                        class="group/row flex cursor-grab items-center gap-2 border-b border-[#F4F5F7] px-3 py-1.5 hover:bg-[#F8F9FA] active:cursor-grabbing dark:border-[#2C333A] dark:hover:bg-[#22272B]"
                    >
                        <!-- Type icon -->
                        <div
                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm text-[8px] font-bold text-white"
                            :style="`background: ${task.category?.name === 'Epic' ? '#7B61FF' : task.category?.name === 'Story' ? '#36B37E' : '#0065FF'}`"
                            :title="task.category?.name ?? task.type?.name"
                        >
                            {{ (task.category?.name ?? task.type?.name ?? 'T')[0] }}
                        </div>

                        <!-- Title -->
                        <a
                            :href="route('task.show', task.id)"
                            class="min-w-0 flex-1 text-sm text-[#172B4D] hover:text-[#0052CC] hover:underline dark:text-[#B8C0CC] dark:hover:text-[#4C9AFF]"
                            :class="{ 'line-through opacity-50': isDone(task) }"
                            @click.stop
                        >
                            {{ task.title }}
                        </a>

                        <!-- Hover actions -->
                        <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover/row:opacity-100">
                            <button
                                v-if="canAct"
                                class="flex h-5 w-5 items-center justify-center rounded text-[#5E6C84] hover:bg-[#DFE1E6] dark:text-[#8993A4] dark:hover:bg-[#2C333A]"
                                @click.stop="emit('edit', task, task.parent_id ?? null)"
                            >
                                <i class="pi pi-pencil text-[9px]" />
                            </button>
                        </div>

                        <!-- Status badge -->
                        <span
                            class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                            :class="
                                isDone(task)
                                    ? 'bg-[#E3FCEF] text-[#006644] dark:bg-[#1C3A2E] dark:text-[#57D9A3]'
                                    : 'bg-[#DFE1E6] text-[#42526E] dark:bg-[#2C333A] dark:text-[#8993A4]'
                            "
                        >
                            {{ task.status?.name ?? '—' }}
                        </span>

                        <!-- Due date -->
                        <span
                            v-if="task.due_date"
                            class="shrink-0 text-xs"
                            :class="isOverdue(task) ? 'font-medium text-red-600 dark:text-red-400' : 'text-[#5E6C84] dark:text-[#6B778C]'"
                        >
                            {{ moment(task.due_date).format('D MMM') }}
                        </span>

                        <!-- Priority -->
                        <i
                            v-if="task.priority"
                            :class="`pi ${getPriorityMeta(task.priority.name).icon} text-xs`"
                            :style="`color: ${getPriorityMeta(task.priority.name).color}`"
                            :title="task.priority.name"
                        />

                        <!-- Assignees -->
                        <div class="flex -space-x-1">
                            <div
                                v-for="u in (task.users ?? []).slice(0, 2)"
                                :key="u.id"
                                class="flex h-5 w-5 items-center justify-center rounded-full border border-white text-[8px] font-bold text-white dark:border-[#1D2125]"
                                :style="`background: ${avatarBg(u.id)}`"
                                :title="u.name"
                            >
                                {{ getInitials(u.name) }}
                            </div>
                        </div>
                    </div>
                </VueDraggable>

                <!-- Empty state -->
                <div
                    v-if="filteredBacklog.length === 0 && quickAddSection !== 'backlog'"
                    class="px-3 py-6 text-center text-xs text-[#5E6C84] dark:text-[#6B778C]"
                >
                    Your backlog is empty.
                </div>

                <!-- Quick add row -->
                <div v-if="quickAddSection === 'backlog'" class="border-b border-[#F4F5F7] px-3 py-1.5 dark:border-[#2C333A]">
                    <div class="flex items-center gap-2">
                        <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm bg-[#0065FF] text-[8px] font-bold text-white">T</div>
                        <input
                            ref="quickTitleInputRef"
                            v-model="quickAddTitle"
                            placeholder="What needs to be done?"
                            class="flex-1 bg-transparent text-sm text-[#172B4D] placeholder-[#8993A4] outline-none dark:text-[#B8C0CC]"
                            @keyup.enter="submitQuickAdd"
                            @keyup.escape="cancelQuickAdd"
                        />
                        <button class="rounded bg-[#0052CC] px-2.5 py-1 text-xs font-medium text-white hover:bg-[#0065FF]" @click="submitQuickAdd">
                            {{ quickAddLoading ? '...' : 'Create' }}
                        </button>
                        <button class="text-xs text-[#5E6C84] hover:text-[#172B4D] dark:text-[#8993A4]" @click="cancelQuickAdd">Cancel</button>
                    </div>
                </div>

                <!-- Footer: + Create -->
                <button
                    v-if="canAct && quickAddSection !== 'backlog'"
                    class="flex w-full items-center gap-1.5 px-3 py-2 text-xs text-[#5E6C84] transition-colors hover:bg-[#F4F5F7] hover:text-[#172B4D] dark:text-[#6B778C] dark:hover:bg-[#22272B] dark:hover:text-[#B8C0CC]"
                    @click="startQuickAdd('backlog')"
                >
                    <i class="pi pi-plus text-[10px]" />
                    Create
                </button>
            </div>
        </div>

        <!-- ── Completed Sprints (collapsed summary) ─────────────────────────── -->
        <div v-if="completedSprints.length" class="mt-3">
            <button
                class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs text-[#5E6C84] hover:bg-[#F4F5F7] dark:text-[#6B778C] dark:hover:bg-[#22272B]"
                @click="toggleSection('completed')"
            >
                <i :class="`pi ${isCollapsed('completed') ? 'pi-chevron-right' : 'pi-chevron-down'} text-[10px]`" />
                <span class="font-medium">Completed sprints ({{ completedSprints.length }})</span>
            </button>

            <div v-show="!isCollapsed('completed')" class="mt-1 flex flex-col gap-1">
                <div
                    v-for="sprint in completedSprints"
                    :key="sprint.id"
                    class="flex items-center gap-3 rounded border border-[#DFE1E6] bg-[#F4F5F7] px-3 py-2 dark:border-[#3B4559] dark:bg-[#22272B]"
                >
                    <span class="flex-1 text-xs font-medium text-[#42526E] dark:text-[#8993A4]">{{ sprint.name }}</span>
                    <span class="text-[10px] text-[#5E6C84] dark:text-[#6B778C]">{{ sprintDateLabel(sprint) }}</span>
                    <span class="rounded bg-[#E3FCEF] px-1.5 py-0.5 text-[10px] font-semibold text-[#006644] dark:bg-[#1C3A2E] dark:text-[#57D9A3]"
                        >DONE</span
                    >
                    <span class="text-[10px] text-[#5E6C84] dark:text-[#6B778C]">
                        {{ sprintDoneCount(sprint) }}/{{ sprintTaskCount(sprint) }} tasks
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Create/Edit Sprint Dialog ─────────────────────────────────────────── -->
    <Dialog
        v-model:visible="showSprintDialog"
        :header="editingSprint ? 'Edit Sprint' : 'Create Sprint'"
        modal
        style="width: 440px"
        :draggable="false"
    >
        <div class="flex flex-col gap-4 p-1">
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]"
                    >Sprint name <span class="text-red-500">*</span></label
                >
                <InputText v-model="sprintForm.name" class="w-full" :class="sprintErrors.name ? '!border-red-400' : ''" autofocus />
                <p v-if="sprintErrors.name" class="mt-1 text-xs text-red-500">{{ sprintErrors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]">Sprint goal</label>
                <Textarea v-model="sprintForm.goal" rows="2" class="w-full !resize-none text-sm" placeholder="What do you want to achieve?" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]">Duration</label>
                <Select v-model="sprintForm.duration" :options="DURATION_OPTIONS" class="w-full text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]">Start date</label>
                    <DatePicker v-model="sprintForm.start_date" date-format="dd M yy" show-icon icon-display="input" class="w-full text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]">End date</label>
                    <DatePicker
                        v-model="sprintForm.end_date"
                        date-format="dd M yy"
                        show-icon
                        icon-display="input"
                        class="w-full text-sm"
                        :min-date="sprintForm.start_date ?? undefined"
                    />
                    <p v-if="sprintErrors.end_date" class="mt-1 text-xs text-red-500">{{ sprintErrors.end_date }}</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-[#DFE1E6] pt-3 dark:border-[#3B4559]">
                <Button label="Cancel" severity="secondary" text @click="showSprintDialog = false" />
                <Button :label="editingSprint ? 'Save' : 'Create Sprint'" :loading="sprintLoading" @click="submitSprint" />
            </div>
        </div>
    </Dialog>

    <!-- ── Complete Sprint Dialog ─────────────────────────────────────────────── -->
    <Dialog
        :visible="!!completingSprint"
        @update:visible="
            (v) => {
                if (!v) completingSprint = null;
            }
        "
        header="Complete Sprint"
        modal
        style="width: 480px"
        :draggable="false"
    >
        <div v-if="completingSprint" class="flex flex-col gap-4 p-1">
            <div class="rounded bg-[#F4F5F7] p-3 text-sm dark:bg-[#22272B]">
                <p class="font-semibold text-[#172B4D] dark:text-[#B8C0CC]">{{ completingSprint.name }}</p>
                <p class="mt-1 text-xs text-[#5E6C84] dark:text-[#6B778C]">
                    {{ sprintDoneCount(completingSprint) }} completed ·
                    <span v-if="incompleteCount(completingSprint)" class="text-amber-600 dark:text-amber-400">
                        {{ incompleteCount(completingSprint) }} incomplete
                    </span>
                    <span v-else class="text-green-600">All done!</span>
                </p>
            </div>

            <div v-if="incompleteCount(completingSprint)">
                <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]">Move incomplete issues to</label>
                <Select
                    v-model="completeForm.move_incomplete_to"
                    :options="[{ id: null, name: 'Backlog' }, ...planningSprints.map((s) => ({ id: s.id, name: s.name }))]"
                    option-label="name"
                    option-value="id"
                    placeholder="Backlog"
                    class="w-full text-sm"
                />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-[#42526E] dark:text-[#8993A4]"
                    >Retrospective <span class="font-normal text-[#8993A4]">(optional)</span></label
                >
                <Textarea v-model="completeForm.retrospective" rows="3" class="w-full text-sm" placeholder="What went well? What to improve?" />
            </div>

            <div class="flex justify-end gap-2 border-t border-[#DFE1E6] pt-3 dark:border-[#3B4559]">
                <Button label="Cancel" severity="secondary" text @click="completingSprint = null" />
                <Button label="Complete Sprint" :loading="completeLoading" @click="submitComplete" />
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.jira-ghost {
    opacity: 0.3;
    background: #deebff !important;
    border: 1.5px dashed #4c9aff !important;
}
</style>
