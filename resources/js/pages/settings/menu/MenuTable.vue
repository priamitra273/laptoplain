<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { can } from '@/lib/utils';
import type { Menu } from '@/types';
import { router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import { computed, ref } from 'vue';
import { buildMenuTree, filterMenuTree, type MenuRow } from './menuTree';

interface Props {
    data?: Menu[];
}

const props = withDefaults(defineProps<Props>(), {
    data: () => [],
});

const emits = defineEmits<{
    (event: 'edit', value: Menu): void;
}>();

const confirm = useConfirmDialog();
const toast = useToast();

const columns: TableColumn<MenuRow>[] = [
    { accessorKey: 'label', header: 'Menu' },
    { accessorKey: 'route', header: 'Route' },
    { accessorKey: 'is_active', header: 'Status' },
    { id: 'actions' },
];

const globalFilter = ref('');
const pendingUuid = ref<string | null>(null);

const isFiltering = computed(() => globalFilter.value.trim().length > 0);

const rows = computed<MenuRow[]>(() => filterMenuTree(buildMenuTree(props.data), globalFilter.value));

const uriFor = (routeName?: string): string | undefined => {
    if (!routeName) {
        return undefined;
    }

    try {
        return route(routeName, undefined, false) as string;
    } catch {
        return undefined;
    }
};

const submit = (row: MenuRow, changes: { sequence_number?: number; is_active?: boolean }, failureMessage: string) => {
    pendingUuid.value = row.uuid;

    router.post(
        route('menu.update', row.uuid),
        {
            _method: 'PUT',
            label: row.label,
            parent_uuid: row.parent_uuid ?? null,
            icon: row.icon,
            route_name: row.route ?? null,
            sequence_number: row.sequence_number,
            is_active: row.is_active,
            ...changes,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => toast.add({ title: 'Failed', description: failureMessage, color: 'error' }),
            onFinish: () => (pendingUuid.value = null),
        },
    );
};

const move = (row: MenuRow, targetSequence: number) => {
    submit(row, { sequence_number: targetSequence }, 'Could not save the new order');
};

const toggleActive = (row: MenuRow, value: boolean) => {
    submit(row, { is_active: value }, `Could not change status of ${row.label}`);
};

const getDropdownActions = (row: MenuRow) => {
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

const handleDelete = async (row: MenuRow) => {
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
            <UTable :data="rows" :columns="columns" :ui="{ tr: 'group/row' }">
                <template #label-cell="{ row }">
                    <div class="flex items-center gap-2.5">
                        <span
                            v-if="row.original.depth > 0"
                            aria-hidden="true"
                            class="ms-2 h-4 w-3 shrink-0 rounded-bl border-b border-l border-default"
                        />
                        <Icon :name="row.original.icon" class="size-4 shrink-0 text-muted" />
                        <span :class="row.original.depth === 0 ? 'font-medium' : ''">{{ row.original.label }}</span>
                    </div>
                </template>

                <template #route-cell="{ row }">
                    <div v-if="row.original.route" class="leading-tight">
                        <span v-if="uriFor(row.original.route)" class="font-mono text-xs">{{ uriFor(row.original.route) }}</span>
                        <span v-else class="text-xs text-error">Route is not registered</span>
                        <span class="block text-xs text-muted">{{ row.original.route }}</span>
                    </div>
                    <span v-else class="text-xs text-muted">Group, no page</span>
                </template>

                <template #is_active-cell="{ row }">
                    <USwitch
                        :model-value="row.original.is_active"
                        :disabled="pendingUuid === row.original.uuid || !can('menu.update')"
                        :aria-label="`Status of ${row.original.label}`"
                        @update:model-value="toggleActive(row.original, $event)"
                    />
                </template>

                <template #actions-cell="{ row }">
                    <div class="flex items-center justify-end gap-0.5">
                        <div
                            v-if="can('menu.update')"
                            class="flex items-center opacity-100 transition-opacity md:opacity-0 md:group-focus-within/row:opacity-100 md:group-hover/row:opacity-100"
                        >
                            <UButton
                                icon="i-lucide-chevron-up"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                :disabled="isFiltering || row.original.previousSequence === undefined || pendingUuid === row.original.uuid"
                                :aria-label="`Move ${row.original.label} up`"
                                @click="move(row.original, row.original.previousSequence!)"
                            />
                            <UButton
                                icon="i-lucide-chevron-down"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                :disabled="isFiltering || row.original.nextSequence === undefined || pendingUuid === row.original.uuid"
                                :aria-label="`Move ${row.original.label} down`"
                                @click="move(row.original, row.original.nextSequence!)"
                            />
                        </div>

                        <UDropdownMenu :items="getDropdownActions(row.original)" :content="{ align: 'end', side: 'bottom' }">
                            <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="sm" aria-label="Actions" />
                        </UDropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 py-10 text-center">
                        <template v-if="isFiltering">
                            <p class="text-sm font-medium">No menu matches "{{ globalFilter }}"</p>
                            <UButton size="sm" color="neutral" variant="subtle" @click="globalFilter = ''">Clear search</UButton>
                        </template>
                        <template v-else>
                            <Icon name="List" class="size-6 text-muted" />
                            <p class="text-sm font-medium">No menu yet</p>
                            <p class="max-w-xs text-sm text-muted">Add a menu to start building the sidebar navigation.</p>
                        </template>
                    </div>
                </template>
            </UTable>
        </UCard>

        <p v-if="rows.length" class="text-xs text-muted">{{ rows.length }} menu shown</p>
    </div>
</template>
