<script setup lang="ts">
import { ColumnNode } from 'primevue/column';
import { computed } from 'vue';
import { CheckboxState, TreeCheckboxEvent } from '../type';
import { nodePropagateDown, nodePropagateUp } from '../function';

interface Props {
    node: any;
    nodes: any[];
    column: ColumnNode;
    modelValue: Record<string, CheckboxState>;
    inputName?: string;
}

const props = defineProps<Props>()

const emits = defineEmits<{
    (event: 'update:modelValue', value: Record<string, CheckboxState>): void;
    (event: 'checkbox-change', value: TreeCheckboxEvent): void;
}>()

const checked = computed<boolean>(() => {
    return props.modelValue[props.node.key] ? props.modelValue[props.node.key] && props.modelValue[props.node.key].checked : false;
})

const partialChecked = computed<boolean>(() => {
    return props.modelValue[props.node.key] ? props.modelValue[props.node.key] && props.modelValue[props.node.key].partialChecked : false;
})

const toggleChange = (event: Event) => {
    const _check = !checked.value
    const _selectionKeys = props.modelValue ? { ...props.modelValue } : {}

    nodePropagateDown(props.node, _check, _selectionKeys)
    nodePropagateUp(props.node, _check, _selectionKeys, props.nodes)

    emits('update:modelValue', _selectionKeys)
    emits('checkbox-change', { ...event, node: props.node, target: event.target, checked: _check })
}
</script>

<template>
    <Checkbox :name="inputName" :model-value="checked" :indeterminate="partialChecked" @change="toggleChange" binary />
</template>