<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import { AnalyticServer, Hardware, HardwareComponent, HardwareStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { DataTablePageEvent } from 'primevue/datatable';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import AnalyticServerForm from './AnalyticServerForm.vue';
import MaintenanceForm from './MaintenanceForm.vue';

interface Props {
    servers?: AnalyticServer[];
    hardware?: Hardware[];
    components: HardwareComponent[];
    hardwareStatus: HardwareStatus[];
}

const visibleImportDialog = ref<boolean>(false);

const props = withDefaults(defineProps<Props>(), {
    servers: () => [],
    hardware: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const visibleMaintenanceForm = ref<boolean>(false);
const selected = ref<AnalyticServer>();

const page = ref<number>(0);
const rows = ref<number>(25);

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.servers?.find((item) => item.uuid === event.item.menuKey);
            visibleForm.value = true;
        },
    },
    {
        label: 'Maintenance Form',
        command(event) {
            selected.value = props.servers?.find((item) => item.uuid === event.item.menuKey);
            visibleMaintenanceForm.value = true;
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    },
];

const destroy = (server: AnalyticServer) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${server.ip_address} server?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('analytic-server.destroy', server.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
        }
    });
};

const onPageChange = (event: DataTablePageEvent) => {
    page.value = event.page;
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
            window.open(route('server.export'), '_blank');
        },
    },
];

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

            <SplitButton label="Add Server" raised @click="visibleForm = true" :model="splitButtonItems" size="small">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </SplitButton>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden p-0">
            <DataTable
                :value="servers"
                v-model:filters="filters"
                data-key="uuid"
                paginator
                v-model:rows="rows"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['ip_address', 'lisence']"
                striped-rows
                row-hover
                @page="onPageChange"
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ page * rows + (index + 1) }}
                    </template>
                </Column>

                <Column field="ip_address" header="IP Address" sortable></Column>
                <Column field="lisence" header="Lisence" sortable></Column>

                <Column field="is_active" header="Active">
                    <template #body="{ data }">
                        <Tag :value="data.is_active ? 'Active' : 'Non Active'" :severity="data.is_active ? 'success' : 'danger'" />
                    </template>
                </Column>

                <Column field="is_alive" header="Availability">
                    <template #body="{ data }">
                        <Tag :severity="data.is_alive ? 'success' : 'danger'" class="border !bg-transparent dark:border-surface-700">
                            <div class="flex items-center gap-2">
                                <span class="relative flex h-3 w-3">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                        :class="[data.is_alive ? 'bg-green-400' : 'bg-red-400']"
                                    ></span>
                                    <span
                                        class="relative inline-flex h-3 w-3 rounded-full"
                                        :class="[data.is_alive ? 'bg-green-500' : 'bg-red-500']"
                                    ></span>
                                </span>
                                <span>{{ data.is_alive ? 'Online' : 'Offline' }}</span>
                            </div>
                        </Tag>
                    </template>
                </Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column field="updated_at" header="Last Update" sortable>
                    <template #body="{ data }">
                        {{ moment(data.updated_at).format('DD MMM YYYY, HH:mm') }}
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

    <AnalyticServerForm v-model:visible="visibleForm" :value="selected" :hardware="hardware" />
    <MaintenanceForm v-model:visible="visibleMaintenanceForm" :value="selected" :hardware :components :hardware-status />
    <UploadDialog
        v-model:visible="visibleImportDialog"
        :verify-url="route('server.verify-import')"
        template-url="/templates/Test Import Server.xlsx"
        header="Import Analytic Server"
    />
</template>
