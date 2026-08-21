<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import TaskFormDrawer from '../kanban/TaskFormDrawer.vue';
import type { KanbanBadge, KanbanTask, KanbanUser } from '../kanban/types';
import type { ShellProps } from '../types';
import TaskTable from './TaskTable.vue';
import type { ListTask, TaskCategoryOption } from './types';

interface TaskStatusOption extends KanbanBadge {
    score: number | null;
}

interface Props extends ShellProps {
    tasks?: ListTask[];
    taskStatuses?: TaskStatusOption[];
    taskPriorities?: KanbanBadge[];
    taskTypes?: KanbanBadge[];
    taskCategories?: TaskCategoryOption[];
    tags?: KanbanBadge[];
    assignableUsers?: KanbanUser[];
}

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    taskStatuses: () => [],
    taskPriorities: () => [],
    taskTypes: () => [],
    taskCategories: () => [],
    tags: () => [],
    assignableUsers: () => [],
});

const overlay = useOverlay();
const taskForm = overlay.create(TaskFormDrawer);

const reloadTasks = () => router.reload({ only: ['tasks', 'tags'] });

/** ListTask doesn't carry description/priority/tags — TaskFormDrawer re-fetches the full
 * record on open anyway, so these are just harmless placeholders for the instant pre-fill. */
const toKanbanTaskShape = (task: ListTask): KanbanTask => ({
    id: task.id,
    parent_id: task.parent_id,
    title: task.title,
    description: null,
    start_date: task.start_date,
    due_date: task.due_date,
    progress: task.progress,
    is_overdue: task.is_overdue,
    status: task.status,
    priority: null,
    type: task.type,
    category: task.category,
    users: task.users,
    tags: [],
    sub_task_recursive: task.sub_task_recursive.map(toKanbanTaskShape),
});

const openCreate = async (parentId: string | null) => {
    const saved = await taskForm.open({
        projectId: props.project.id,
        defaultParentId: parentId,
        statuses: props.taskStatuses,
        priorities: props.taskPriorities,
        types: props.taskTypes,
        categories: props.taskCategories,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reloadTasks();
};

const openEdit = async (task: ListTask) => {
    const saved = await taskForm.open({
        task: toKanbanTaskShape(task),
        projectId: props.project.id,
        statuses: props.taskStatuses,
        priorities: props.taskPriorities,
        types: props.taskTypes,
        categories: props.taskCategories,
        assignableUsers: props.assignableUsers,
        tags: props.tags,
    });

    if (saved) reloadTasks();
};
</script>

<template>
    <Head :title="`List - ${project.title}`" />

    <ProjectShellLayout>
        <Deferred :data="['tasks', 'assignableUsers']">
            <template #fallback>
                <div class="flex flex-col gap-4">
                    <USkeleton class="h-8 w-40" />
                    <USkeleton class="h-64 w-full rounded-lg" />
                </div>
            </template>

            <TaskTable
                :project-id="project.id"
                :tasks="tasks"
                :task-statuses="taskStatuses"
                :task-types="taskTypes"
                @add="openCreate"
                @edit="openEdit"
                @moved="reloadTasks"
                @deleted="reloadTasks"
            />
        </Deferred>
    </ProjectShellLayout>
</template>
