<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectRole } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import ProjectRoleTable from './Table.vue';

interface Props {
    project_roles?: ProjectRole[];
}

const props = withDefaults(defineProps<Props>(), {
    project_roles: () => [],
});
</script>

<template>
    <Head title="Project Role" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Role" description="Manage master data project role" />

            <ProjectRoleTable :project_roles="props.project_roles" />
        </div>
        <Toast />
        <ConfirmDialog />
    </AppLayout>
</template>
