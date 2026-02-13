<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { TaskStatus } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import TaskStatusTable from './Table.vue';

interface Props {
    task_statuses?: TaskStatus[];
}

const props = withDefaults(defineProps<Props>(), {
    task_statuses: () => [],
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role === 'super-admin-admin' || role === 'admin-admin';
}
</script>

<template>
    <Head title="Task Status" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task Status" description="Manage master data task status" />
            <TaskStatusTable :task_statuses="props.task_statuses" :has-permission="hasPermission()" />
        </div>
    </AppLayout>
</template>
