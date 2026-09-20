<script setup lang="ts">
import PriorityIcon from '@/components/PriorityIcon.vue';
import { priorityIcon, severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface Props {
    modelValue?: string;
    items: PriorityOption[];
    placeholder?: string;
    disabled?: boolean;
    pill?: boolean;
    ui?: Record<string, string>;
}

withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
});

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <USelectMenu
        :model-value="modelValue"
        :items="items"
        value-key="id"
        label-key="name"
        :disabled="disabled"
        :ui="ui"
        @update:model-value="(value: string) => $emit('update:modelValue', value)"
    >
        <template #default>
            <span v-if="!modelValue" class="text-dimmed">{{ placeholder }}</span>
            <PriorityIcon
                v-else
                :label="items.find((item) => item.id === modelValue)?.name"
                :severity="items.find((item) => item.id === modelValue)?.severity"
                :pill="pill"
            />
        </template>

        <template #item-leading="{ item }">
            <UIcon :name="priorityIcon(item.name)" class="size-4" :class="`text-${severityColor(item.severity)}`" />
        </template>

        <template #item-label="{ item }">
            <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
        </template>
    </USelectMenu>
</template>
