<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { ProjectPolicyKey } from '@/types/type';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import Swal from 'sweetalert2';
import { computed, inject, provide, ref, watch } from 'vue';
import type { Epic, Sprint, Task, TaskCategory, TaskPriority, TaskStatus, TaskType, User } from '..';
import BacklogSection from './partials/BacklogSection.vue';
import CompleteSprintDialog from './partials/CompleteSprintDialog.vue';
import EditSprintDialog from './partials/EditSprintDialog.vue';
import SprintSection from './partials/SprintSection.vue';
import StartSprintDialog from './partials/StartSprintDialog.vue';
import { BacklogKey } from './types';

const props = defineProps<{
    projectId: string;
    sprints: Sprint[];
    backlog: Task[];
    epics: Epic[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    taskCategories: TaskCategory[];
    assignableUsers: User[];
}>();

const emit = defineEmits(['add', 'addBacklog', 'edit', 'activeSprintTaskIds']);

const policy = inject(ProjectPolicyKey, null);

const toast = useToast();

const { canAction } = useProjectPermissions(policy);

const canTaskCreate = computed(() => canAction('task', 'create'));
const canSprintCreate = computed(() => canAction('sprint', 'create'));
const canSprintUpdate = computed(() => canAction('sprint', 'update'));
const canSprintDelete = computed(() => canAction('sprint', 'delete'));
const canAct = computed(() => canTaskCreate.value || canSprintCreate.value);

const localSprints = ref<Sprint[]>([...props.sprints]);
const localBacklog = ref<Task[]>([...props.backlog]);
const selectedTaskIds = ref<string[]>([]);

// ─── Helpers ──────────────────────────────────────────────────────
const r = (name: string, sprintId?: string) =>
    route(`project.sprints.${name}`, {
        projectEncoded: props.projectId,
        ...(sprintId ? { sprintEncoded: sprintId } : {}),
    });

const notify = (severity: 'success' | 'error', summary: string) => toast.add({ severity, summary, life: 2500 });

const syncBoardData = (payload: { sprints?: Sprint[]; backlog?: Task[] }) => {
    localSprints.value = payload.sprints ?? [];
    localBacklog.value = payload.backlog ?? [];
};

const refreshBoardData = async () => {
    const { data } = await axios.get(r('index'));
    syncBoardData(data ?? {});
};

const getErrorMessage = (error: any, fallback = 'Something went wrong') => {
    const message = error?.response?.data?.message;
    const errors = error?.response?.data?.errors;
    if (typeof message === 'string' && message.length > 0) return message;
    if (errors && typeof errors === 'object') {
        const first = Object.values(errors)[0];
        if (Array.isArray(first) && first[0]) return first[0];
        if (typeof first === 'string') return first;
    }
    return fallback;
};

const activeSprintTaskIds = computed(() => {
    return localSprints.value
        .filter((s) => s.status?.name === 'Active' && s?.tasks?.length)
        .flatMap((sprint) => (sprint.tasks ?? []).filter((task) => task.category?.name?.toLowerCase() !== 'epic').map((task) => String(task.id)));
});

watch(activeSprintTaskIds, (ids) => emit('activeSprintTaskIds', ids), { immediate: true });

watch(
    () => props.sprints,
    (sprints) => {
        localSprints.value = [...sprints];
    },
    { deep: true },
);
watch(
    () => props.backlog,
    (backlog) => {
        localBacklog.value = [...backlog];
    },
    { deep: true },
);

watch(
    () => [localSprints.value, localBacklog.value],
    () => {
        const visibleSet = new Set(visibleTaskIds.value);
        selectedTaskIds.value = selectedTaskIds.value.filter((id) => visibleSet.has(id));
    },
    { deep: true },
);

const filteredBacklog = computed(() => {
    return localBacklog.value.filter((task) => task.category?.name?.toLowerCase() !== 'epic');
});

const visibleTaskIds = computed(() => {
    const sprintIds = localSprints.value.flatMap((sprint) => (sprint.tasks ?? []).map((task) => String(task.id)));
    const backlogIds = filteredBacklog.value.map((task) => String(task.id));
    return Array.from(new Set([...sprintIds, ...backlogIds]));
});

const selectedCount = computed(() => selectedTaskIds.value.length);
const isAllSelected = computed(() => visibleTaskIds.value.length > 0 && visibleTaskIds.value.every((id) => selectedTaskIds.value.includes(id)));

// ─── Actions ──────────────────────────────────────────────────────
const createSprint = async () => {
    if (!canSprintCreate.value) return;
    try {
        const { data } = await axios.post(r('store'), { name: `Sprint ${localSprints.value.length + 1}` });
        if (data?.success === false) throw new Error(data?.message || 'Failed to create sprint');
        if (data?.sprint) localSprints.value = [...localSprints.value, data.sprint].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
        refreshBoardData().catch(() => null);
        notify('success', data?.message || 'Sprint created successfully');
    } catch (error) {
        notify('error', getErrorMessage(error, 'Failed to create sprint'));
    }
};

const showStartDialog = ref(false);
const startingSprint = ref<Sprint | null>(null);
const openStartDialog = (sprint: Sprint) => {
    startingSprint.value = sprint;
    showStartDialog.value = true;
};

const showEditDialog = ref(false);
const editingSprint = ref<Sprint | null>(null);
const openEditDialog = (sprint: Sprint) => {
    editingSprint.value = sprint;
    showEditDialog.value = true;
};

const showCompleteDialog = ref(false);
const completingSprint = ref<Sprint | null>(null);
const openCompleteDialog = (sprint: Sprint) => {
    completingSprint.value = sprint;
    showCompleteDialog.value = true;
};

const deleteSprint = (sprint: Sprint) => {
    Swal.fire({
        icon: 'warning',
        title: `Delete "${sprint.name}"?`,
        text: 'Tasks in this sprint will move back to backlog.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300' },
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            await axios.delete(r('destroy', sprint.id));
            localSprints.value = localSprints.value.filter((s) => s.id !== sprint.id);
            await refreshBoardData();
        } catch (error) {
            await Swal.fire('Failed', getErrorMessage(error, 'Failed to delete sprint'), 'error');
        }
    });
};

