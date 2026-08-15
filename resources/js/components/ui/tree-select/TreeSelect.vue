<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import type { TreeSelectNode, TreeSelectProps } from './types';
import { branchKeys, buildNodeMap, buildParentMap, filterTree, pathToKeys, resolveFilterFields, toKeyArray } from './utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(defineProps<TreeSelectProps>(), {
    options: () => [],
    selectionMode: 'single',
    placeholder: 'Pilih…',
    emptyMessage: 'Tidak ada pilihan',
    filterMode: 'lenient',
    filterPlaceholder: 'Cari…',
    color: 'neutral',
    variant: 'outline',
    trailingIcon: 'i-lucide-chevron-down',
    clearIcon: 'i-lucide-x',
});

const emit = defineEmits<{ 'update:modelValue': [value: string | string[] | undefined] }>();

// useFormField takes the raw props on purpose: it has to tell an explicitly set prop from a
// default, otherwise the values coming from UFormField get overwritten.
const { id, size, color, disabled, emitFormChange, emitFormBlur, emitFormFocus, ariaAttrs } = useFormField<TreeSelectProps>(props);

const open = ref(false);
const filterText = ref('');
const expanded = defineModel<string[]>('expanded', { default: () => [] });

const isMultiple = computed(() => props.selectionMode !== 'single');
const isCheckbox = computed(() => props.selectionMode === 'checkbox');

const nodeMap = computed(() => buildNodeMap(props.options));
const parentMap = computed(() => buildParentMap(props.options));

const selectedKeys = computed(() => toKeyArray(props.modelValue));
// ponytail: a key missing from `options` is silently dropped here, and with it from the next
// emitted value. Keep both fed from the same source, or resolve the label server-side.
const selectedNodes = computed(() =>
    selectedKeys.value.map((key) => nodeMap.value[key]).filter((node): node is TreeSelectNode => node !== undefined),
);

const hasSelection = computed(() => selectedNodes.value.length > 0);
const label = computed(() => selectedNodes.value.map((node) => node.label ?? node.key).join(', '));

const visibleNodes = computed(() => {
    if (!props.filter) return props.options;
    return filterTree(props.options, {
        text: filterText.value,
        fields: resolveFilterFields(props.filterBy),
        strict: props.filterMode === 'strict',
        locale: props.filterLocale,
    });
});

/**
 * reka's Tree holds node objects rather than keys, and compares them through `getKey`.
 * The cast is what lets one component serve both modes: `UTree` derives its modelValue type
 * from a literal `multiple`, so a dynamic boolean collapses it to the single-item branch.
 */
const treeValue = computed(() => (isMultiple.value ? selectedNodes.value : selectedNodes.value[0]) as TreeSelectNode);

const getNodeKey = (node: TreeSelectNode) => node.key;

function commit(value: TreeSelectNode | TreeSelectNode[] | undefined) {
    const nodes = value === undefined ? [] : Array.isArray(value) ? value : [value];
    const keys = nodes.map((node) => node.key);

    emit('update:modelValue', isMultiple.value ? keys : keys[0]);
    emitFormChange();

    // One click on a tree row fires `select` *and* `toggle`, so closing on any single-mode
    // selection would kill expansion. Only a leaf ends the interaction.
    const node = nodes[0];
    if (!isMultiple.value && node && !node.children?.length) open.value = false;
}

function clear() {
    emit('update:modelValue', isMultiple.value ? [] : undefined);
    emitFormChange();
}

const isDisabled = computed(() => Boolean(disabled.value || props.loading));
const canClear = computed(() => Boolean(props.showClear) && hasSelection.value && !isDisabled.value);

const popoverContent = computed(() => ({
    align: 'start' as const,
    ...props.content,
    class: ['min-w-(--reka-popover-trigger-width)', props.content?.class],
}));

/**
 * A UCheckbox is reka's CheckboxRoot, which always renders a `<button>`, so the tree row
 * cannot stay one. Only checkbox mode pays for the swap; the other modes keep the native
 * button. reka drives every click and keypress on the row itself, so `role="treeitem"` on a
 * div loses nothing.
 */
const treeAs = computed(() => (isCheckbox.value ? { link: 'div' } : undefined));

// reka only computes `indeterminate` for parents in a propagating tree; elsewhere it is undefined.
function checkboxState(selected: boolean, indeterminate: boolean | undefined) {
    return indeterminate ? ('indeterminate' as const) : selected;
}

watch(open, (value) => {
    if (!value) {
        filterText.value = '';
        return;
    }

    // Reveal every stored value when the panel opens, the way PrimeVue's expandPath does.
    const next = new Set(expanded.value);
    for (const key of selectedKeys.value) {
        for (const ancestor of pathToKeys(key, parentMap.value)) next.add(ancestor);
    }
    if (next.size !== expanded.value.length) expanded.value = [...next];
});

// Without this every match stays hidden behind a parent that is still collapsed.
watch(filterText, (text) => {
    if (text.trim()) expanded.value = branchKeys(visibleNodes.value);
});
</script>

<template>
    <UPopover v-model:open="open" :content="popoverContent" :ui="{ content: 'p-1' }">
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
                <div class="flex items-center gap-2">
                    <UIcon
                        v-if="canClear"
                        :name="clearIcon"
                        role="button"
                        aria-label="Kosongkan pilihan"
                        class="size-4 text-dimmed hover:text-default"
                        @pointerdown.stop.prevent="clear"
                    />
                    <UIcon :name="trailingIcon" class="size-4 text-dimmed" />
                </div>
            </template>
        </UButton>

        <template #content>
            <!-- reka's PopoverContent focuses the first focusable child, so the search box takes
           focus on open and the first tree row takes it when there is none. -->
            <UInput
                v-if="filter"
                v-model="filterText"
                :placeholder="filterPlaceholder"
                icon="i-lucide-search"
                size="sm"
                autocomplete="off"
                aria-label="Cari"
                class="mb-1 w-full"
            />

            <UTree
                v-if="visibleNodes.length"
                :items="visibleNodes"
                :as="treeAs"
                :get-key="getNodeKey"
                :model-value="treeValue"
                :multiple="isMultiple"
                :propagate-select="isCheckbox"
                :bubble-select="isCheckbox"
                :size="size"
                :expanded="expanded"
                class="max-h-72 overflow-y-auto"
                @update:model-value="commit"
                @update:expanded="expanded = $event"
            >
                <template v-if="isCheckbox" #item-leading="{ item, selected, indeterminate, handleSelect }">
                    <!-- The row itself already carries the state as aria-selected, so this checkbox is
               a duplicate and stays hidden from assistive tech. click.stop: the row selects
               on click too, and without it the two would toggle each other back.
               mousedown.prevent keeps focus on the row, which is what reka's roving focus
               tracks — and keeps an aria-hidden element from ever holding focus. -->
                    <UCheckbox
                        :model-value="checkboxState(selected, indeterminate)"
                        :size="size"
                        :disabled="item.disabled"
                        tabindex="-1"
                        aria-hidden="true"
                        class="shrink-0"
                        @click.stop
                        @mousedown.prevent
                        @update:model-value="handleSelect"
                    />
                    <UIcon v-if="item.icon" :name="item.icon" class="size-4 shrink-0" />
                </template>
            </UTree>

            <p v-else class="px-3 py-4 text-center text-sm text-dimmed">{{ emptyMessage }}</p>
        </template>
    </UPopover>
</template>
