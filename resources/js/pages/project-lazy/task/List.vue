<script setup lang="ts">
import type { ListProps, ListTask, SavedTaskPayload } from '@/pages/project-lazy';
import { Deferred, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import ListTableSkeleton from './partials/ListTableSkeleton.vue';
import TaskFormDrawer from '../partials/TaskFormDrawer.vue';
import TaskTable from './TaskTable.vue';
import { useLocalTaskTree } from '../composables/useLocalTaskTree';
import { buildListTaskNode, patchListTaskNode } from '../utils/listTaskNode';

const props = defineProps<ListProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const localProgress = ref(props.project.progress);

const { tasks, applySaved } = useLocalTaskTree<ListTask>(
    computed(() => props.tasks),
    {
        onProjectProgress: (value) => {
            localProgress.value = value;
        },
    },
);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: ListTask) => drawer.value?.openEdit(task);
const onSaved = (payload: SavedTaskPayload) => applySaved(payload, { build: buildListTaskNode, patch: patchListTaskNode });
</script>

<template>
    <Head :title="`List - ${props.project.title}`" />

    <ProjectShellLayout :liveProgress="localProgress">
        <Deferred :data="['tasks', 'assignableUsers']">
            <template #fallback>
                <ListTableSkeleton />
            </template>

            <TaskTable
                :projectId="props.project.id"
                :tasks="tasks"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :taskTypes="props.taskTypes"
                :taskCategories="props.taskCategories"
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
