<script setup lang="ts" generic="T extends object">
// Table server-driven: sorting & pagination dikendalikan parent (lewat query URL),
// bukan TanStack client-side seperti DataTable.vue. Niru persis pola yang sudah
// terbukti dipakai favorites/project/Table.vue.
import { computed, h, useSlots } from 'vue';
import type { TableColumn, TableRow } from '@nuxt/ui';
import UButton from '@nuxt/ui/components/Button.vue';

const props = withDefaults(
    defineProps<{
        data: T[];
        columns: TableColumn<T>[];
        loading?: boolean;
        empty?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        total: number;
        pageSizes?: number[];
        resultLabel?: string;
        tableBaseClass?: string;
        tableUi?: Record<string, string>;
        // Lewat prop, bukan emit: UTable menandai baris `data-selectable` begitu
        // `onSelect` ada, jadi tabel yang barisnya tidak bisa diklik tidak kena efeknya.
        onRowSelect?: (event: Event, row: TableRow<T>) => void;
    }>(),
    {
        empty: 'Tidak ada data.',
        pageSizes: () => [10, 25, 50],
        resultLabel: 'results',
        tableBaseClass: 'w-full',
    },
);

const emit = defineEmits<{
    sort: [column: string];
}>();

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

const resultRange = computed(() => ({
    start: props.total === 0 ? 0 : (page.value - 1) * perPage.value + 1,
    end: Math.min(page.value * perPage.value, props.total),
}));

const pageSizeItems = computed(() => props.pageSizes.map((size) => ({ label: `${size} / page`, value: size })));

// Header teks biasa (tanpa ikon panah) — kolom yang lagi aktif dibedakan lewat
// warna+tebal teks. Kolom bisa override id sort-nya sendiri (mis. accessorKey
// "status" tapi kolom DB-nya "status_id") lewat `column.id`.
const withSortHeader = (column: TableColumn<T>): TableColumn<T> => {
    if (column.enableSorting === false || typeof column.header !== 'string') return column;

    const label = column.header;
    const id = column.id ?? ('accessorKey' in column ? String(column.accessorKey) : '');

    return {
        ...column,
        header: () => {
            const isSorted = props.sort === id;

            return h(UButton, {
                label,
                variant: 'ghost',
                color: isSorted ? 'primary' : 'neutral',
                size: 'sm',
                disabled: props.loading,
                class: ['-mx-2.5', isSorted ? 'font-semibold' : 'font-medium'],
                onClick: () => emit('sort', id),
            });
        },
    } as TableColumn<T>;
};

const columns = computed(() => props.columns.map(withSortHeader));

const slots = useSlots();

// `footer-meta` dirender di baris footer, jadi tidak boleh ikut diteruskan ke UTable.
const tableSlotNames = computed(() => Object.keys(slots).filter((name) => name !== 'footer-meta'));
</script>

<template>
    <div class="overflow-hidden rounded-md ring ring-default">
        <UTable :data="data" :columns="columns" :loading="loading" :empty="empty" :ui="{ base: tableBaseClass, ...tableUi }" :on-select="onRowSelect"
            ><template v-for="name in tableSlotNames" :key="name" #[name]="slotProps">
                <slot :name="name" v-bind="slotProps ?? {}" />
            </template>
        </UTable>
        <div class="flex flex-col gap-3 border-t border-default px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">Showing {{ resultRange.start }}-{{ resultRange.end }} of {{ total }} {{ resultLabel }}</p>

            <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                <slot name="footer-meta" />
                <USelect
                    v-model="perPage"
                    :items="pageSizeItems"
                    label-key="label"
                    value-key="value"
                    color="neutral"
                    variant="outline"
                    class="w-28"
                />
                <UPagination v-model:page="page" :items-per-page="perPage" :total="total" size="sm" />
            </div>
        </div>
    </div>
</template>
