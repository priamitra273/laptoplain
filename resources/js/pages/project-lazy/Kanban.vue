<script setup lang="ts">
import KanbanBoard from '@/pages/project/task/partials/TaskKanbanBoard.vue';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { KanbanProps } from './index';
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';
import KanbanBoardSkeleton from './partials/KanbanBoardSkeleton.vue';
import TaskFormDrawer from './task/TaskFormDrawer.vue';

const props = defineProps<KanbanProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: { id: string; parent_id?: string | null }) => drawer.value?.openEdit(task);
const onStatusUpdate = () => router.reload({ only: ['tasks'] });
</script>

<template>
    <Head :title="`Kanban - ${props.project.title}`" />

    <ProjectShellLayout>
        <Deferred :data="['taskStatuses', 'taskPriorities', 'taskTypes', 'taskCategories', 'tags', 'tasks', 'assignableUsers', 'epics']">
            <template #fallback>
                <KanbanBoardSkeleton />
            </template>

            <KanbanBoard
                :projectId="props.project.id"
                :tasks="props.tasks"
                :statuses="props.taskStatuses"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :taskTypes="props.taskTypes"
                :assignableUsers="props.assignableUsers"
                :epic-tasks="props.epics"
                @statusUpdate="onStatusUpdate"
                @add="openCreate"
                @edit="onEdit"
            />

            <TaskFormDrawer
                ref="drawer"
                :projectId="props.project.id"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :taskTypes="props.taskTypes"
                :taskCategories="props.taskCategories"
                :tags="props.tags"
                :assignableUsers="props.assignableUsers"
                @saved="onStatusUpdate"
            />
        </Deferred>
    </ProjectShellLayout>
</template>
