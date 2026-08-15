<script setup lang="ts" generic="T extends Record<string, unknown>">
// UTable already has getSubRows and getExpandedRowModel, so all that is left is
// per-level indentation and a toggle button in the marker column.
import { computed, useSlots, useTemplateRef } from 'vue';
import { FlexRender } from '@tanstack/vue-table';
import type { ExpandedState, Row, Table } from '@tanstack/vue-table';
import type { TableColumn } from '@nuxt/ui';

const props = withDefaults(
    defineProps<{
        data: T[];
        columns: TableColumn<T>[];
        /** Column that carries the chevron and the indentation. Defaults to the first column. */
        expandColumn?: string;
        getSubRows?: (row: T) => T[] | undefined;
        /** Indentation width per level, in rem. */
        indent?: number;
        loading?: boolean;
        empty?: string;
        getRowId?: (row: T, index: number, parent?: Row<T>) => string;
    }>(),
    {
        getSubRows: (row: T) => row.children as T[] | undefined,
        indent: 1.25,
        empty: 'Tidak ada data.',
    },
);

// An initial value is required: UTable only wires onExpandedChange when the model
// is not undefined at setup. Set it to `true` to expand every row.
const expanded = defineModel<ExpandedState>('expanded', { default: () => ({}) });

const table = useTemplateRef<{ tableApi: Table<T> }>('table');
const tableApi = computed(() => table.value?.tableApi);
defineExpose({ tableApi });

const columnId = (column: TableColumn<T>) => column.id ?? ('accessorKey' in column ? String(column.accessorKey) : '');

// A caller's slot wins over the columnDef render function, so the chevron has to go
// through a slot too — otherwise a caller's #<id>-cell would erase it.
const expandSlot = computed(() => {
    const first = props.columns[0];
    return `${props.expandColumn ?? (first ? columnId(first) : '')}-cell`;
});

const slots = useSlots();
const forwardedSlots = computed(() => Object.keys(slots).filter((name) => name !== expandSlot.value));
</script>

<template>
    <UTable
        ref="table"
        v-model:expanded="expanded"
        :data="data"
        :columns="columns"
        :get-sub-rows="getSubRows"
        :get-row-id="getRowId"
        :loading="loading"
        :empty="empty"
        :ui="{
            // UTable inserts a detail-panel <tr> under every expanded row even without an
            // #expanded slot. For a tree that is just an empty row — spotted by its lone, empty td.
            tr: 'has-[>td:only-child:empty]:hidden',
        }"
    >
        <template #[expandSlot]="ctx">
            <div class="flex items-center gap-1" :style="{ paddingInlineStart: `${ctx.row.depth * indent}rem` }">
                <UButton
                    v-if="ctx.row.getCanExpand()"
                    :icon="ctx.row.getIsExpanded() ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    square
                    :aria-label="ctx.row.getIsExpanded() ? 'Tutup' : 'Buka'"
                    @click="ctx.row.toggleExpanded()"
                />
                <span v-else class="size-6 shrink-0" />
                <slot :name="expandSlot" v-bind="ctx">
                    <FlexRender :render="ctx.column.columnDef.cell" :props="ctx" />
                </slot>
            </div>
        </template>

        <template v-for="name in forwardedSlots" :key="name" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps ?? {}" />
        </template>
    </UTable>
</template>
