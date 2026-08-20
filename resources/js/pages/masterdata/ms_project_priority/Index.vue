<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../types';
import ProjectPriorityForm from './Form.vue';
import ProjectPriorityTable from './Table.vue';

interface Props {
    project_priorities?: MasterDataItem[];
}

withDefaults(defineProps<Props>(), {
    project_priorities: () => [],
});

const overlay = useOverlay();

const priorityForm = overlay.create(ProjectPriorityForm);

const addPriority = () => {
    priorityForm.open();
};

const editPriority = (priority: MasterDataItem) => {
    priorityForm.open({ value: priority });
};
</script>

<template>
    <Head title="Project Priority" />

    <AppLayout title="Project Priority">
        <Heading title="Project Priority" description="Manage master data project priority">
            <UButton v-if="can('project-priority.create')" size="sm" @click="addPriority">Add Project Priority</UButton>
        </Heading>

        <ProjectPriorityTable :data="project_priorities" @edit="editPriority" />
    </AppLayout>
</template>
