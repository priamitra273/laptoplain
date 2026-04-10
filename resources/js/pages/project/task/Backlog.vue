<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import type { Task, TaskPriority, TaskStatus, TaskType } from '..';
import BacklogSection from './partials/BacklogSection.vue';
import CompleteSprintDialog from './partials/CompleteSprintDialog.vue';
import EditSprintDialog from './partials/EditSprintDialog.vue';
import SprintSection from './partials/SprintSection.vue';
import StartSprintDialog from './partials/StartSprintDialog.vue';
import type { Sprint, TaskCategory } from './type';

interface User {
    id: string;
    name: string;
    avatar_url?: string | null;
}
const props = defineProps<{
    projectId: string;
    sprints: Sprint[];
    backlog: Task[];
    epics: { id: string; title: string }[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    taskCategories: TaskCategory[];
    isMember: boolean;
    hasPermission: boolean;
    assignableUsers: User[];
}>();

const emit = defineEmits([
    'add',
    'addBacklog',
    'edit',
    'activeSprintTaskIds', // ← tambahkan ini
]);

const startLoading = ref(false);
const editLoading = ref(false);
const completeLoading = ref(false);

const toast = useToast();
const canAct = computed(() => props.isMember || props.hasPermission);
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

const opts = { preserveScroll: true, onError: (e: any) => notify('error', (Object.values(e)[0] as string) ?? 'Something went wrong') };

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
    const activeSprint = localSprints.value.find((s) => s.status?.name === 'Active');
    return (activeSprint?.tasks ?? []).filter((t) => t.category?.name?.toLowerCase() !== 'epic').map((t) => String(t.id));
});

watch(
    activeSprintTaskIds,
    (ids) => {
        emit('activeSprintTaskIds', ids);
    },
    { immediate: true },
);
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

// ─── Create Sprint ────────────────────────────────────────────────
const createSprint = async () => {
    try {
        const { data } = await axios.post(r('store'), { name: `Sprint ${localSprints.value.length + 1}` });

        if (data?.success === false) {
            throw new Error(data?.message || 'Failed to create sprint');
        }

        if (data?.sprint) {
            localSprints.value = [...localSprints.value, data.sprint].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
        }

        refreshBoardData().catch(() => null);
        notify('success', data?.message || 'Sprint created successfully');
    } catch (error) {
        notify('error', getErrorMessage(error, 'Failed to create sprint'));
    }
};

// ─── Start Sprint ─────────────────────────────────────────────────
const showStartDialog = ref(false);
const startingSprint = ref<Sprint | null>(null);

const openStartDialog = (sprint: Sprint) => {
    startingSprint.value = sprint;
    showStartDialog.value = true;
};