const onTaskMoved = async (taskId: string, fromSprintId: string | null, toSprintId: string | null) => {
    const taskFromBacklog = localBacklog.value.find((task) => String(task.id) === taskId) ?? null;
    const taskFromSprints = localSprints.value.flatMap((sprint) => sprint.tasks ?? []).find((task) => String(task.id) === taskId) ?? null;
    const movingTask = taskFromBacklog ?? taskFromSprints;

    if (toSprintId) {
        localBacklog.value = localBacklog.value.filter((task) => String(task.id) !== taskId);
        localSprints.value = localSprints.value.map((sprint) => ({
            ...sprint,
            tasks: (sprint.tasks ?? []).filter((task) => String(task.id) !== taskId),
        }));
        if (movingTask) {
            localSprints.value = localSprints.value.map((sprint) =>
                sprint.id === toSprintId
                    ? {
                          ...sprint,
                          tasks: [...(sprint.tasks ?? []), movingTask].filter(
                              (task, index, arr) => arr.findIndex((candidate) => String(candidate.id) === String(task.id)) === index,
                          ),
                      }
                    : sprint,
            );
        }

        try {
            await axios.post(r('tasks.assign', toSprintId), { task_ids: [taskId] });
            refreshBoardData().catch(() => null);
        } catch (error) {
            await refreshBoardData();
            notify('error', getErrorMessage(error, 'Failed to move task'));
        }
        return;
    }

    localSprints.value = localSprints.value.map((sprint) => ({
        ...sprint,
        tasks: (sprint.tasks ?? []).filter((task) => String(task.id) !== taskId),
    }));
    if (movingTask && !localBacklog.value.some((task) => String(task.id) === taskId)) {
        localBacklog.value = [...localBacklog.value, movingTask];
    }

    try {
        await axios.delete(
            route('project.sprints.tasks.remove', {
                projectEncoded: props.projectId,
                sprintEncoded: fromSprintId,
                taskEncoded: taskId,
            }),
        );
        refreshBoardData().catch(() => null);
    } catch (error) {
        await refreshBoardData();
        notify('error', getErrorMessage(error, 'Failed to move task'));
    }
};

