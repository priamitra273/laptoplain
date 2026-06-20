<script lang="ts">
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import TaskTable from '@/pages/project/task/Table.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { ListProps, ListTask } from './index';
import TaskFormDrawer from './task/TaskFormDrawer.vue';

const props = defineProps<ListProps>();

const drawer = ref<InstanceType<typeof TaskFormDrawer> | null>(null);

const openCreate = (parentId: string | null) => drawer.value?.openCreate(parentId ?? null);
const onEdit = (task: ListTask) => drawer.value?.openEdit(task);
const onSaved = () => router.reload({ only: ['tasks'] });
</script>

<template>
    <Head :title="`List - ${props.project.title}`" />

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
</template>
