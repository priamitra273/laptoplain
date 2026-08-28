<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { fetchJson } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router, useHttp } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import TaskFormDrawer from '../kanban/TaskFormDrawer.vue';
import type { KanbanBadge, KanbanStatusOption, KanbanTask, KanbanUser } from '../kanban/types';
import BacklogSection from './BacklogSection.vue';
import CompleteSprintDialog from './CompleteSprintDialog.vue';
import EditSprintDialog from './EditSprintDialog.vue';
import SprintSection from './SprintSection.vue';
import StartSprintDialog from './StartSprintDialog.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

interface Props {
    projectId: string;
    sprints: BacklogSprint[];
    backlogTasks: BacklogTask[];
    epics: BacklogEpic[];
    statuses: KanbanStatusOption[];
    priorities: KanbanBadge[];
    types: KanbanBadge[];
    categories: KanbanBadge[];
    tags: KanbanBadge[];
    assignableUsers: KanbanUser[];
}

const props = defineProps<Props>();

const toast = useToast();
const confirm = useConfirmDialog();
const overlay = useOverlay();

const policy = inject(ProjectPolicyKey, null);
const { canAction } = useProjectPermissions(policy);
const canTaskCreate = computed(() => canAction('task', 'create'));
const canSprintCreate = computed(() => canAction('sprint', 'create'));
const canSprintUpdate = computed(() => canAction('sprint', 'update'));
const canSprintDelete = computed(() => canAction('sprint', 'delete'));
const canAct = computed(() => canTaskCreate.value || canSprintCreate.value);

const taskForm = overlay.create(TaskFormDrawer);
const startDialog = overlay.create(StartSprintDialog);
const editDialog = overlay.create(EditSprintDialog);
const completeDialog = overlay.create(CompleteSprintDialog);

const creatingSprint = ref(false);

const reload = () => router.reload({ only: ['sprints', 'backlog', 'epics'] });

const createSprint = async () => {
    if (!canSprintCreate.value) return;

    creatingSprint.value = true;
    try {
        await fetchJson(route('project.sprints.store', { projectEncoded: props.projectId }), 'POST');
        toast.add({ title: 'Success', description: 'Sprint created successfully', color: 'success' });
        reload();
    } catch {
        toast.add({ title: 'Failed', description: 'Could not create sprint.', color: 'error' });
    } finally {
        creatingSprint.value = false;
    }
};

const openStartDialog = async (sprint: BacklogSprint) => {
    const saved = await startDialog.open({ sprint, projectId: props.projectId });
    if (saved) reload();
};

const openEditDialog = async (sprint: BacklogSprint) => {
    const saved = await editDialog.open({ sprint, projectId: props.projectId });
    if (saved) reload();
};

const openCompleteDialog = async (sprint: BacklogSprint) => {
    const saved = await completeDialog.open({ sprint, sprints: props.sprints, projectId: props.projectId });
    if (saved) reload();
};

const deleteSprint = async (sprint: BacklogSprint) => {
    const confirmed = await confirm({
        title: 'Delete Sprint',
        description: `Delete "${sprint.name}"? Tasks in this sprint will move back to backlog.`,
    });
    if (!confirmed) return;

    try {
        await fetchJson(route('project.sprints.destroy', { projectEncoded: props.projectId, sprintEncoded: sprint.id }), 'DELETE');
        toast.add({ title: 'Success', description: 'Sprint deleted successfully', color: 'success' });
        reload();
    } catch {
        toast.add({ title: 'Failed', description: 'Could not delete sprint.', color: 'error' });
    }
};

const persistMove = async (taskId: string, fromSprintId: string | null, toSprintId: string | null) => {
    try {
        if (toSprintId) {
            await fetchJson(route('project.sprints.tasks.assign', { projectEncoded: props.projectId, sprintEncoded: toSprintId }), 'POST', {
                task_ids: [taskId],
            });
        } else if (fromSprintId) {
            await fetchJson(
                route('project.sprints.tasks.remove', { projectEncoded: props.projectId, sprintEncoded: fromSprintId, taskEncoded: taskId }),
                'DELETE',
            );
        }
    } catch {
        toast.add({ title: 'Failed', description: 'Could not move task.', color: 'error' });
    } finally {
        reload();
    }
};

/**
 * Endpoint assign menerima banyak id sekaligus, jadi memindahkan sepuluh task
 * tetap satu request — bukan sepuluh.
 */
const bulkMove = async (taskIds: string[], toSprintId: string) => {
    if (!taskIds.length) return;

    try {
        await fetchJson(route('project.sprints.tasks.assign', { projectEncoded: props.projectId, sprintEncoded: toSprintId }), 'POST', {
            task_ids: taskIds,
        });

        const sprintName = props.sprints.find((sprint) => sprint.id === toSprintId)?.name ?? 'the sprint';
        toast.add({
            title: 'Moved',
            description: `${taskIds.length} ${taskIds.length === 1 ? 'task' : 'tasks'} moved to ${sprintName}.`,
            color: 'success',
        });
    } catch {
        toast.add({ title: 'Failed', description: 'Could not move the selected tasks.', color: 'error' });
    } finally {
        reload();
    }
};

