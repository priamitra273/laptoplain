<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectPriority } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProjectPriorityTable from './Table.vue';

interface Props {
    project_priorities?: ProjectPriority[];
}

const props = withDefaults(defineProps<Props>(), {
    project_priorities: () => [],
});

</script>

<template>
    <Head title="Project Priority" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Priority" description="Manage master data project priority" />
            <ProjectPriorityTable :project_priorities="props.project_priorities" />
        </div>
    </AppLayout>
</template>
