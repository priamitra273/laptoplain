<script setup lang="ts">
import { toLucideIcon } from '@/lib/menu';
import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { computed } from 'vue';

interface TaskCategory {
    name: string;
    icon?: string | null;
    severity: PrimeSeverity | null;
}

const props = withDefaults(
    defineProps<{
        category: TaskCategory;
        showLabel?: boolean;
    }>(),
    {
        showLabel: true,
    },
);

const icon = computed(
    () => toLucideIcon(props.category.icon) ?? 'i-lucide-tag',
);
</script>

<template>
    <UTooltip :text="category.name">
        <UBadge
            :color="severityColor(category.severity)"
            variant="subtle"
            size="sm"
            :aria-label="category.name"
            class="gap-1"
        >
            <UIcon
                :name="icon"
                class="size-3.5 shrink-0"
                aria-hidden="true"
            />

            <span v-if="showLabel">{{ category.name }}</span>
        </UBadge>
    </UTooltip>
</template>