const assignEpic = async (task: BacklogTask, epicId: string | null) => {
    try {
        await fetchJson(route('project.tasks.parent.update', { projectEncoded: props.projectId, task: task.id }), 'PUT', {
            parent_id: epicId,
        });
        toast.add({ title: 'Success', description: epicId ? 'Task moved to epic' : 'Task removed from epic', color: 'success' });
    } catch {
        toast.add({ title: 'Failed', description: 'Could not update the epic.', color: 'error' });
    } finally {
        reload();
    }
};

const priorityHttp = useHttp<{ priority_id: string }>({ priority_id: '' });
const updatePriority = (task: BacklogTask, priorityId: string) => {
    priorityHttp.priority_id = priorityId;
    priorityHttp.put(route('project.tasks.priority.update', { projectEncoded: props.projectId, task: task.id }), {
        onError: () => toast.add({ title: 'Failed', description: 'Could not update priority.', color: 'error' }),
        onFinish: () => reload(),
    });
};

const toKanbanTaskShape = (task: BacklogTask): KanbanTask => ({
    id: task.id,
    parent_id: task.parent_id,
    title: task.title,
    description: null,
    start_date: null,
    due_date: null,
    progress: 0,
    is_overdue: false,
    status: task.status,
    priority: task.priority,
    type: null,
    category: task.category,
    users: task.users,
    tags: [],
    sub_task_recursive: [],
});

const openCreateTask = async (sprintId: string | null) => {
    if (!canTaskCreate.value) return;

    const saved = await taskForm.open({
        projectId: props.projectId,
        sprintId,
        excludeEpicCategory: true,
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reload();
};

const openEditTask = async (task: BacklogTask) => {
    const saved = await taskForm.open({
        task: toKanbanTaskShape(task),
        projectId: props.projectId,
        excludeEpicCategory: true,
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reload();
};

const openCreateEpic = async () => {
    if (!canTaskCreate.value) return;

    const saved = await taskForm.open({
        projectId: props.projectId,
        onlyEpicCategory: true,
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reload();
};
</script>

<template>
    <div
        v-if="!sprints.length && !backlogTasks.length"
        class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-default px-6 py-14 text-center"
    >
        <UIcon name="i-lucide-layers" class="size-8 text-muted" />
        <div class="space-y-1.5">
            <p class="text-base font-semibold text-highlighted">Nothing to plan yet</p>
            <p class="mx-auto max-w-lg text-sm leading-relaxed text-muted">
                There are no upcoming batches and no unscheduled tasks. Create a batch to group what the team does next, or add tasks and leave them
                unscheduled until you are ready.
            </p>
        </div>
        <div v-if="canAct" class="mt-1 flex flex-wrap justify-center gap-2">
            <UButton label="Create a batch" :loading="creatingSprint" :disabled="!canSprintCreate" @click="createSprint" />
            <UButton label="Add a task" color="neutral" variant="outline" :disabled="!canTaskCreate" @click="openCreateTask(null)" />
        </div>
    </div>

    <div v-else class="flex flex-col gap-3">
        <SprintSection
            v-for="sprint in sprints"
            :key="sprint.id"
            :sprint="sprint"
            :all-sprints="sprints"
            :epics="epics"
            :priorities="priorities"
            :can-act="canAct"
            :can-sprint-update="canSprintUpdate"
            :can-sprint-delete="canSprintDelete"
            @edit-sprint="openEditDialog"
            @start="openStartDialog"
            @complete="openCompleteDialog"
            @delete-sprint="deleteSprint"
            @add-issue="openCreateTask"
            @task-moved="persistMove"
            @edit-task="openEditTask"
            @update-priority="updatePriority"
            @move-task="(task, fromId, toId) => persistMove(task.id, fromId, toId)"
            @assign-epic="assignEpic"
            @create-epic="openCreateEpic"
        />

        <BacklogSection
            :tasks="backlogTasks"
            :sprints="sprints"
            :epics="epics"
            :priorities="priorities"
            :can-act="canAct"
            :creating-sprint="creatingSprint"
            @add-issue="() => openCreateTask(null)"
            @create-sprint="createSprint"
            @task-moved="persistMove"
            @edit-task="openEditTask"
            @update-priority="updatePriority"
            @move-task="(task, fromId, toId) => persistMove(task.id, fromId, toId)"
            @assign-epic="assignEpic"
            @create-epic="openCreateEpic"
            @bulk-move="bulkMove"
        />
    </div>
</template>
