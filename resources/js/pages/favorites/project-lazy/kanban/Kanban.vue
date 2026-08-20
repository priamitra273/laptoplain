<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import KanbanBoard from './KanbanBoard.vue';
import TaskDetailPanel from './TaskDetailPanel.vue';
import TaskFormDrawer from './TaskFormDrawer.vue';
import type { KanbanBadge, KanbanStatusOption, KanbanTask, KanbanUser } from './types';

interface Props extends ShellProps {
    activeSprintId: string | null;
    taskStatuses?: KanbanStatusOption[];
    taskPriorities?: KanbanBadge[];
    taskTypes?: KanbanBadge[];
    tasks?: KanbanTask[];
    assignableUsers?: KanbanUser[];
    tags?: KanbanBadge[];
}

const props = withDefaults(defineProps<Props>(), {
    taskStatuses: () => [],
    taskPriorities: () => [],
    taskTypes: () => [],
    tasks: () => [],
    assignableUsers: () => [],
    tags: () => [],
});

const overlay = useOverlay();
const taskForm = overlay.create(TaskFormDrawer);
const taskDetail = overlay.create(TaskDetailPanel);

const selectedTaskId = ref<string | null>(null);

const reloadTasks = () => router.reload({ only: ['tasks', 'tags'] });

const openCreate = async (statusId?: string) => {
    const saved = await taskForm.open({
        projectId: props.project.id,
        sprintId: props.activeSprintId,
        defaultStatusId: statusId,
        statuses: props.taskStatuses,
        priorities: props.taskPriorities,
        types: props.taskTypes,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reloadTasks();
};

const openEdit = async (task: KanbanTask) => {
    const saved = await taskForm.open({
        task,
        projectId: props.project.id,
        sprintId: props.activeSprintId,
        statuses: props.taskStatuses,
        priorities: props.taskPriorities,
        types: props.taskTypes,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reloadTasks();
};

const openDetail = async (task: KanbanTask) => {
    selectedTaskId.value = task.id;
    const result = await taskDetail.open({ task });
    selectedTaskId.value = null;
    if (result === 'edit') openEdit(task);
};
</script>

<template>
    <Head title="Kanban" />

    <ProjectShellLayout>
        <Deferred :data="['taskStatuses', 'taskPriorities', 'taskTypes', 'tasks', 'assignableUsers', 'tags']">
            <template #fallback>
                <div class="flex gap-3 overflow-x-auto py-2">
                    <USkeleton v-for="n in 4" :key="n" class="h-96 w-75 shrink-0 rounded-xl" />
                </div>
            </template>

            <KanbanBoard
                :project-id="project.id"
                :sprint-id="activeSprintId"
                :selected-task-id="selectedTaskId"
                :tasks="tasks"
                :statuses="taskStatuses"
                :priorities="taskPriorities"
                :types="taskTypes"
                :assignable-users="assignableUsers"
                @add="openCreate"
                @detail="openDetail"
                @edit="openEdit"
                @created="reloadTasks"
            />
        </Deferred>
    </ProjectShellLayout>
</template>
