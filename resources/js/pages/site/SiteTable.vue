<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import { Pagination, Site } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import moment from 'moment';
import { DataTableFilterEvent, DataTablePageEvent, DataTableSortEvent } from 'primevue/datatable';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { onMounted, ref, watch } from 'vue';
import { Regency } from './type';

interface SitePagination extends Pagination {
    data: Site[];
}

interface FilterParams {
    regency_uuid?: {
        matchMode?: string;
        value?: string | string[] | null;
    };
    [key: string]: any;
}
const visibleImportDialog = ref<boolean>(false);

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

const sites = ref<SitePagination>();
const dt = ref();
const isLoadingDatatable = ref(false);

const filters = ref({
    global: { value: route().params?.q, matchMode: FilterMatchMode.CONTAINS },
    regency_uuid: { value: null, matchMode: FilterMatchMode.IN },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Site>();

const statusSeverities: Record<string, string> = {
    OPEN: 'primary',
    PROGRESS: 'warn',
    RELOCATION: 'warn',
    DISMANTLE: 'danger',
    COMPLETE: 'success',
};

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            router.visit(route('site.edit', event.item.menuKey));
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
    router.visit(route('site.create'));
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
            window.open(route('site.export'), '_blank');
        },
    },
];

const destroy = (site: Site) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete site ${site.site_id}?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('site.destroy', site.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
        }
    });
};

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

    const response = await axios.get(route('site.datatable', { ...params }));

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

            <!-- <div class="flex gap-2">
                <Button label="Import" text raised @click="visibleImportDialog = true">
                    <template #icon>
                        <Icon name="Upload" />
                    </template>
                </Button>

                <Button raised as-child v-slot="slotProps">
                    <Link :href="route('site.create')" :class="slotProps.class">
                        <Icon name="Plus" />
                        <span>Add Site</span>
                    </Link>
                </Button>
            </div> -->

            <SplitButton
                class="p-button-raised"
                :model="splitButtonItems"
                @click="goToCreate"
                size="small"
            >
                <Icon name="Plus" />
                <span>Add Site</span>
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
                        {{ index + 1 }}
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

                <Column field="has_preconfig" header="Has Preconfig">
                    <template #body="{ data }">
                        <Tag :value="data.has_preconfig ? 'YES' : 'NO'" :severity="data.has_preconfig ? 'success' : 'danger'" />
                    </template>
                </Column>

                <Column field="status" header="Status Installation" sortable>
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="statusSeverities[data.status]" />
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

    <UploadDialog v-model:visible="visibleImportDialog" :verify-url="route('site.verify-import')" template-url="/templates/Test Import Site.xlsx" header="Import Site" />
</template>