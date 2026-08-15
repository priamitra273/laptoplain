<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { PrimeSeverity } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Project } from '.';
import ProjectTable from './Table.vue';

interface ProjectStatus {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

interface ProjectPriority {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

interface Props {
    projects: Project[];
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
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
