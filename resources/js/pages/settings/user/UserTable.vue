<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { can } from '@/lib/utils';
import type { UserList } from '@/types';
import { router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import UButton from '@nuxt/ui/components/Button.vue';
import { getPaginationRowModel } from '@tanstack/vue-table';
import type { Column, PaginationState, SortingState, Table } from '@tanstack/vue-table';
import moment from 'moment';
import { computed, h, ref, useTemplateRef } from 'vue';

interface Props {
    data?: UserList[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const confirm = useConfirmDialog();
const toast = useToast();

const pageSizes = [10, 20, 50];

const globalFilter = ref('');
const sorting = ref<SortingState>([]);
const pagination = ref<PaginationState>({ pageIndex: 0, pageSize: pageSizes[0] });

const table = useTemplateRef<{ tableApi: Table<UserList> }>('table');
const paginationRowModel = getPaginationRowModel<UserList>();

const sortIcon = (direction: false | 'asc' | 'desc') => {
    if (direction === 'asc') return 'i-lucide-arrow-up';
    if (direction === 'desc') return 'i-lucide-arrow-down';
    return 'i-lucide-arrow-up-down';
};

const columns: TableColumn<UserList>[] = [
    { header: 'No', enableSorting: false, cell: ({ row }) => row.index + 1 },
    { accessorKey: 'team_name', header: 'Team', enableSorting: false },
    { accessorKey: 'role_label', header: 'Role', enableSorting: false },
    { accessorKey: 'name', header: 'Name', enableSorting: false },
    { accessorKey: 'email', header: 'Email', enableSorting: false },
    { accessorKey: 'is_active', header: 'Status', enableSorting: false },
    {
        accessorKey: 'created_at',
        header: ({ column }: { column: Column<UserList, unknown> }) =>
            h(UButton, {
                label: 'Created Date',
                trailingIcon: sortIcon(column.getIsSorted()),
                variant: 'ghost',
                color: 'neutral',
                size: 'sm',
                class: '-mx-2.5 font-medium',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            }),
        cell: ({ row }) => moment(row.getValue('created_at')).format('DD MMM YYYY, HH:mm'),
    },
    { accessorKey: 'created_by', header: 'Created By', enableSorting: false },
    { id: 'actions' },
];

const total = computed(() => table.value?.tableApi?.getFilteredRowModel().rows.length ?? 0);

const page = computed({
    get: () => pagination.value.pageIndex + 1,
    set: (value: number) => (pagination.value = { ...pagination.value, pageIndex: value - 1 }),
});

const pageSize = computed({
    get: () => pagination.value.pageSize,
    set: (value: number) => (pagination.value = { pageIndex: 0, pageSize: value }),
});

const getDropdownActions = (row: UserList) => {
    const items: DropdownMenuItem[] = [];

    if (can('user.update')) {
        items.push({
            label: 'Edit',
            icon: 'i-lucide-pencil',
            onSelect() {
                router.visit(route('user.edit', row.uuid));
            },
        });
    }

    if (can('user.delete')) {
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

const handleDelete = async (row: UserList) => {
    const confirmed = await confirm({
        title: 'Delete User',
        description: `Are you sure want to delete ${row.name}?`,
    });

    if (confirmed) {
        router.delete(route('user.destroy', row.uuid), {
            onSuccess() {
                toast.add({ title: 'Success', description: 'Success delete data', color: 'success' });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-3">
        <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search User" class="md:w-md" />

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
