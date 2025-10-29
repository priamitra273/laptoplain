<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api';
import Icon from '@/components/Icon.vue';
import moment from 'moment';
import DropdownButton from '@/components/DropdownButton.vue';
import { MenuItem } from 'primevue/menuitem';
import { DataTableFilterEvent, DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import { DatatableParams, Installation, ListPagination, Regency } from './type';
import PreconfigView from '@/pages/preconfig/partials/PreconfigView.vue';

interface Props {
    regencies: Regency[]
}

const props = withDefaults(defineProps<Props>(), {
    regencies: () => []
})

const sites = ref<ListPagination>()
const dt = ref()
const isLoadingDatatable = ref(false);

const filters = ref({
    global: { value: route().params?.q, matchMode: FilterMatchMode.CONTAINS },
    regency_uuid: { value: null, matchMode: FilterMatchMode.IN }
});

const visibleForm = ref<boolean>(false)
const selected = ref<Installation>();

const statusSeverities: Record<string, string> = {
    OPEN: 'primary',
    PROGRESS: 'warn',
    RELOCATION: 'warn',
    DISMANTLE: 'danger',
    COMPLETE: 'success'
}

const items: MenuItem[] = [
    {
        label: 'View',
        command(event) {
            selected.value = sites.value?.data.find((item) => item.uuid === event.item.menuKey)
            visibleForm.value = true;
        },
    },
    {
        label: 'Update',
        command(event) {
            router.visit(route('installation.edit', event.item.menuKey))
        },
    }
];

const onPage = async (event: DataTablePageEvent) => {
    sites.value = await getData({
        rows: event.rows,
        page: event.page + 1,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value
    })
}

const onSort = async (event: DataTableSortEvent) => {
    sites.value = await getData({
        rows: event.rows,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        q: filters.value.global.value
    })
}

const onFilter = async (event: DataTableFilterEvent) => {
    sites.value = await getData({
        rows: event.rows,
        sortField: event.sortField,
        sortOrder: event.sortOrder,
        filters: event.filters,
        q: filters.value.global.value,
    })
}

const getData = async (params: DatatableParams) => {
    isLoadingDatatable.value = true;

    const response = await axios.get(route('installation.datatable', { ...params }));

    isLoadingDatatable.value = false;

    return response.data
}

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined
})

watchDebounced(() => filters.value.global.value, async (newValue) => {
    sites.value = await getData({
        rows: dt.value.rows,
        sortField: dt.value.sortField,
        sortOrder: dt.value.sortOrder,
        q: newValue,
    })
}, { debounce: 500, maxWait: 1000 })

onMounted(async () => {
    sites.value = await getData({ rows: dt.value.rows })
})

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
        </div>

        <!-- Datatable -->
        <div class="card p-0 overflow-hidden">
            <DataTable ref="dt" :value="sites?.data" v-model:filters="filters" data-key="uuid" paginator :rows="25"
                :rowsPerPageOptions="[25, 50, 100]" :globalFilterFields="['name']" lazy :loading="isLoadingDatatable"
                :total-records="sites?.meta.total" filter-display="menu" @page="onPage" @sort="onSort" @filter="onFilter" scrollable striped-rows row-hover>

                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 + dt.d_first }}
                    </template>
                </Column>

                <Column field="project_name" header="Project" :sortable="true" body-class="whitespace-nowrap">
                </Column>

                <Column field="regency_name" header="Regency" filter-field="regency_uuid" :sortable="true"
                    :show-filter-match-modes="false" :filter-menu-style="{ width: '14rem' }" show-clear-button>
                    <template #filter="{ filterModel }">
                        <MultiSelect v-model="filterModel.value" :options="regencies" optionLabel="name"
                            option-value="uuid" placeholder="Any" :max-selected-labels="1" filter>
                        </MultiSelect>
                    </template>
                </Column>

                <Column field="site_id" header="Site ID" class="whitespace-nowrap" :sortable="true"></Column>
                <Column field="site_name" header="Site Name" :sortable="true"></Column>
                <Column field="latitude" header="Latitude" :sortable="true"></Column>
                <Column field="longitude" header="Longitude" :sortable="true"></Column>

                <Column field="status" header="Status" >
                    <template #body="{data}">
                        <Tag :value="data.status" :severity="statusSeverities[data.status]" />
                    </template>
                </Column>

                <Column field="updated_at" header="Last update" class="whitespace-nowrap" :sortable="true">
                    <template #body="{ data }">
                        {{ data.updated_at ? moment(data.updated_at).format('DD MMM YYYY, HH:mm') : null }}
                    </template>
                </Column>

                <Column frozen>
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

        <PreconfigView :preconfig="selected" v-model:visible="visibleForm" />
    </div>
</template>
