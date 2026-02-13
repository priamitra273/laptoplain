<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { TaskPriority } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import TaskPriorityTable from './Table.vue';

interface Props {
    task_priorities?: TaskPriority[];
}

const props = withDefaults(defineProps<Props>(), {
    task_priorities: () => [],
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role ? (role.startsWith('super-admin-') || role.startsWith('admin-')) : false;
}
</script>

<template>
    <Head title="Task Priority" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task Priority" description="Manage master data task priority" />
            <TaskPriorityTable :task_priorities="props.task_priorities" :has-permission="hasPermission()" />
        </div>
    </AppLayout>
</template>
