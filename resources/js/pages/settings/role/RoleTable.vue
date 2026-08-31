<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { can } from '@/lib/utils';
import type { RoleList } from '@/types';
import { router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import moment from 'moment';
import { ref } from 'vue';

interface Props {
    data?: RoleList[];
}

withDefaults(defineProps<Props>(), {
    data: () => [],
});

const confirm = useConfirmDialog();
const toast = useToast();

const columns: TableColumn<RoleList>[] = [
    { header: 'No', cell: ({ row }) => row.index + 1 },
    { accessorKey: 'team_name', header: 'Team' },
    { accessorKey: 'label', header: 'Name' },
    { accessorKey: 'is_active', header: 'Status' },
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => moment(row.getValue('created_at')).format('DD MMM YYYY, HH:mm'),
    },
    { id: 'actions' },
];

const globalFilter = ref('');

const getDropdownActions = (row: RoleList) => {
    const items: DropdownMenuItem[] = [];

    if (can('role.update')) {
        items.push({
            label: 'Edit',
            icon: 'i-lucide-pencil',
            onSelect() {
                router.visit(route('role.edit', row.id));
            },
        });
    }

    if (can('role.delete')) {
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

const handleDelete = async (row: RoleList) => {
    const confirmed = await confirm({
        title: 'Delete Role',
        description: `Are you sure want to delete ${row.label} role?`,
    });

    if (confirmed) {
        router.delete(route('role.destroy', row.id), {
            onSuccess() {
                toast.add({ title: 'Success', description: 'Success delete data', color: 'success' });
            },
        });
    }
};
</script>

<template>
    <div class="space-y-3">
        <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search Role" class="md:w-md" />

        <UTable v-model:global-filter="globalFilter" :data="data" :columns="columns"
            class="rounded-lg border border-default">
            <template #is_active-cell="{ row }">
                <UBadge :color="row.original.is_active ? 'success' : 'error'" variant="subtle">
                    {{ row.original.is_active ? 'Active' : 'Nonactive' }}
                </UBadge>
            </template>

            <template #actions-cell="{ row }">
                <div class="flex justify-end">
                    <UDropdownMenu :items="getDropdownActions(row.original)"
                        :content="{ align: 'end', side: 'bottom' }">
                        <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost"
                            aria-label="Actions" />
                    </UDropdownMenu>
                </div>
            </template>
        </UTable>
    </div>
</template>
