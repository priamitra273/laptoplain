<script setup lang="ts">
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import { priorityIcon, severityColor, severityDotClass } from '@/lib/utils';
import { toLucideIcon } from '@/lib/menu';
import type { PrimeSeverity } from '@/types';
import { computed } from 'vue';

interface BadgeOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
    icon?: string | null;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        items: BadgeOption[];
        display?: 'status' | 'priority';
        placeholder?: string;
        disabled?: boolean;
        pill?: boolean;
        ui?: Record<string, string>;
    }>(),
    { display: 'status', placeholder: 'Select an option' },
);

defineEmits<{ 'update:modelValue': [value: string] }>();
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
            <PriorityIcon v-else-if="display === 'priority'" :label="selected?.name" :severity="selected?.severity" :pill="pill" />
            <StatusBadge v-else :label="selected?.name" :severity="selected?.severity" :icon="toLucideIcon(selected?.icon)" />
        </template>
        <template #item-leading="{ item }">
            <template v-if="display === 'priority'">
                <UIcon :name="priorityIcon(item.name)" class="size-4" :class="`text-${severityColor(item.severity)}`" />
            </template>
            <template v-else>
                <UIcon v-if="item.icon" :name="toLucideIcon(item.icon)" class="my-0.5 size-4" :class="`text-${severityColor(item.severity)}`" />
                <span v-else class="flex h-5 w-4 shrink-0 items-center justify-center"
                    ><span class="size-1.5 rounded-full" :class="severityDotClass(item.severity)"
                /></span>
            </template>
        </template>
        <template v-if="display === 'priority'" #item-label="{ item }">
            <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
        </template>
    </USelectMenu>
</template>
