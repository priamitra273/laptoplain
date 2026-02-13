<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { PrimeSeverity, Project } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
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
    // roles: { id: number; name: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
    // roles: () => [],
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role ? (role.startsWith('super-admin-') || role.startsWith('admin-')) : false;
}
</script>

<template>
    <Head title="Project" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project" description="Manage master data project" />
            <ProjectTable :projects="props.projects" :statuses="props.statuses" :priorities="props.priorities" :has-permission="hasPermission()" />
        </div>
    </AppLayout>
</template>
