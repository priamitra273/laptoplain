import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import Swal from 'sweetalert2';
import { computed, provide, ref, watch } from 'vue';
import type { BacklogProps, BacklogSprint, BacklogTask } from '@/pages/project-lazy';
import { BacklogKey } from '../partials/types';
import { useTaskSelection } from './useBacklogSelection';

type ConfigData = App.Data.ProjectRole.ConfigData;
type CreateMode = 'backlog' | 'sprint' | 'backlog-add-parent' | 'sprint-add-parent';

interface BacklogDrawer {
    openCreate: (options: { sprintId?: string | null; parentId?: string | null; mode?: CreateMode }) => void;
    openEdit: (task: { id: string; parent_id?: string | null }) => void;
}

const isEpic = (task: BacklogTask) => task.category?.name?.toLowerCase() === 'epic';

/**
 * Owns the backlog board: local mirror state, sprint CRUD, drag-and-drop moves, epic
 * & priority updates, context menus and the injected BacklogKey context. Mutations are
 * optimistic, then reconciled by re-fetching the deferred props via a partial reload.
 */
export function useBacklogBoard(props: BacklogProps, policy: ConfigData | null, drawer: BacklogDrawer) {
    const toast = useToast();
    const { canAction } = useProjectPermissions(policy);

    const canTaskCreate = computed(() => canAction('task', 'create'));
    const canSprintCreate = computed(() => canAction('sprint', 'create'));
    const canSprintUpdate = computed(() => canAction('sprint', 'update'));
    const canSprintDelete = computed(() => canAction('sprint', 'delete'));
    const canAct = computed(() => canTaskCreate.value || canSprintCreate.value);

    const localSprints = ref<BacklogSprint[]>([...(props.sprints ?? [])]);
    const localBacklog = ref<BacklogTask[]>([...(props.backlog ?? [])]);
    const loading = ref({ backlog: false });

    // ─── Helpers ──────────────────────────────────────────────────────
    const r = (name: string, sprintId?: string) =>
        route(`project.sprints.${name}`, {
            projectEncoded: props.project.id,
            ...(sprintId ? { sprintEncoded: sprintId } : {}),
        });

    const notify = (severity: 'success' | 'error', summary: string) => toast.add({ severity, summary, life: 2500 });

    /** Re-fetch the deferred board collections (no skeleton flash on subsequent reloads). */
    const refresh = () => router.reload({ only: ['sprints', 'backlog', 'epics'] });

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

    watch(
        () => props.sprints,
        (sprints) => {
            localSprints.value = [...(sprints ?? [])];
        },
        { deep: true },
    );
    watch(
        () => props.backlog,
        (backlog) => {
            localBacklog.value = [...(backlog ?? [])];
        },
        { deep: true },
    );

    // ─── Selection ────────────────────────────────────────────────────
    const visibleTaskIds = computed(() => {
        const sprintIds = localSprints.value.flatMap((sprint) => (sprint.tasks ?? []).filter((task) => !isEpic(task)).map((task) => String(task.id)));
        const backlogIds = localBacklog.value.filter((task) => !isEpic(task)).map((task) => String(task.id));
        return Array.from(new Set([...sprintIds, ...backlogIds]));
    });

    const selection = useTaskSelection(visibleTaskIds);
    watch(visibleTaskIds, () => selection.prune(), { deep: true });

    // ─── Sprint dialogs ───────────────────────────────────────────────
    const showStartDialog = ref(false);
    const startingSprint = ref<BacklogSprint | null>(null);
    const openStartDialog = (sprint: BacklogSprint) => {
        startingSprint.value = sprint;
        showStartDialog.value = true;
    };

    const showEditDialog = ref(false);
    const editingSprint = ref<BacklogSprint | null>(null);
    const openEditDialog = (sprint: BacklogSprint) => {
        editingSprint.value = sprint;
        showEditDialog.value = true;
    };

    const showCompleteDialog = ref(false);
    const completingSprint = ref<BacklogSprint | null>(null);
    const openCompleteDialog = (sprint: BacklogSprint) => {
        completingSprint.value = sprint;
        showCompleteDialog.value = true;
    };

    // ─── Sprint CRUD ──────────────────────────────────────────────────
    const createSprint = async () => {
        if (!canSprintCreate.value) return;

        try {
            loading.value.backlog = true;
            const { data } = await axios.post(r('store'));
            if (data?.success === false) throw new Error(data?.message || 'Failed to create sprint');
            if (data?.sprint) {
                localSprints.value = [...localSprints.value, data.sprint].sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
            }
            notify('success', data?.message || 'Sprint created successfully');
            refresh();
        } catch (error) {
            notify('error', getErrorMessage(error, 'Failed to create sprint'));
        } finally {
            loading.value.backlog = false;
        }
    };

    const deleteSprint = (sprint: BacklogSprint) => {
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
                refresh();
            } catch (error) {
                await Swal.fire('Failed', getErrorMessage(error, 'Failed to delete sprint'), 'error');
            }
        });
    };

    // ─── Task move (drag & drop / menu) ───────────────────────────────
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
                refresh();
            } catch (error) {
                refresh();
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
                    projectEncoded: props.project.id,
                    sprintEncoded: fromSprintId,
                    taskEncoded: taskId,
                }),
            );
            refresh();
        } catch (error) {
            refresh();
            notify('error', getErrorMessage(error, 'Failed to move task'));
        }
    };

    // ─── Task updates ─────────────────────────────────────────────────
    const patchTaskInCollections = (taskId: string | number, patch: Partial<BacklogTask>) => {
        const id = String(taskId);
        localBacklog.value = localBacklog.value.map((t) => (String(t.id) === id ? { ...t, ...patch } : t));
        localSprints.value = localSprints.value.map((s) => ({
            ...s,
            tasks: (s.tasks ?? []).map((t) => (String(t.id) === id ? { ...t, ...patch } : t)),
        }));
    };

    const assignTaskToEpic = async (task: BacklogTask, epicId: string | null) => {
        try {
            await axios.put(route('project.tasks.parent.update', { projectEncoded: props.project.id, task: String(task.id) }), { parent_id: epicId });
            refresh();
            notify('success', 'Task updated successfully');
        } catch (error) {
            notify('error', getErrorMessage(error, 'Failed to update task'));
        }
    };

    const updatePriority = async (task: BacklogTask, priorityId: string) => {
        try {
            await axios.put(route('project.tasks.priority.update', { projectEncoded: props.project.id, task: String(task.id) }), {
                priority_id: priorityId,
            });
            const priority = props.taskPriorities.find((p) => String(p.id) === String(priorityId));
            if (priority) patchTaskInCollections(task.id, { priority });
            refresh();
        } catch (error) {
            refresh();
            notify('error', getErrorMessage(error, 'Failed to update priority'));
        }
    };

    // ─── Context menus ────────────────────────────────────────────────
    const sprintMenu = ref();
    const taskMenu = ref();
    const activeSprintForMenu = ref<BacklogSprint | null>(null);
    const activeTaskCtx = ref<{ task: BacklogTask; sprintId: string | null } | null>(null);

    const sprintMenuItems = computed(() => {
        const sprint = activeSprintForMenu.value;
        if (!sprint) return [];
        return [
            { label: 'Edit Sprint', icon: 'pi pi-pencil', command: () => openEditDialog(sprint), disabled: !canSprintUpdate.value },
            ...(sprint.status?.name === 'Active'
                ? [{ label: 'Complete Sprint', icon: 'pi pi-flag', command: () => openCompleteDialog(sprint), disabled: !canSprintUpdate.value }]
                : []),
            { separator: true },
            { label: 'Delete Sprint', icon: 'pi pi-trash', command: () => deleteSprint(sprint), disabled: !canSprintDelete.value },
        ];
    });

    const taskMenuItems = computed(() => {
        const ctx = activeTaskCtx.value;
        if (!ctx) return [];
        return [
            { label: 'Edit', icon: 'pi pi-pencil', command: () => drawer.openEdit(ctx.task) },
            { separator: true },
            ...(ctx.sprintId
                ? [{ label: 'Move to Backlog', icon: 'pi pi-arrow-down', command: () => onTaskMoved(String(ctx.task.id), ctx.sprintId, null) }]
                : []),
            ...localSprints.value
                .filter((s) => s.id !== ctx.sprintId)
                .map((s) => ({
                    label: `Move to ${s.name}`,
                    icon: 'pi pi-arrow-right',
                    command: () => onTaskMoved(String(ctx.task.id), ctx.sprintId, s.id),
                })),
        ];
    });

    const onSprintMenu = (event: MouseEvent, sprint: BacklogSprint) => {
        activeSprintForMenu.value = sprint;
        sprintMenu.value.toggle(event);
    };

    // ─── Task form (create / edit) ────────────────────────────────────
    const viewEpic = (epicId: string) => router.get(route('task.show', epicId));

    const openCreateTask = (sprintId: string | null = null, parentTaskId: string | null = null) => {
        if (sprintId) {
            drawer.openCreate({ sprintId, parentId: parentTaskId, mode: 'sprint' });
            return;
        }
        drawer.openCreate({ parentId: parentTaskId, mode: 'backlog' });
    };

    // ─── Provide context ──────────────────────────────────────────────
    provide(BacklogKey, {
        projectId: props.project.id,
        get epics() {
            return props.epics;
        },
        get taskPriorities() {
            return props.taskPriorities;
        },
        get taskStatuses() {
            return props.taskStatuses;
        },
        get canAct() {
            return canAct.value;
        },
        get canSprintCreate() {
            return canSprintCreate.value;
        },
        get canSprintUpdate() {
            return canSprintUpdate.value;
        },
        get canSprintDelete() {
            return canSprintDelete.value;
        },
        editTask: (task) => drawer.openEdit(task),
        addEpic: assignTaskToEpic,
        updatePriority,
        viewEpic,
        toggleSelect: selection.toggleTask,
        openTaskMenu: (event, task, sprintId) => {
            activeTaskCtx.value = { task, sprintId };
            taskMenu.value.toggle(event);
        },
        addTask: (task, sprintId) => openCreateTask(sprintId ?? null, task?.id ? String(task.id) : null),
        addParent: (epicId, sprintId) =>
            drawer.openCreate({ parentId: epicId, sprintId: sprintId ?? null, mode: sprintId ? 'sprint-add-parent' : 'backlog-add-parent' }),
    });

    return {
        // state
        localSprints,
        localBacklog,
        loading,
        // selection
        selectedTaskIds: selection.selectedTaskIds,
        selectedCount: selection.selectedCount,
        isAllSelected: selection.isAllSelected,
        toggleSelectAll: selection.toggleAll,
        toggleMany: selection.toggleMany,
        clearSelection: selection.clear,
        // sprint dialogs
        showStartDialog,
        startingSprint,
        showEditDialog,
        editingSprint,
        showCompleteDialog,
        completingSprint,
        openStartDialog,
        openCompleteDialog,
        // actions
        createSprint,
        onTaskMoved,
        onSprintMenu,
        refresh,
        // menus
        sprintMenu,
        taskMenu,
        sprintMenuItems,
        taskMenuItems,
        // helpers exposed for the template
        openCreateTask,
    };
}
