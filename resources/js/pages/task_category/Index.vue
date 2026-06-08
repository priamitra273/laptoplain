<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { TaskCategory } from '@/types';
import { Head } from '@inertiajs/vue3';
import TaskCategoryTable from './Table.vue';

interface Props {
    task_categories?: TaskCategory[];
}

const props = withDefaults(defineProps<Props>(), {
    task_categories: () => [],
});

</script>

<template>
    <Head title="Task Category" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task Category" description="Manage master data task categories" />
            <TaskCategoryTable :task_categories="props.task_categories" />
        </div>
    </AppLayout>
</template>