const saveStartSprint = (form: object) => {
    startLoading.value = true;
    router.patch(r('start', startingSprint.value!.id), form as any, {
        ...opts,
        onSuccess: () => {
            showStartDialog.value = false;
        },
        onFinish: () => {
            startLoading.value = false;
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
    editLoading.value = true;
    router.put(r('update', editingSprint.value!.id), form as any, {
        ...opts,
        onSuccess: () => {
            showEditDialog.value = false;
        },
        onFinish: () => {
            editLoading.value = false;
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
    completeLoading.value = true;
    router.patch(r('complete', completingSprint.value!.id), form as any, {
        ...opts,
        onSuccess: () => {
            showCompleteDialog.value = false;
        },
        onFinish: () => {
            completeLoading.value = false;
        },
    });
};

// ─── Delete Sprint ────────────────────────────────────────────────
const deleteSprint = (sprint: Sprint) => {
    Swal.fire({
        icon: 'warning',
        title: `Delete "${sprint.name}"?`,
        text: 'Tasks in this sprint will move back to backlog.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
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

// ─── Drag & Drop ─────────────────────────────────────────────────
// taskMoved: (taskId, fromSprintId | null, toSprintId | null, newIndex)
// null = backlog
const onTaskMoved = async (taskId: string, fromSprintId: string | null, toSprintId: string | null) => {
    const taskFromBacklog = localBacklog.value.find((task) => String(task.id) === taskId) ?? null;
    const taskFromSprints = localSprints.value.flatMap((sprint) => sprint.tasks ?? []).find((task) => String(task.id) === taskId) ?? null;
    const movingTask = taskFromBacklog ?? taskFromSprints;

    if (toSprintId) {
        // Optimistic: remove task from backlog and all sprint lists first.
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

    // Moved OUT to backlog
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
        ...localSprints.value
            .filter((s) => s.id !== ctx.sprintId)
            .map((s) => ({ label: `Move to ${s.name}`, icon: 'pi pi-arrow-right', command: () => onTaskMoved(ctx.task.id, ctx.sprintId, s.id) })),
    ];
});

const onSprintMenu = (event: MouseEvent, sprint: Sprint) => {
    activeSprintForMenu.value = sprint;
    sprintMenu.value.toggle(event);
};
const onTaskMenu = (event: MouseEvent, task: any, sprintId: string | null) => {
    activeTaskCtx.value = { task, sprintId };
    taskMenu.value.toggle(event);
};

const assignTaskToEpic = async (taskId: string | number, epicId: string | null) => {
    try {
        await axios.put(
            route('project.tasks.parent.update', {
                projectEncoded: props.projectId,
                task: String(taskId),
            }),
            { parent_id: epicId },
        );
        await refreshBoardData();
        router.reload({
            only: ['tasks'],
        });
        notify('success', 'Task updated successfully');
    } catch (error) {
        notify('error', getErrorMessage(error, 'Failed to update task'));
    }
};

const patchTaskInCollections = (taskId: string | number, patch: Partial<Task>) => {
    const id = String(taskId);
    localBacklog.value = localBacklog.value.map((task) => (String(task.id) === id ? { ...task, ...patch } : task));
    localSprints.value = localSprints.value.map((sprint) => ({
        ...sprint,
        tasks: (sprint.tasks ?? []).map((task) => (String(task.id) === id ? { ...task, ...patch } : task)),
    }));
};

const updateTaskInline = async (taskId: string | number, payload: { status_id?: string; priority_id?: string }) => {
    try {
        if (payload.priority_id) {
            await axios.put(
                route('project.tasks.priority.update', {
                    projectEncoded: props.projectId,
                    task: String(taskId),
                }),
                { priority_id: payload.priority_id },
            );
        } else {
            await axios.put(`/project/${props.projectId}/tasks/${String(taskId)}`, payload);
        }

        if (payload.status_id) {
            const status = props.taskStatuses.find((item) => item.id === payload.status_id);
            patchTaskInCollections(taskId, { status });
        }
        if (payload.priority_id) {
            const priority = props.taskPriorities.find((item) => item.id === payload.priority_id);
            patchTaskInCollections(taskId, { priority });
        }

        refreshBoardData().catch(() => null);
        router.reload({ only: ['tasks', 'sprints', 'backlog'] });
    } catch (error) {
        await refreshBoardData();
        notify('error', getErrorMessage(error, 'Failed to update task'));
    }
};

const toggleTaskSelection = (task: { id: string | number }, checked: boolean) => {
    const id = String(task.id);
    if (checked) {
        if (!selectedTaskIds.value.includes(id)) {
            selectedTaskIds.value = [...selectedTaskIds.value, id];
        }
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
const viewEpic = (epicId: string) => {
    router.get(route('task.show', epicId));
};
const toggleSectionSelection = (taskIds: string[], checked: boolean) => {
    const next = new Set(selectedTaskIds.value);
    taskIds.forEach((id) => {
        if (checked) next.add(id);
        else next.delete(id);
    });
    selectedTaskIds.value = Array.from(next);
};

const openCreateTask = (sprintId: string | MouseEvent | null = null, parentTaskId: string | null = null) => {
    if (typeof sprintId === 'string' && sprintId) {
        emit('add', parentTaskId, undefined, 'sprint', sprintId);
        return;
    }

    if (parentTaskId) {
        emit('add', parentTaskId, undefined, 'backlog');
        return;
    }

    emit('addBacklog');
};
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Sprints -->
        <SprintSection
            v-for="sprint in localSprints"
            :key="sprint.id"
            :sprint="sprint"
            :canAct="canAct"
            :epics="props.epics"
            :taskStatuses="props.taskStatuses"
            :taskPriorities="props.taskPriorities"
            :selectedIds="selectedTaskIds"
            @edit="(task) => emit('edit', task, null)"
            @taskMenu="(ev, task) => onTaskMenu(ev, task, sprint.id)"
            @sprintMenu="(ev, s) => onSprintMenu(ev, s)"
            @start="openStartDialog"
            @complete="openCompleteDialog"
            @add="(task, sprintId) => openCreateTask(sprintId, String(task.id))"
            @addParent="(epicId, sprintId) => emit('add', epicId, undefined, 'sprint-add-parent', sprintId)"
            @addEpic="(task, epicId) => assignTaskToEpic(task.id, epicId)"
            @updatePriority="(task, priorityId) => updateTaskInline(task.id, { priority_id: priorityId })"
            @viewEpic="viewEpic"
            @toggleSelect="(task, checked) => toggleTaskSelection(task, checked)"
            @toggleSelectAll="(taskIds, checked) => toggleSectionSelection(taskIds, checked)"
            @addIssue="(sprintId) => openCreateTask(sprintId)"
            @taskMoved="onTaskMoved"
        />

        <!-- Backlog -->
        <BacklogSection
            :tasks="filteredBacklog"
            :canAct="canAct"
            :epics="props.epics"
            :taskStatuses="props.taskStatuses"
            :taskPriorities="props.taskPriorities"
            :selectedIds="selectedTaskIds"
            @edit="(task) => emit('edit', task, null)"
            @add="(task) => openCreateTask(null, String(task.id))"
            @addParent="(epicId) => emit('add', epicId, undefined, 'backlog-add-parent')"
            @addEpic="(task, epicId) => assignTaskToEpic(task.id, epicId)"
            @updatePriority="(task, priorityId) => updateTaskInline(task.id, { priority_id: priorityId })"
            @viewEpic="viewEpic"
            @toggleSelect="(task, checked) => toggleTaskSelection(task, checked)"
            @toggleSelectAll="(taskIds, checked) => toggleSectionSelection(taskIds, checked)"
            @taskMenu="(ev, task) => onTaskMenu(ev, task, null)"
            @addIssue="openCreateTask"
            @createSprint="createSprint"
            @taskMoved="onTaskMoved"
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

    <StartSprintDialog
        v-model:visible="showStartDialog"
        :sprint="startingSprint"
        :loading="startLoading"
        :disabled="startLoading"
        @save="saveStartSprint"
    />
    <EditSprintDialog
        v-model:visible="showEditDialog"
        :sprint="editingSprint"
        :loading="editLoading"
        :disabled="editLoading"
        @save="saveEditSprint"
    />
    <CompleteSprintDialog
        v-model:visible="showCompleteDialog"
        :sprint="completingSprint"
        :sprints="localSprints"
        :loading="completeLoading"
        :disabled="completeLoading"
        @save="saveCompleteSprint"
    />

    <Menu ref="sprintMenu" :model="sprintMenuItems" popup />
    <Menu ref="taskMenu" :model="taskMenuItems" popup />
</template>
