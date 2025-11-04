<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { TaskType } from '@/types';
import { Head } from '@inertiajs/vue3';
import TaskTypeTable from './Table.vue';

interface Props {
    task_types?: TaskType[];
}

const props = withDefaults(defineProps<Props>(), {
    task_types: () => [],
});
</script>

<template>
    <Head title="Task Type" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task Type" description="Manage master data task type" />

            <TaskTypeTable :task_types="props.task_types" />
        </div>
    </AppLayout>
</template>
