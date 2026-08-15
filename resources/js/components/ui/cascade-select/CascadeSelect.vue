<script setup lang="ts" generic="T = unknown">
import { computed } from 'vue';
import type { DropdownMenuItem } from '@nuxt/ui';

import type { CascadeSelectProps } from './types';
import { buildCascadeNodes, findCascadePath, toMenuItems } from './utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(defineProps<CascadeSelectProps<T>>(), {
    options: () => [],
    placeholder: 'Pilih…',
    emptyMessage: 'Tidak ada pilihan',
    color: 'neutral',
    variant: 'outline',
    trailingIcon: 'i-lucide-chevron-down',
    clearIcon: 'i-lucide-x',
});

const emit = defineEmits<{ 'update:modelValue': [value: T | undefined] }>();

// useFormField sengaja menerima props mentah: ia harus bisa membedakan prop yang
// diisi eksplisit dari default, supaya nilai dari UFormField tidak tertimpa.
const { id, size, color, disabled, emitFormChange, emitFormBlur, emitFormFocus, ariaAttrs } = useFormField<CascadeSelectProps<T>>(props);

const nodes = computed(() => buildCascadeNodes(props.options ?? [], props));
const path = computed(() => findCascadePath(nodes.value, props.modelValue));
const selected = computed(() => path.value[path.value.length - 1]);

const label = computed(() => selected.value?.label ?? '');
const hasSelection = computed(() => path.value.length > 0);

const items = computed<DropdownMenuItem[]>(() => {
    if (nodes.value.length === 0) return [{ type: 'label' as const, label: props.emptyMessage }];

    const pathKeys = new Set(path.value.slice(0, -1).map((node) => node.key));
    // Nilai leaf lahir dari accessor, jadi tipenya cuma bisa dijanjikan lewat T.
    return toMenuItems(nodes.value, selected.value?.key, pathKeys, (node) => commit(node.value as T));
});

function commit(value: T | undefined) {
    emit('update:modelValue', value);
    emitFormChange();
}

const isDisabled = computed(() => Boolean(disabled.value || props.loading));
const canClear = computed(() => Boolean(props.showClear) && hasSelection.value && !isDisabled.value);

const menuContent = computed(() => ({
    align: 'start' as const,
    ...props.content,
    // Lebar minimum selebar trigger hanya untuk panel akar. Kalau ditaruh di `ui.content`,
    // submenu ikut mewarisinya — dan "trigger" sebuah submenu adalah baris menu induknya,
    // jadi lebarnya terkunci selebar panel induk.
    class: ['min-w-(--reka-dropdown-menu-trigger-width)', props.content?.class],
}));
</script>

<template>
    <UDropdownMenu :items="items" :size="size" :disabled="isDisabled" :content="menuContent" :ui="{ content: 'max-h-80 overflow-y-auto min-w-36' }">
        <UButton
            :id="id"
            :size="size"
            :color="color"
            :variant="variant"
            :loading="loading"
            :disabled="disabled"
            class="justify-between"
            :class="props.class"
            v-bind="{ ...ariaAttrs, ...$attrs }"
            @blur="emitFormBlur"
            @focus="emitFormFocus"
        >
            <span class="truncate" :class="{ 'text-dimmed': !hasSelection }">
                {{ hasSelection ? label : placeholder }}
            </span>

            <template #trailing>
                <!-- span (UIcon), bukan button: tombol bersarang di dalam tombol bukan HTML
             yang valid. pointerdown dihentikan supaya mengosongkan nilai tidak ikut
             membuka overlay — trigger reka bereaksi pada pointerdown, bukan click. -->
                <UIcon
                    v-if="canClear"
                    :name="clearIcon"
                    role="button"
                    aria-label="Kosongkan pilihan"
                    class="size-4 text-dimmed hover:text-default"
                    @pointerdown.stop.prevent="commit(undefined)"
                />
                <UIcon :name="trailingIcon" class="size-4 text-dimmed" />
            </template>
        </UButton>
    </UDropdownMenu>
</template>