const sprintMenu = ref();
const taskMenu = ref();
const activeSprintForMenu = ref<Sprint | null>(null);
const activeTaskCtx = ref<{ task: Task; sprintId: string | null } | null>(null);

const sprintMenuItems = computed(() => {
    const s = activeSprintForMenu.value;
    if (!s) return [];
    return [
        { label: 'Edit Sprint', icon: 'pi pi-pencil', command: () => openEditDialog(s), disabled: !canSprintUpdate.value },
        ...(s.status?.name === 'Active'
            ? [{ label: 'Complete Sprint', icon: 'pi pi-flag', command: () => openCompleteDialog(s), disabled: !canSprintUpdate.value }]
            : []),
        { separator: true },
        { label: 'Delete Sprint', icon: 'pi pi-trash', command: () => deleteSprint(s), disabled: !canSprintDelete.value },
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
        ...localSprints.value
            .filter((s) => s.id !== ctx.sprintId)
            .map((s) => ({ label: `Move to ${s.name}`, icon: 'pi pi-arrow-right', command: () => onTaskMoved(ctx.task.id, ctx.sprintId, s.id) })),
    ];
});

const onSprintMenu = (event: MouseEvent, sprint: Sprint) => {
    activeSprintForMenu.value = sprint;
    sprintMenu.value.toggle(event);
};

const assignTaskToEpic = async (task: any, epicId: string | null) => {
    try {
        await axios.put(route('project.tasks.parent.update', { projectEncoded: props.projectId, task: String(task.id) }), { parent_id: epicId });
        await refreshBoardData();
        router.reload({ only: ['tasks'] });
        notify('success', 'Task updated successfully');
    } catch (error) {
        notify('error', getErrorMessage(error, 'Failed to update task'));
    }
};

const updatePriority = async (task: any, priorityId: string) => {
    try {
        await axios.put(route('project.tasks.priority.update', { projectEncoded: props.projectId, task: String(task.id) }), {
            priority_id: priorityId,
        });
        const priority = props.taskPriorities.find((p) => p.id === priorityId);
        if (priority) {
            patchTaskInCollections(task.id, { priority });
        }
        refreshBoardData().catch(() => null);
        router.reload({ only: ['tasks', 'sprints', 'backlog'] });
    } catch (error) {
        await refreshBoardData();
        notify('error', getErrorMessage(error, 'Failed to update priority'));
    }
};

const patchTaskInCollections = (taskId: string | number, patch: Partial<Task>) => {
    const id = String(taskId);
    localBacklog.value = localBacklog.value.map((t) => (String(t.id) === id ? { ...t, ...patch } : t));
    localSprints.value = localSprints.value.map((s) => ({
        ...s,
        tasks: (s.tasks ?? []).map((t) => (String(t.id) === id ? { ...t, ...patch } : t)),
    }));
};

const toggleTaskSelection = (task: any, checked: boolean) => {
    const id = String(task.id);
    if (checked) {
        if (!selectedTaskIds.value.includes(id)) selectedTaskIds.value = [...selectedTaskIds.value, id];
        return;
    }
    selectedTaskIds.value = selectedTaskIds.value.filter((taskId) => taskId !== id);
};

