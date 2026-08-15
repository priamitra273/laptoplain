<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import PreconfigView from '@/pages/preconfig/partials/PreconfigView.vue';
import { Pagination } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import moment from 'moment';
import { DataTableFilterEvent, DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import { MenuItem } from 'primevue/menuitem';
import { onMounted, ref, watch } from 'vue';
import { Preconfig, Regency } from './type';

interface PreconfigPagination extends Pagination {
    data: Preconfig[];
}

interface FilterParams {
    regency_uuid?: {
        matchMode?: string;
        value?: string | string[] | null;
    };
    [key: string]: any;
}

interface DatatableParams {
    rows: number;
    page?: number | null;
    sortField?: string | ((item: any) => string) | null;
    sortOrder?: 1 | 0 | -1 | null;
    filters?: FilterParams;
    q?: string;
}

interface Props {
    regencies: Regency[];
}

const props = withDefaults(defineProps<Props>(), {
    regencies: () => [],
});

const visibleImportDialog = ref<boolean>(false);

const sites = ref<PreconfigPagination>();
const dt = ref();
const isLoadingDatatable = ref(false);

const filters = ref({
    global: { value: route().params?.q, matchMode: FilterMatchMode.CONTAINS },
    regency_uuid: { value: null, matchMode: FilterMatchMode.IN },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Preconfig>();

const items: MenuItem[] = [
    {
        label: 'View',
        command(event) {
            selected.value = sites.value?.data.find((item) => item.uuid === event.item.menuKey);
            visibleForm.value = true;
        },
    },
    {
        label: 'Setting',
        command(event) {
            router.visit(route('preconfig.edit', event.item.menuKey));
        },
    },
];

const onPage = async (event: DataTablePageEvent) => {
    sites.value = await getData({
        rows: event.rows,
        page: event.page + 1,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value,
    });
};

const onSort = async (event: DataTableSortEvent) => {
    sites.value = await getData({
        rows: event.rows,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value,
    });
};

const onFilter = async (event: DataTableFilterEvent) => {
    sites.value = await getData({
        rows: event.rows,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        filters: event.filters,
        q: filters.value.global.value,
    });
};

const getData = async (params: DatatableParams) => {
    isLoadingDatatable.value = true;

    const response = await axios.get(route('preconfig.datatable', { ...params }));

    isLoadingDatatable.value = false;

    return response.data;
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined;
});

watchDebounced(
    () => filters.value.global.value,
    async (newValue) => {
        sites.value = await getData({
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
            window.open(route('preconfig.export'), '_blank');
        },
    },
];

onMounted(async () => {
    sites.value = await getData({ rows: dt.value.rows });
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
                :value="sites?.data"
                v-model:filters="filters"
                data-key="uuid"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name']"
                lazy
                :loading="isLoadingDatatable"
                :total-records="sites?.meta.total"
                filter-display="menu"
                @page="onPage"
                @sort="onSort"
                @filter="onFilter"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 + dt.d_first }}
                    </template>
                </Column>

                <Column field="project_name" header="Project" sortable body-class="whitespace-nowrap"> </Column>

                <Column
                    field="regency_name"
                    header="Regency"
                    filter-field="regency_uuid"
                    sortable
                    :show-filter-match-modes="false"
                    :filter-menu-style="{ width: '14rem' }"
                >
                    <template #filter="{ filterModel }">
                        <MultiSelect
                            v-model="filterModel.value"
                            :options="regencies"
                            optionLabel="name"
                            option-value="uuid"
                            placeholder="Any"
                            :max-selected-labels="1"
                            filter
                        >
                        </MultiSelect>
                    </template>
                </Column>

                <Column field="site_id" header="Site ID" sortable></Column>
                <Column field="site_name" header="Site Name" sortable></Column>
                <Column field="latitude" header="Latitude" sortable></Column>
                <Column field="longitude" header="Longitude" sortable></Column>
                <Column field="total_cctv" header="Total CCTV" sortable></Column>

                <Column field="last_update" header="Last update" sortable>
                    <template #body="{ data }">
                        {{ data.last_update ? moment(data.last_update).format('DD MMM YYYY, HH:mm') : null }}
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

        <PreconfigView :preconfig="selected" v-model:visible="visibleForm" />
        <UploadDialog
            v-model:visible="visibleImportDialog"
            :verify-url="route('preconfig.verify-import')"
            template-url="/templates/Test Import Preconfig.xlsx"
            header="Import Preconfig"
        />
    </div>
</template>
