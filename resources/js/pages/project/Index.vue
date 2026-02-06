<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Project } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProjectTable from './Table.vue';

interface Props {
    projects: Project[];
    statuses: { id: number; name: string }[];
    priorities: { id: number; name: string }[];

    // roles: { id: number; name: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
    // roles: () => [],
});
</script>

<template>
    <Head title="Project" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project" description="Manage master data project" />
            <ProjectTable :projects="props.projects" :statuses="props.statuses" :priorities="props.priorities" />
        </div>
    </AppLayout>
</template>
