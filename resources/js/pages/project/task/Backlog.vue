<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { Task, TaskPriority, TaskStatus, TaskType } from '..';
import BacklogSection from './partials/BacklogSection.vue';
import CompleteSprintDialog from './partials/CompleteSprintDialog.vue';
import EditSprintDialog from './partials/EditSprintDialog.vue';
import EpicPanel from './partials/EpicPanel.vue';
import SprintSection from './partials/SprintSection.vue';
import StartSprintDialog from './partials/StartSprintDialog.vue';
import type { Sprint, TaskCategory } from './type';

interface User {
    id: string;
    name: string;
    avatar_url?: string | null;
}
interface Epic {
    id: string;
    title: string;
}

const props = defineProps<{
    projectId: string;
    sprints: Sprint[];
    backlog: Task[];
    epics: Epic[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    taskCategories: TaskCategory[];
    isMember: boolean;
    hasPermission: boolean;
    assignableUsers: User[];
}>();

const emit = defineEmits(['add', 'edit']);
const toast = useToast();
const canAct = computed(() => props.isMember || props.hasPermission);

// ─── Helpers ──────────────────────────────────────────────────────
const r = (name: string, sprintId?: string) =>
    route(`project.sprints.${name}`, {
        projectEncoded: props.projectId,
        ...(sprintId ? { sprintEncoded: sprintId } : {}),
    });

const notify = (severity: 'success' | 'error', summary: string) => toast.add({ severity, summary, life: 2500 });

const opts = { preserveScroll: true, onError: (e: any) => notify('error', (Object.values(e)[0] as string) ?? 'Something went wrong') };

// ─── Epic Panel ───────────────────────────────────────────────────
const selectedEpicId = ref<string | null>(null);

// Epic filter: task punya relasi parent yang category-nya Epic
// Backend kirim epic_id di setiap task untuk keperluan filter
const filterByEpic = (tasks: Task[]) =>
    selectedEpicId.value ? tasks.filter((t) => t.parent_id === selectedEpicId.value || t.id === selectedEpicId.value) : tasks;

const filteredBacklog = computed(() => filterByEpic(props.backlog));
const filteredSprintTasks = (sprint: Sprint) => (selectedEpicId.value ? filterByEpic(sprint.tasks ?? []) : undefined);

// ─── Create Sprint ────────────────────────────────────────────────
const createSprint = () => {
    router.post(r('store'), { name: `Sprint ${props.sprints.length + 1}` }, opts);
};

// ─── Start Sprint ─────────────────────────────────────────────────
const showStartDialog = ref(false);
const startingSprint = ref<Sprint | null>(null);

const openStartDialog = (sprint: Sprint) => {
    startingSprint.value = sprint;
    showStartDialog.value = true;
};

const saveStartSprint = (form: object) => {
    router.patch(r('start', startingSprint.value!.id), form, {
        ...opts,
        onSuccess: () => {
            showStartDialog.value = false;
        },
    });
};

// ─── Edit Sprint ──────────────────────────────────────────────────
const showEditDialog = ref(false);
const editingSprint = ref<Sprint | null>(null);

const openEditDialog = (sprint: Sprint) => {
    editingSprint.value = sprint;
    showEditDialog.value = true;
};

const saveEditSprint = (form: object) => {
    router.put(r('update', editingSprint.value!.id), form, {
        ...opts,
        onSuccess: () => {
            showEditDialog.value = false;
        },
    });
};

// ─── Complete Sprint ──────────────────────────────────────────────
const showCompleteDialog = ref(false);
const completingSprint = ref<Sprint | null>(null);

const openCompleteDialog = (sprint: Sprint) => {
    completingSprint.value = sprint;
    showCompleteDialog.value = true;
};

const saveCompleteSprint = (form: object) => {
    router.patch(r('complete', completingSprint.value!.id), form, {
        ...opts,
        onSuccess: () => {
            showCompleteDialog.value = false;
        },
    });
};

// ─── Delete Sprint ────────────────────────────────────────────────
const deleteSprint = (sprint: Sprint) => {
    if (!confirm(`Delete sprint "${sprint.name}"? Tasks will move to backlog.`)) return;
    router.delete(r('destroy', sprint.id), opts);
};

// ─── Drag & Drop ─────────────────────────────────────────────────
// taskMoved: (taskId, fromSprintId | null, toSprintId | null, newIndex)
// null = backlog
const onTaskMoved = (taskId: string, fromSprintId: string | null, toSprintId: string | null) => {
    if (toSprintId) {
        // Moved INTO a sprint
        router.post(r('tasks.assign', toSprintId), { task_ids: [taskId] }, opts);
    } else {
        // Moved OUT to backlog
        router.delete(
            route('project.sprints.tasks.remove', {
                projectEncoded: props.projectId,
                sprintEncoded: fromSprintId,
                taskEncoded: taskId,
            }),
            opts,
        );
    }
};

// ─── Context Menus ────────────────────────────────────────────────
const sprintMenu = ref();
const taskMenu = ref();
const activeSprintForMenu = ref<Sprint | null>(null);
const activeTaskCtx = ref<{ task: Task; sprintId: string | null } | null>(null);

const sprintMenuItems = computed(() => {
    const s = activeSprintForMenu.value;
    if (!s) return [];
    return [
        { label: 'Edit Sprint', icon: 'pi pi-pencil', command: () => openEditDialog(s) },
        ...(s.status?.name === 'Active' ? [{ label: 'Complete Sprint', icon: 'pi pi-flag', command: () => openCompleteDialog(s) }] : []),
        { separator: true },
        { label: 'Delete Sprint', icon: 'pi pi-trash', command: () => deleteSprint(s) },
    ];
});

const taskMenuItems = computed(() => {
    const ctx = activeTaskCtx.value;
    if (!ctx) return [];
    return [
        { label: 'Edit', icon: 'pi pi-pencil', command: () => emit('edit', ctx.task, null) },
        { separator: true },
        ...(ctx.sprintId
            ? [{ label: 'Move to Backlog', icon: 'pi pi-arrow-down', command: () => onTaskMoved(ctx.task.id, ctx.sprintId, null) }]
            : []),
        ...props.sprints
            .filter((s) => s.id !== ctx.sprintId)
            .map((s) => ({ label: `Move to ${s.name}`, icon: 'pi pi-arrow-right', command: () => onTaskMoved(ctx.task.id, ctx.sprintId, s.id) })),
    ];
});

const onSprintMenu = (event: MouseEvent, sprint: Sprint) => {
    activeSprintForMenu.value = sprint;
    sprintMenu.value.toggle(event);
};
const onTaskMenu = (event: MouseEvent, task: Task, sprintId: string | null) => {
    activeTaskCtx.value = { task, sprintId };
    taskMenu.value.toggle(event);
};
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Toolbar -->
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-surface-700 dark:text-surface-200">Backlog</span>
            <div class="flex gap-2">
                <Button v-if="canAct" label="Create Sprint" icon="pi pi-plus" size="small" outlined @click="createSprint" />
                <Button v-if="canAct" label="Create Issue" icon="pi pi-plus" size="small" @click="emit('add', null)" />
            </div>
        </div>

        <!-- Epic Panel filter -->
        <EpicPanel :epics="epics" :selectedEpicId="selectedEpicId" @select="selectedEpicId = $event" />

        <!-- Sprints -->
        <SprintSection
            v-for="sprint in sprints"
            :key="sprint.id"
            :sprint="sprint"
            :canAct="canAct"
            :filteredTasks="filteredSprintTasks(sprint)"
            @edit="(task) => emit('edit', task, null)"
            @taskMenu="(ev, task) => onTaskMenu(ev, task, sprint.id)"
            @sprintMenu="(ev, s) => onSprintMenu(ev, s)"
            @start="openStartDialog"
            @complete="openCompleteDialog"
            @addIssue="emit('add', null)"
            @taskMoved="onTaskMoved"
        />

        <!-- Backlog -->
        <BacklogSection
            :tasks="backlog"
            :filteredTasks="filteredBacklog"
            :canAct="canAct"
            @edit="(task) => emit('edit', task, null)"
            @taskMenu="(ev, task) => onTaskMenu(ev, task, null)"
            @addIssue="emit('add', null)"
            @taskMoved="onTaskMoved"
        />
    </div>

    <StartSprintDialog v-model:visible="showStartDialog" :sprint="startingSprint" @save="saveStartSprint" />
    <EditSprintDialog v-model:visible="showEditDialog" :sprint="editingSprint" @save="saveEditSprint" />
    <CompleteSprintDialog v-model:visible="showCompleteDialog" :sprint="completingSprint" :sprints="sprints" @save="saveCompleteSprint" />

    <Menu ref="sprintMenu" :model="sprintMenuItems" popup />
    <Menu ref="taskMenu" :model="taskMenuItems" popup />
</template>
