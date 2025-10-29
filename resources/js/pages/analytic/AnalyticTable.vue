<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import { Pagination } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import moment from 'moment';
import { DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { onMounted, ref, watch } from 'vue';
import { Analytic } from './type';

interface AnalyticPagination extends Pagination {
    data: Analytic[];
}

const visibleImportDialog = ref<boolean>(false);

interface DatatableParams {
    rows: number;
    page?: number | null;
    sortField?: string | ((item: any) => string) | null;
    sortOrder?: 1 | 0 | -1 | null;
    q?: string;
}

const analytics = ref<AnalyticPagination>();
const dt = ref();

const filters = ref({
    global: { value: route().params?.q, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Analytic>();

const statusSeverities: Record<string, string> = {
    OPEN: 'primary',
    DRAFT: 'warn',
    CANCEL: 'danger',
    COMPLETE: 'success',
};

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            router.visit(route('analytic.edit', event.item.menuKey));
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    },
];

const destroy = (analytic: Analytic) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete analytic on ${analytic.name}?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('analytic.destroy', analytic.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
        }
    });
};

const onPage = async (event: DataTablePageEvent) => {
    analytics.value = await getData({
        rows: event.rows,
        page: event.page + 1,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value,
    });
};

const onSort = async (event: DataTableSortEvent) => {
    analytics.value = await getData({
        rows: event.rows,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value,
    });
};

const getData = async (params: DatatableParams) => {
    const response = await axios.get(route('analytic.datatable', { ...params }));

    return response.data;
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined;
});

watchDebounced(
    () => filters.value.global.value,
    async (newValue) => {
        analytics.value = await getData({
            rows: dt.value.rows,
            sortField: dt.value.sortField,
            sortOrder: dt.value.sortOrder,
            q: newValue,
        });
    },
    { debounce: 500, maxWait: 1000 },
);

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
            window.open(route('analytic.export'), '_blank');
        },
    },
];

onMounted(async () => {
    analytics.value = await getData({ rows: dt.value.rows });
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Action Table -->
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" id="search" placeholder="Search" autofocus />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <!-- <Button label="Import" icon="pi pi-upload" @click="visibleImportDialog = true" raised></Button> -->
            <SplitButton class="p-button-raised" :model="splitButtonItems" size="small">
                <Icon name="Menu" />
                <span>More</span>
            </SplitButton>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden p-0">
            <DataTable
                ref="dt"
                :value="analytics?.data"
                v-model:filters="filters"
                data-key="uuid"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name']"
                lazy
                :total-records="analytics?.meta.total"
                @page="onPage"
                @sort="onSort"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="project_name" header="Project" sortable></Column>
                <Column field="site_id" header="Site ID" sortable></Column>
                <Column field="name" header="CCTV Name" sortable></Column>
                <Column field="latitude" header="Latitude" sortable></Column>
                <Column field="longitude" header="Longitude" sortable></Column>

                <Column field="analytic_status_name" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag :value="data.analytic_status_name" :severity="statusSeverities[data.analytic_status_name]" />
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

    <UploadDialog
        v-model:visible="visibleImportDialog"
        :verify-url="route('analytic.verify-import')"
        template-url="/templates/Test Import Analytic.xlsx"
        header="Import Config Analytic"
    />
</template>
