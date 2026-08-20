<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import ProjectForm from './Form.vue';
import ProjectTable from './Table.vue';
import type { Project, ProjectPriorityOption, ProjectStatusOption } from './types';

interface Props {
    projects?: Project[];
    statuses?: ProjectStatusOption[];
    priorities?: ProjectPriorityOption[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
});

const overlay = useOverlay();

const projectForm = overlay.create(ProjectForm);

const addProject = () => {
    projectForm.open({ statuses: props.statuses, priorities: props.priorities });
};
</script>

<template>
    <Head title="Project" />

    <AppLayout title="Project">
        <Heading title="Project" description="Manage and track all your projects">
            <UButton v-if="can('project.create')" size="sm" @click="addProject">Add Project</UButton>
        </Heading>

        <ProjectTable :data="projects" :statuses="statuses" :priorities="priorities" />
    </AppLayout>
</template>
