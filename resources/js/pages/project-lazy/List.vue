<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { ListProps, ListTask } from './index';
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';
import ListTableSkeleton from './partials/ListTableSkeleton.vue';
import TaskFormDrawer from './task/TaskFormDrawer.vue';
import TaskTable from './task/TaskTable.vue';

const props = defineProps<ListProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: ListTask) => drawer.value?.openEdit(task);
const onSaved = () => router.reload({ only: ['tasks'] });
</script>

<template>
    <Head :title="`List - ${props.project.title}`" />

    <ProjectShellLayout>
        <Deferred :data="['tasks', 'assignableUsers']">
            <template #fallback>
                <ListTableSkeleton />
            </template>

            <TaskTable
                :projectId="props.project.id"
                :tasks="props.tasks"
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
