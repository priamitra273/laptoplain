<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Project } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProjectTable from './ProjectTable.vue';

interface Props {
    projects?: Project[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
});
</script>

<template>
    <Head title="Project" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project" description="Manage master data project" />

            <ProjectTable :projects="props.projects" />
        </div>
    </AppLayout>
</template>
