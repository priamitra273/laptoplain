<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { can } from '@/lib/utils';
import { toLucideIcon } from '@/lib/menu';
import type { Menu } from '@/types';
import { router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import UButton from '@nuxt/ui/components/Button.vue';
import { getPaginationRowModel } from '@tanstack/vue-table';
import type { Column, PaginationState, SortingState, Table } from '@tanstack/vue-table';
import moment from 'moment';
import { computed, h, ref, useTemplateRef } from 'vue';

interface Props {
    data?: Menu[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const emits = defineEmits<{
    (event: 'edit', value: Menu): void;
}>();

const confirm = useConfirmDialog();
const toast = useToast();

const pageSizes = [10, 20, 50];

// Sort indicator is text-only (no arrow icon): the active column's label turns
// primary + semibold instead of showing a direction glyph.
const withSortHeader = (column: TableColumn<Menu>): TableColumn<Menu> => {
    if (column.enableSorting === false || typeof column.header !== 'string') return column;

    const label = column.header;

    return {
        ...column,
        header: ({ column: col }: { column: Column<Menu, unknown> }) => {
            const isSorted = col.getIsSorted();

            return h(UButton, {
                label,
                variant: 'ghost',
                color: isSorted ? 'primary' : 'neutral',
                size: 'sm',
                class: ['-mx-2.5', isSorted ? 'font-semibold' : 'font-medium'],
                onClick: () => col.toggleSorting(isSorted === 'asc'),
            });
        },
    } as TableColumn<Menu>;
};

const baseColumns: TableColumn<Menu>[] = [
    { accessorKey: 'label', header: 'Label' },
    { accessorKey: 'parent', header: 'Parent' },
    { accessorKey: 'icon', header: 'Icon', enableSorting: false },
    { accessorKey: 'route', header: 'Route Name' },
    { accessorKey: 'sequence_number', header: 'Sequence' },
    { accessorKey: 'is_active', header: 'Status' },
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => moment(row.getValue('created_at')).format('DD MMM YYYY, HH:mm'),
    },
    { id: 'actions', enableSorting: false },
];

const columns = baseColumns.map(withSortHeader);

const globalFilter = ref('');
const sorting = ref<SortingState>([]);
const pagination = ref<PaginationState>({ pageIndex: 0, pageSize: pageSizes[0] });

const table = useTemplateRef<{ tableApi: Table<Menu> }>('table');
const paginationRowModel = getPaginationRowModel<Menu>();

const total = computed(() => table.value?.tableApi?.getFilteredRowModel().rows.length ?? 0);

const page = computed({
    get: () => pagination.value.pageIndex + 1,
    set: (value: number) => (pagination.value = { ...pagination.value, pageIndex: value - 1 }),
});

const pageSize = computed({
    get: () => pagination.value.pageSize,
    set: (value: number) => (pagination.value = { pageIndex: 0, pageSize: value }),
});

const getDropdownActions = (row: Menu) => {
    const items: DropdownMenuItem[] = [];

    if (can('menu.update')) {
        items.push({
            label: 'Edit',
            icon: 'i-lucide-pencil',
            onSelect() {
                emits('edit', row);
            },
        });
    }

    if (can('menu.delete')) {
        items.push({
            label: 'Delete',
            icon: 'i-lucide-trash',
            onSelect() {
                handleDelete(row);
            },
        });
    }

    return items;
};

const handleDelete = async (row: Menu) => {
    const confirmed = await confirm({
        title: 'Delete Menu',
        description: `Are you sure want to delete ${row.label} menu?`,
    });

    if (confirmed) {
        router.delete(route('menu.destroy', row.uuid), {
            onSuccess() {
                toast.add({ title: 'Success', description: 'Success delete data', color: 'success' });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-3">
        <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search Menu" class="md:w-md" />

        <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
            <div>
                <UTable
                    ref="table"
                    v-model:global-filter="globalFilter"
                    v-model:sorting="sorting"
                    v-model:pagination="pagination"
                    :data="data"
                    :columns="columns"
                    :pagination-options="{ getPaginationRowModel: paginationRowModel }"
                    class="flex-1"
                >
                    <template #icon-cell="{ row }">
                        <UIcon v-if="row.original.icon" :name="toLucideIcon(row.original.icon)!" class="size-4" />
                    </template>

                    <template #is_active-cell="{ row }">
                        <UBadge :color="row.original.is_active ? 'success' : 'error'" variant="subtle">
                            {{ row.original.is_active ? 'Active' : 'Nonactive' }}
                        </UBadge>
                    </template>

                    <template #actions-cell="{ row }">
                        <div class="flex justify-end">
                            <UDropdownMenu :items="getDropdownActions(row.original)" :content="{ align: 'end', side: 'bottom' }">
                                <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" aria-label="Actions" />
                            </UDropdownMenu>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <div class="flex items-center justify-center gap-3">
            <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
            <UPagination v-model:page="page" :items-per-page="pageSize" :total="total" />
        </div>
    </div>
</template>
