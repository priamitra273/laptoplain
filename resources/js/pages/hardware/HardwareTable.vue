<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import HardwareStatusTag from '@/components/HardwareStatusTag.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import { Hardware, HardwareBrand, HardwareComponent, HardwareModel } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import HardwareForm from './HardwareForm.vue';

interface Props {
    hardware?: Hardware[];
    components?: HardwareComponent[];
    brands?: HardwareBrand[];
    models?: HardwareModel[];
}

const props = withDefaults(defineProps<Props>(), {
    hardware: () => [],
    components: () => [],
    brands: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const visibleImportDialog = ref<boolean>(false);
const selected = ref<Hardware>();

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.hardware?.find((item) => item.uuid === event.item.menuKey);
            visibleForm.value = true;
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    },
];

const goToCreate = () => {
    visibleForm.value = true;
};

const splitButtonItems: MenuItem[] = [
    {
        label: 'Import',
        icon: 'pi pi-upload',
        command: () => {
            visibleImportDialog.value = true;
        },
    },
    {
        label: 'Export',
        icon: 'pi pi-download',
        command: () => {
            window.open(route('hardware.export'), '_blank');
        },
    },
];

const destroy = (hardware: Hardware) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${hardware.serial_number} device?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('hardware.destroy', hardware.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
        }
    });
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Action Table -->
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <SplitButton class="p-button-raised" :model="splitButtonItems" @click="goToCreate" size="small">
                <Icon name="Plus" />
                <span>Add Hardware</span>
            </SplitButton>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="hardware"
                v-model:filters="filters"
                data-key="uuid"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['serial_number', 'category']"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="serial_number" header="Serial Number" sortable></Column>

                <Column field="category" header="Category" sortable>
                    <template #body="{ data }">
                        <Tag :value="data.category?.toUpperCase()" />
                    </template>
                </Column>

                <Column field="brand.name" header="Brand" sortable></Column>

                <Column field="model" header="Model" sortable></Column>

                <Column field="remarks" header="Notes" sortable>
                    <template #body="{ data }">
                        {{ data.remarks || '-' }}
                    </template>
                </Column>

                <Column field="used_by_server_ips" header="Used By" sortable>
                    <template #body="{ data }">
                        {{ data.used_by_server_ips || '-' }}
                    </template>
                </Column>

                <Column field="status.name" header="Status" sortable>
                    <template #body="{ data }">
                        <HardwareStatusTag :value="data.status" />
                    </template>
                </Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column>
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.uuid" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <HardwareForm v-model:visible="visibleForm" :value="selected" :components="props.components" :brands="props.brands" :models="props.models" />
    <UploadDialog
        v-model:visible="visibleImportDialog"
        :verify-url="route('hardware.verify-import')"
        template-url="/templates/Test Import Hardware.xlsx"
        header="Import Hardware"
    />
</template>
