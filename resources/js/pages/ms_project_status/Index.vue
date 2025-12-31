<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { MsProjectStatus } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import ProjectStatusTable from './Table.vue';

interface Props {
    statuses?: MsProjectStatus[];
}

const props = withDefaults(defineProps<Props>(), {
    statuses: () => [],
});
</script>

<template>
    <Head title="Project Status" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Status" description="Manage master data project status" />

            <ProjectStatusTable :statuses="props.statuses" />
        </div>
        <Toast />
        <ConfirmDialog />
    </AppLayout>
</template>
