<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../types';
import TaskStatusForm from './Form.vue';
import TaskStatusTable from './Table.vue';

export interface TaskStatusItem extends MasterDataItem {
    score: number;
}

interface Props {
    task_statuses?: TaskStatusItem[];
}

withDefaults(defineProps<Props>(), {
    task_statuses: () => [],
});

const overlay = useOverlay();

const statusForm = overlay.create(TaskStatusForm);

const addStatus = () => {
    statusForm.open();
};

const editStatus = (status: TaskStatusItem) => {
    statusForm.open({ value: status });
};
</script>

<template>
    <Head title="Task Status" />

    <AppLayout title="Task Status">
        <Heading title="Task Status" description="Manage master data task status">
            <UButton v-if="can('task-status.create')" size="sm" @click="addStatus">Add Task Status</UButton>
        </Heading>

        <TaskStatusTable :data="task_statuses" @edit="editStatus" />
    </AppLayout>
</template>
