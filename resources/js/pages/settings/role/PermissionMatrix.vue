<script setup lang="ts">
import TreeTable from '@/components/ui/TreeTable.vue';
import { toLucideIcon } from '@/lib/menu';
import type { TableColumn } from '@nuxt/ui';
import type { ExpandedState } from '@tanstack/vue-table';
import { ref } from 'vue';
import {
    columnState,
    fromPermissionNames,
    matrixState,
    nodeState,
    PERMISSION_ACTIONS,
    rowState,
    toggleAction,
    toggleAll,
    toPermissionNames,
    type CheckState,
    type MenuNode,
    type MenuPermission,
    type PermissionAction,
} from './permissions';

const props = defineProps<{
    menu: MenuNode[];
    menuPermissions: MenuPermission[];
}>();

const model = defineModel<string[]>({ required: true });

const selected = ref(fromPermissionNames(model.value, props.menuPermissions));
const expanded = ref<ExpandedState>(true);

const actionLabels: Record<PermissionAction, string> = {
    read: 'View',
    create: 'Create',
    update: 'Update',
    delete: 'Delete',
};

const centered = { class: { th: 'text-center', td: 'text-center' } };

const columns: TableColumn<MenuNode>[] = [
    { id: 'label', header: 'Menu' },
    ...PERMISSION_ACTIONS.map((action) => ({ id: action, meta: centered })),
    { id: 'row', meta: centered },
];

const sync = (): void => {
    model.value = toPermissionNames(props.menu, selected.value, props.menuPermissions);
};

const onToggleAction = (nodes: MenuNode[], action: PermissionAction, value: CheckState): void => {
    toggleAction(nodes, action, value === true, selected.value);
    sync();
};

const onToggleAll = (nodes: MenuNode[], value: CheckState): void => {
    toggleAll(nodes, value === true, selected.value);
    sync();
};
</script>

<template>
    <UCard title="Role's Permissions" description="Please choose at least one permission."
        :ui="{ body: 'p-0 sm:py-0' }">
        <TreeTable v-model:expanded="expanded" :data="menu" :columns="columns" empty="No menu available.">
            <template #label-cell="{ row }">
                <UIcon v-if="row.original.data.icon" :name="toLucideIcon(row.original.data.icon)!"
                    class="size-4 shrink-0 text-muted" />
                <span>{{ row.original.data.label }}</span>
            </template>

            <template v-for="action in PERMISSION_ACTIONS" :key="`${action}-header`" #[`${action}-header`]>
                <div class="flex flex-col items-center gap-1">
                    <span class="text-xs font-medium">{{ actionLabels[action] }}</span>
                    <UCheckbox :model-value="columnState(menu, action, selected)"
                        :aria-label="`Select all ${actionLabels[action]}`"
                        @update:model-value="onToggleAction(menu, action, $event)" />
                </div>
            </template>

            <template v-for="action in PERMISSION_ACTIONS" :key="`${action}-cell`" #[`${action}-cell`]="{ row }">
                <UCheckbox :model-value="nodeState(row.original, action, selected)"
                    :aria-label="`${actionLabels[action]} ${row.original.data.label}`" class="justify-center"
                    @update:model-value="onToggleAction([row.original], action, $event)" />
            </template>

            <template #row-header>
                <div class="flex justify-center items-end h-full p-1.5">
                    <UCheckbox :model-value="matrixState(menu, selected)" aria-label="Select all permissions"
                        @update:model-value="onToggleAll(menu, $event)" />
                </div>
            </template>

            <template #row-cell="{ row }">
                <UCheckbox :model-value="rowState(row.original, selected)"
                    :aria-label="`Select all permissions for ${row.original.data.label}`" class="justify-center"
                    @update:model-value="onToggleAll([row.original], $event)" />
            </template>
        </TreeTable>
    </UCard>
</template>
