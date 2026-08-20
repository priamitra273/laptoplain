<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../types';
import TaskPriorityForm from './Form.vue';
import TaskPriorityTable from './Table.vue';

interface Props {
    task_priorities?: MasterDataItem[];
}

withDefaults(defineProps<Props>(), {
    task_priorities: () => [],
});

const overlay = useOverlay();

const priorityForm = overlay.create(TaskPriorityForm);

const addPriority = () => {
    priorityForm.open();
};

const editPriority = (priority: MasterDataItem) => {
    priorityForm.open({ value: priority });
};
</script>

<template>
    <Head title="Task Priority" />

    <AppLayout title="Task Priority">
        <Heading title="Task Priority" description="Manage master data task priority">
            <UButton v-if="can('task-priority.create')" size="sm" @click="addPriority">Add Task Priority</UButton>
        </Heading>

        <TaskPriorityTable :data="task_priorities" @edit="editPriority" />
    </AppLayout>
</template>