const toggleSelectAll = () => {
    selectedTaskIds.value = isAllSelected.value ? [] : [...visibleTaskIds.value];
};
const clearSelection = () => {
    selectedTaskIds.value = [];
};
const viewEpic = (epicId: string) => router.get(route('task.show', epicId));

const openCreateTask = (sprintId: string | null = null, parentTaskId: string | null = null) => {
    if (sprintId) {
        emit('add', parentTaskId, undefined, 'sprint', sprintId);
        return;
    }
    if (parentTaskId) {
        emit('add', parentTaskId, undefined, 'backlog');
        return;
    }
    emit('addBacklog');
};

// ─── Provide Context ──────────────────────────────────────────────
provide(BacklogKey, {
    projectId: props.projectId,
    epics: props.epics,
    taskPriorities: props.taskPriorities,
    taskStatuses: props.taskStatuses,
    canAct: canAct.value,
    canSprintCreate: canSprintCreate.value,
    canSprintUpdate: canSprintUpdate.value,
    canSprintDelete: canSprintDelete.value,
    editTask: (task) => emit('edit', task, null),
    addEpic: assignTaskToEpic,
    updatePriority,
    viewEpic,
    toggleSelect: toggleTaskSelection,
    openTaskMenu: (event, task, sprintId) => {
        activeTaskCtx.value = { task, sprintId };
        taskMenu.value.toggle(event);
    },
    addTask: (task, sprintId) => openCreateTask(sprintId ?? null, task?.id ? String(task.id) : null),
    addParent: (epicId, sprintId) => emit('add', epicId, undefined, sprintId ? 'sprint-add-parent' : 'backlog-add-parent', sprintId),
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Sprints -->
        <SprintSection
            v-for="sprint in localSprints"
            :key="sprint.id"
            :sprint="sprint"
            :selectedIds="selectedTaskIds"
            @sprintMenu="onSprintMenu"
            @start="openStartDialog"
            @complete="openCompleteDialog"
            @addIssue="openCreateTask"
            @taskMoved="onTaskMoved"
            @toggleSelectAll="
                (taskIds, checked) => {
                    const next = new Set(selectedTaskIds);
                    taskIds.forEach((id) => (checked ? next.add(id) : next.delete(id)));
                    selectedTaskIds = Array.from(next);
                }
            "
        />

        <!-- Backlog -->
        <BacklogSection
            :tasks="filteredBacklog"
            :selectedIds="selectedTaskIds"
            @addIssue="openCreateTask"
            @createSprint="createSprint"
            @taskMoved="onTaskMoved"
            @toggleSelectAll="
                (taskIds, checked) => {
                    const next = new Set(selectedTaskIds);
                    taskIds.forEach((id) => (checked ? next.add(id) : next.delete(id)));
                    selectedTaskIds = Array.from(next);
                }
            "
        />
    </div>

    <div
        v-if="selectedCount > 0"
        class="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center gap-2 rounded-md border border-surface-200 bg-white px-3 py-2 text-sm shadow-lg dark:border-surface-700 dark:bg-surface-900"
    >
        <span class="rounded bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-700 dark:bg-surface-800 dark:text-surface-200">
            {{ selectedCount }} selected
        </span>
        <Button :label="isAllSelected ? 'Unselect all' : 'Select all'" text size="small" class="!px-2" @click="toggleSelectAll" />
        <Button label="Clear" text size="small" class="!px-2" @click="clearSelection" />
    </div>

    <StartSprintDialog v-model:visible="showStartDialog" :sprint="startingSprint" :projectId="props.projectId" />
    <EditSprintDialog v-model:visible="showEditDialog" :sprint="editingSprint" :projectId="props.projectId" />
    <CompleteSprintDialog v-model:visible="showCompleteDialog" :sprint="completingSprint" :sprints="localSprints" :projectId="props.projectId" />

    <Menu ref="sprintMenu" :model="sprintMenuItems" popup />
    <Menu ref="taskMenu" :model="taskMenuItems" popup />
</template>
