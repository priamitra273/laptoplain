<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectRole } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import ProjectRoleTable from './Table.vue';

interface Props {
    project_roles?: ProjectRole[];
}

const props = withDefaults(defineProps<Props>(), {
    project_roles: () => [],
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role === 'super-admin-admin' || role === 'admin-admin';
}
</script>

<template>
    <Head title="Project Role" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Role" description="Manage master data project role" />

            <ProjectRoleTable :project_roles="props.project_roles" :has-permission="hasPermission()" />
        </div>
    </AppLayout>
</template>
