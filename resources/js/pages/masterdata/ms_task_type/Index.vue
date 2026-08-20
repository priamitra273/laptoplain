<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../types';
import TaskTypeForm from './Form.vue';
import TaskTypeTable from './Table.vue';

interface Props {
    task_types?: MasterDataItem[];
}

withDefaults(defineProps<Props>(), {
    task_types: () => [],
});

const overlay = useOverlay();

const typeForm = overlay.create(TaskTypeForm);

const addType = () => {
    typeForm.open();
};

const editType = (type: MasterDataItem) => {
    typeForm.open({ value: type });
};
</script>

<template>
    <Head title="Task Type" />

    <AppLayout title="Task Type">
        <Heading title="Task Type" description="Manage master data task type">
            <UButton v-if="can('task-type.create')" size="sm" @click="addType">Add Task Type</UButton>
        </Heading>

        <TaskTypeTable :data="task_types" @edit="editType" />
    </AppLayout>
</template>
