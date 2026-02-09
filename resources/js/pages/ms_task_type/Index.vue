<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { TaskType } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import TaskTypeTable from './Table.vue';

interface Props {
    task_types?: TaskType[];
}

const props = withDefaults(defineProps<Props>(), {
    task_types: () => [],
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role === 'super-admin-admin' || role === 'admin-admin';
}
</script>

<template>
    <Head title="Task Type" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task Type" description="Manage master data task type" />
            <TaskTypeTable :task_types="props.task_types" :has-permission="hasPermission()" />
        </div>
        <Toast />
        <ConfirmDialog />
    </AppLayout>
</template>
