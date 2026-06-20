<script setup lang="ts">
import KanbanBoard from '@/pages/project/task/partials/TaskKanbanBoard.vue';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { KanbanCard, KanbanProps, SavedTaskPayload } from './index';
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';
import KanbanBoardSkeleton from './partials/KanbanBoardSkeleton.vue';
import TaskFormDrawer from './task/TaskFormDrawer.vue';
import { useLocalTaskTree } from './task/composables/useLocalTaskTree';
import { buildKanbanCardNode, patchKanbanCardNode } from './task/nodes/kanbanCardNode';

const props = defineProps<KanbanProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const { tasks, applySaved } = useLocalTaskTree<KanbanCard>(computed(() => props.tasks), {});

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: { id: string; parent_id?: string | null }) => drawer.value?.openEdit(task);
const onStatusUpdate = () => router.reload({ only: ['tasks'] });
const onSaved = (payload: SavedTaskPayload) => applySaved(payload, { build: buildKanbanCardNode, patch: patchKanbanCardNode });
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
                :tasks="tasks"
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
                @saved="onSaved"
            />
        </Deferred>
    </ProjectShellLayout>
</template>
