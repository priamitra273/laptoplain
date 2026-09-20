<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { can } from '@/lib/utils';
import { router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import UButton from '@nuxt/ui/components/Button.vue';
import { getPaginationRowModel } from '@tanstack/vue-table';
import type { Column, PaginationState, SortingState, Table } from '@tanstack/vue-table';
import moment from 'moment';
import { computed, h, ref, useTemplateRef } from 'vue';

interface ProjectRoleListItem {
    id: string;
    name: string;
    created_at?: string;
}

interface Props {
    data?: ProjectRoleListItem[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const confirm = useConfirmDialog();

const pageSizes = [10, 20, 50];

// Sort indicator is text-only (no arrow icon): the active column's label turns
// primary + semibold instead of showing a direction glyph.
const withSortHeader = (column: TableColumn<ProjectRoleListItem>): TableColumn<ProjectRoleListItem> => {
    if (column.enableSorting === false || typeof column.header !== 'string') return column;

    const label = column.header;

    return {
        ...column,
        header: ({ column: col }: { column: Column<ProjectRoleListItem, unknown> }) => {
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
    } as TableColumn<ProjectRoleListItem>;
};

const baseColumns: TableColumn<ProjectRoleListItem>[] = [
    { header: 'No', enableSorting: false, cell: ({ row }) => row.index + 1 },
    { accessorKey: 'name', header: 'Name' },
    {
        accessorKey: 'created_at',
        header: 'Created Date',
        cell: ({ row }) => moment(row.getValue('created_at')).format('DD MMM YYYY, HH:mm'),
    },
    { id: 'actions' },
];

const columns = baseColumns.map(withSortHeader);

const globalFilter = ref('');
const sorting = ref<SortingState>([]);
const pagination = ref<PaginationState>({ pageIndex: 0, pageSize: pageSizes[0] });

const table = useTemplateRef<{ tableApi: Table<ProjectRoleListItem> }>('table');
const paginationRowModel = getPaginationRowModel<ProjectRoleListItem>();

const total = computed(() => table.value?.tableApi?.getFilteredRowModel().rows.length ?? 0);

const page = computed({
    get: () => pagination.value.pageIndex + 1,
    set: (value: number) => (pagination.value = { ...pagination.value, pageIndex: value - 1 }),
});

const pageSize = computed({
    get: () => pagination.value.pageSize,
    set: (value: number) => (pagination.value = { pageIndex: 0, pageSize: value }),
});

const getDropdownActions = (row: ProjectRoleListItem) => {
    const items: DropdownMenuItem[] = [];

    if (can('project-role.update')) {
        items.push({
            label: 'Edit',
            icon: 'i-lucide-pencil',
            onSelect() {
                router.visit(route('project-role.edit', row.id));
            },
        });
    }

    if (can('project-role.delete')) {
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

const handleDelete = async (row: ProjectRoleListItem) => {
    const confirmed = await confirm({
        title: 'Delete Project Role',
        description: `Are you sure want to delete ${row.name} project role?`,
    });

    if (confirmed) {
        // controller redirect sudah bawa flash "success"; AppLayout yang menampilkan toast-nya
        router.delete(route('project-role.destroy', row.id));
    }
};
</script>

<template>
    <div class="space-y-3">
        <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search Project Role" class="md:w-md" />

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
