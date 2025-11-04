<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { MsProjectStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import FormProjectStatus from './Form.vue';

interface Props {
    statuses: MsProjectStatus[];
}

const props = defineProps<Props>();

const filters = ref({
    global: { value: '', matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref(false);
const selected = ref<MsProjectStatus | null>(null);

const openCreate = () => {
    selected.value = null;
    visibleForm.value = true;
};

const openEdit = (status: MsProjectStatus) => {
    selected.value = status;
    visibleForm.value = true;
};

const destroy = (status: MsProjectStatus) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete "${status.name}"?`,
        text: 'This action cannot be undone!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ms_project_status.destroy', status.id), {
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Project status has been deleted.', 'success');
                },
            });
        }
    });
};

// Item dropdown untuk Edit/Delete
const items = [
    {
        label: 'Edit',
        command(event: any) {
            const data = event.item.data;
            openEdit(data);
        },
    },
    {
        label: 'Delete',
        command(event: any) {
            const data = event.item.data;
            destroy(data);
        },
    },
];

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = null;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- 🔹 Action bar -->
        <div class="flex justify-between gap-2">
            <!-- 🔍 Search -->
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <!-- ➕ Add button -->
            <Button label="Add Project Status" raised @click="openCreate">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- 🔹 Tabel Data -->
        <div class="card overflow-hidden">
            <DataTable
                :value="props.statuses"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name', 'severity']"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="name" header="Name" sortable />
                <Column field="severity" header="Severity" sortable />

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Action">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-4 text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <FormProjectStatus v-model:visible="visibleForm" :value="selected" />
</template>
