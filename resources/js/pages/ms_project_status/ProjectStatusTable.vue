<script setup lang="ts">
import { MsProjectStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import FormProjectStatus from './FormProjectStatus.vue';

interface Props {
    statuses: MsProjectStatus[];
}

const props = defineProps<Props>();

const visible = ref(false);
const selected = ref<MsProjectStatus | null>(null);

const openCreate = () => {
    selected.value = null;
    visible.value = true;
};

const openEdit = (status: MsProjectStatus) => {
    selected.value = status;
    visible.value = true;
};

const destroy = (status: MsProjectStatus) => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ms_project_status.destroy', status.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Project status has been deleted.', 'success');
                },
            });
        }
    });
};
</script>

<template>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">List of Project Status</h2>
        <Button label="Add New" icon="pi pi-plus" @click="openCreate" />
    </div>

    <DataTable :value="props.statuses" class="mt-4" striped-rows>
        <Column field="name" header="Name" />
        <Column field="severity" header="Severity" />
        <Column header="Action">
            <template #body="{ data }">
                <div class="flex gap-2">
                    <Button icon="pi pi-pencil" severity="info" @click="openEdit(data)" />
                    <Button icon="pi pi-trash" severity="danger" @click="destroy(data)" />
                </div>
            </template>
        </Column>
    </DataTable>

    <FormProjectStatus v-model:visible="visible" :value="selected" />
</template>
