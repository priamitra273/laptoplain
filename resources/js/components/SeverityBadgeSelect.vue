<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { toLucideIcon } from '@/lib/menu';
import { severityColor, severityDotClass } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { computed } from 'vue';

interface SeverityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
    icon?: string | null;
}

interface Props {
    modelValue?: string;
    items: SeverityOption[];
    placeholder?: string;
    disabled?: boolean;
    ui?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select an option',
});

defineEmits<{
    'update:modelValue': [value: string];
}>();

const selected = computed(() => props.items.find((item) => item.id === props.modelValue));
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
            <StatusBadge v-else :label="selected?.name" :severity="selected?.severity" :icon="toLucideIcon(selected?.icon)" />
        </template>

        <template #item-leading="{ item }">
            <UIcon v-if="item.icon" :name="toLucideIcon(item.icon)" class="my-0.5 size-4" :class="`text-${severityColor(item.severity)}`" />
            <span v-else class="flex h-5 w-4 shrink-0 items-center justify-center">
                <span class="size-1.5 rounded-full" :class="severityDotClass(item.severity)" />
            </span>
        </template>
    </USelectMenu>
</template>
