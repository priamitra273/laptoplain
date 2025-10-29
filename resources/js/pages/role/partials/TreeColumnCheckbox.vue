<script setup lang="ts">
import { computed } from 'vue';
import { CheckboxState, TreeCheckboxEvent } from '../type';
import { PassThrough } from '@primevue/core';
import { TreeNode } from 'primevue/treenode';
import { ColumnPassThroughOptions } from 'primevue/column';
import TreeCheckbox from './TreeCheckbox.vue';

interface Props {
    modelValue: Record<string, CheckboxState>;
    nodes: TreeNode[];
    totalNode: number;
    inputName?: string;
    inputHeaderName?: string;
    header?: string;
}

interface Emits {
    (event: 'update:modelValue', value: Record<string, CheckboxState>): void;
    (event: 'checkbox-change', value: TreeCheckboxEvent): void;
    (event: 'checkbox-header-change', value: Event): void;
}

const props = defineProps<Props>();
const emits = defineEmits<Emits>();

const selectedKeys = computed({
    get: () => props.modelValue,
    set: (value) => emits('update:modelValue', value)
});

const passThroughOption: PassThrough<ColumnPassThroughOptions> = {
    columnHeaderContent: {
        class: 'justify-center'
    },
    bodyCellContent: {
        class: 'justify-center'
    },
}

const countSelected = computed<number>(() => {
    return Object.values(selectedKeys.value).filter((item) => item.checked).length;
})

const countPartialSelected = computed<number>(() => {
    return Object.values(selectedKeys.value).filter((item) => item.partialChecked).length;
})

const checkedHeader = computed<boolean>(() => {
    return countSelected.value > 0 && countSelected.value === props.totalNode;
})

const partialCheckedHeader = computed(() => {
    return (countPartialSelected.value > 0) || countSelected.value > 0 && countSelected.value !== props.totalNode;
});

const onChangeHeaderCheckbox = (event: Event) => {
    const check = (event.target as HTMLInputElement).checked

    if (check) selectHeaderCheckbox(props.nodes, selectedKeys.value)
    else selectedKeys.value = {}

    emits('checkbox-header-change', event)
}

const selectHeaderCheckbox = (nodes: TreeNode[], _selectedKeys: Record<string, CheckboxState>): void => {
    for (const node of nodes) {
        _selectedKeys[node.key] = { checked: true, partialChecked: false }

        if (node.children && node.children?.length > 0) {
            selectHeaderCheckbox(node.children, _selectedKeys)
        }
    }
}
</script>

<template>
    <Column :pt="passThroughOption">
        <template #header>
            <span v-if="header" class="p-treetable-column-title" data-pc-section="columntitle">{{ header }}</span>
            <Checkbox :name="inputHeaderName" :model-value="checkedHeader" :indeterminate="partialCheckedHeader" binary @change="onChangeHeaderCheckbox" />
        </template>

        <template #body="{ node, column }">
            <TreeCheckbox v-model="selectedKeys" :input-name="inputName" :node="node" :nodes="props.nodes" :column="column" @checkbox-change="$emit('checkbox-change', $event)"/>
        </template>
    </Column>
</template>