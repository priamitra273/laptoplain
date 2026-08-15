<script setup lang="ts" generic="T extends Record<string, unknown>">
// Thin wrapper over UTable — which is itself a TanStack Table wrapper. Only what
// UTable leaves out is added here: the pagination row model, clickable sort
// headers, and the surrounding UI.
import { computed, h, ref, useTemplateRef } from 'vue';
import { getPaginationRowModel } from '@tanstack/vue-table';
import type { Column, PaginationState, RowSelectionState, SortingState, Table, VisibilityState } from '@tanstack/vue-table';
import type { TableColumn } from '@nuxt/ui';
// Deep imports: auto-import only covers components used in templates, not h() in script.
import UButton from '@nuxt/ui/components/Button.vue';
import UCheckbox from '@nuxt/ui/components/Checkbox.vue';

const props = withDefaults(
    defineProps<{
        data: T[];
        columns: TableColumn<T>[];
        loading?: boolean;
        empty?: string;
        searchable?: boolean;
        searchPlaceholder?: string;
        selectable?: boolean;
        columnToggle?: boolean;
        pageSizes?: number[];
        /** Sorting, filtering and page slicing are handled by the server — TanStack stops doing them itself. */
        manual?: boolean;
        /** Total rows on the server. Required when `manual`, since the page count cannot be derived from `data`. */
        rowCount?: number;
        getRowId?: (row: T, index: number) => string;
    }>(),
    {
        empty: 'Tidak ada data.',
        searchable: true,
        searchPlaceholder: 'Cari...',
        columnToggle: true,
        pageSizes: () => [10, 20, 50],
    },
);

// Every model gets an initial value because UTable only wires onSortingChange,
// onPaginationChange, etc. when the value is not undefined at setup.
const globalFilter = defineModel<string>('globalFilter', { default: '' });
const sorting = defineModel<SortingState>('sorting', { default: () => [] });
const pagination = defineModel<PaginationState>('pagination', {
    default: () => ({ pageIndex: 0, pageSize: 10 }),
});
const rowSelection = defineModel<RowSelectionState>('rowSelection', { default: () => ({}) });
const columnVisibility = ref<VisibilityState>({});

const table = useTemplateRef<{ tableApi: Table<T> }>('table');
const tableApi = computed(() => table.value?.tableApi);
defineExpose({ tableApi });

// The one row model UTable does not include. Built once here rather than called
// in the template, so it is not recreated on every render.
const paginationRowModel = getPaginationRowModel<T>();

const columnId = (column: TableColumn<T>) => column.id ?? ('accessorKey' in column ? String(column.accessorKey) : '');

const headerLabel = (id: string) => {
    const column = props.columns.find((c) => columnId(c) === id);
    return typeof column?.header === 'string' ? column.header : id;
};

const sortIcon = (direction: false | 'asc' | 'desc') => {
    if (direction === 'asc') return 'i-lucide-arrow-up';
    if (direction === 'desc') return 'i-lucide-arrow-down';
    return 'i-lucide-arrow-up-down';
};

// UTable renders headers as-is, so a string header is swapped for a button.
// A caller's #<id>-header slot still wins over this transform.
const withSortHeader = (column: TableColumn<T>): TableColumn<T> => {
    if (column.enableSorting === false || typeof column.header !== 'string') return column;
    const label = column.header;
    return {
        ...column,
        header: ({ column: col }: { column: Column<T, unknown> }) =>
            h(UButton, {
                label,
                trailingIcon: sortIcon(col.getIsSorted()),
                variant: 'ghost',
                color: 'neutral',
                size: 'sm',
                class: '-mx-2 font-medium',
                onClick: () => col.toggleSorting(col.getIsSorted() === 'asc'),
            }),
    } as TableColumn<T>;
};

const selectColumn: TableColumn<T> = {
    id: 'select',
    enableSorting: false,
    enableHiding: false,
    meta: { class: { th: 'w-10', td: 'w-10' } },
    header: ({ table: api }) =>
        h(UCheckbox, {
            modelValue: api.getIsSomePageRowsSelected() ? 'indeterminate' : api.getIsAllPageRowsSelected(),
            'onUpdate:modelValue': (value: unknown) => api.toggleAllPageRowsSelected(!!value),
        }),
    cell: ({ row }) =>
        h(UCheckbox, {
            modelValue: row.getIsSelected(),
            'onUpdate:modelValue': () => row.toggleSelected(),
        }),
};

const columns = computed(() => {
    const mapped = props.columns.map(withSortHeader);
    return props.selectable ? [selectColumn, ...mapped] : mapped;
});

const columnItems = computed(() =>
    (tableApi.value?.getAllColumns() ?? [])
        .filter((column) => column.getCanHide())
        .map((column) => ({
            label: headerLabel(column.id),
            type: 'checkbox' as const,
            checked: column.getIsVisible(),
            onUpdateChecked: (checked: boolean) => column.toggleVisibility(checked),
            onSelect: (event: Event) => event.preventDefault(),
        })),
);

// UPagination wants a row count, not a page count.
const total = computed(() => (props.manual ? (props.rowCount ?? 0) : (tableApi.value?.getFilteredRowModel().rows.length ?? 0)));

const page = computed({
    get: () => pagination.value.pageIndex + 1,
    set: (value) => (pagination.value = { ...pagination.value, pageIndex: value - 1 }),
});

const pageSize = computed({
    get: () => pagination.value.pageSize,
    set: (value) => (pagination.value = { pageIndex: 0, pageSize: value }),
});

const selectedCount = computed(() => Object.keys(rowSelection.value).length);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div v-if="searchable || columnToggle" class="flex items-center gap-2">
            <UInput v-if="searchable" v-model="globalFilter" icon="i-lucide-search" :placeholder="searchPlaceholder" class="max-w-sm flex-1" />
            <UDropdownMenu v-if="columnToggle" :items="columnItems" :content="{ align: 'end' }" class="ms-auto">
                <UButton icon="i-lucide-settings-2" color="neutral" variant="outline" label="Kolom" />
            </UDropdownMenu>
        </div>

        <UTable
            ref="table"
            v-model:global-filter="globalFilter"
            v-model:sorting="sorting"
            v-model:pagination="pagination"
            v-model:row-selection="rowSelection"
            v-model:column-visibility="columnVisibility"
            :data="data"
            :columns="columns"
            :loading="loading"
            :empty="empty"
            :get-row-id="getRowId"
            :pagination-options="{
                getPaginationRowModel: paginationRowModel,
                manualPagination: manual,
                rowCount,
            }"
            :sorting-options="{ manualSorting: manual }"
            :column-filters-options="{ manualFiltering: manual }"
        >
            <template v-for="name in Object.keys($slots)" :key="name" #[name]="slotProps">
                <slot :name="name" v-bind="slotProps ?? {}" />
            </template>
        </UTable>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted">
                <template v-if="selectable">{{ selectedCount }} dari {{ total }} baris terpilih</template>
                <template v-else>{{ total }} baris</template>
            </p>

            <div class="flex items-center gap-3">
                <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
                <UPagination v-model:page="page" :items-per-page="pageSize" :total="total" />
            </div>
        </div>
    </div>
</template>
