<script setup lang="ts">
import { Ref, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api';
import Icon from '@/components/Icon.vue';
import moment from 'moment';
import DropdownButton from '@/components/DropdownButton.vue';
import Swal from 'sweetalert2'
import { MenuItem } from 'primevue/menuitem';
import { DeviceExtended } from './type';
import DeviceForm from './DeviceForm.vue';
import DataTable from 'primevue/datatable';
import UploadDialog from '@/components/UploadDialog.vue';

interface Props {
    devices?: DeviceExtended[]
}

const props = withDefaults(defineProps<Props>(), {
    devices: () => []
})

const dt: Ref<typeof DataTable | null> = ref(null);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false)
const selected = ref<DeviceExtended>()

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.devices?.find((item) => item.uuid === event.item.menuKey)
            visibleForm.value = true;
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data)
        },
    }
];

const visibleImportDialog = ref<boolean>(false);

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
            window.open(route('device.export'), '_blank');
        },
    },
];

const destroy = (device: DeviceExtended) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${device.mac_address} device?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('device.destroy', device.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success')
                }
            })
        }
    });
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined
})

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

            <SplitButton
                class="p-button-raised"
                :model="splitButtonItems"
                @click="goToCreate"
                size="small"
            >
                <Icon name="Plus" />
                <span>Add Device</span>
            </SplitButton>
        </div>

        <!-- Datatable -->
        <div class="card p-0 overflow-hidden">
            <DataTable ref="dt" :value="devices" v-model:first="first" v-model:filters="filters" data-key="uuid" paginator :rows="25"
                :rowsPerPageOptions="[25, 50, 100]" :globalFilterFields="['mac_address', 'ip_dhcp', 'ip_static', 'site_id', 'cctv_name']" striped-rows row-hover>
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 + first }}
                    </template>
                </Column>

                <Column field="mac_address" header="Mac Address" sortable></Column>
                <Column field="ip_dhcp" header="IP DHCP" sortable></Column>
                <Column field="ip_static" header="IP Static" sortable></Column>
                <Column field="site_id" header="Site ID" sortable></Column>
                <Column field="cctv_name" header="CCTV Name" sortable></Column>

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
                    <p class="text-center">
                        No Data
                    </p>
                </template>
            </DataTable>
        </div>
    </div>

    <DeviceForm v-model:visible="visibleForm" :value="selected" />
    <UploadDialog v-model:visible="visibleImportDialog" :verify-url="route('device.verify-import')" template-url="/templates/Test Import Device.xlsx" header="Import Device" />
</template>
