<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../types';
import ProjectStatusForm from './Form.vue';
import ProjectStatusTable from './Table.vue';

interface Props {
    statuses?: MasterDataItem[];
}

withDefaults(defineProps<Props>(), {
    statuses: () => [],
});

const overlay = useOverlay();

const statusForm = overlay.create(ProjectStatusForm);

const addStatus = () => {
    statusForm.open();
};

const editStatus = (status: MasterDataItem) => {
    statusForm.open({ value: status });
};
</script>

<template>
    <Head title="Project Status" />

    <AppLayout title="Project Status">
        <Heading title="Project Status" description="Manage master data project status">
            <UButton v-if="can('project-status.create')" size="sm" @click="addStatus">Add Project Status</UButton>
        </Heading>

        <ProjectStatusTable :data="statuses" @edit="editStatus" />
    </AppLayout>
</template>
