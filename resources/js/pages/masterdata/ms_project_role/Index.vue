<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head, Link } from '@inertiajs/vue3';
import ProjectRoleTable from './Table.vue';

interface ProjectRoleListItem {
    id: string;
    name: string;
    created_at?: string;
}

interface Props {
    project_roles?: ProjectRoleListItem[];
}

withDefaults(defineProps<Props>(), {
    project_roles: () => [],
});
</script>

<template>
    <Head title="Project Role" />

    <AppLayout title="Project Role">
        <Heading title="Project Role" description="Manage master data project role">
            <Link v-if="can('project-role.create')" :href="route('project-role.create')">
                <UButton size="sm">Add Project Role</UButton>
            </Link>
        </Heading>

        <ProjectRoleTable :data="project_roles" />
    </AppLayout>
</template>
