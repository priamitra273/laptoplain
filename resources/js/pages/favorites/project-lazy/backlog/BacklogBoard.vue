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

const priorityHttp = useHttp<{ priority_id: string }>({ priority_id: '' });
const updatePriority = (task: BacklogTask, priorityId: string) => {
    priorityHttp.priority_id = priorityId;
    priorityHttp.put(route('project.tasks.priority.update', { projectEncoded: props.projectId, task: task.id }), {
        onError: () => toast.add({ title: 'Failed', description: 'Could not update priority.', color: 'error' }),
        onFinish: () => reload(),
    });
};

/** BacklogTask doesn't carry description/dates/progress/tags — TaskFormDrawer re-fetches
 * the full record on open anyway, so these are just harmless placeholders for the pre-fill. */
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
    <div class="flex flex-col gap-3">
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
        />
    </div>
</template>
