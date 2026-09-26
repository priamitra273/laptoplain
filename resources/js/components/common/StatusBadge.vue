<script setup lang="ts">
import { severityColor, severityDotClass } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string | null | undefined;
        severity: PrimeSeverity | null | undefined;
        icon?: string | null;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    { size: 'sm' },
);

const dotClass = computed(() => severityDotClass(props.severity));
const iconColorClass = computed(() => `text-${severityColor(props.severity)}`);
</script>

<template>
    <UBadge v-if="label" color="neutral" variant="subtle" :size="size">
        <template #leading>
            <UIcon v-if="icon" :name="icon" class="size-4" :class="iconColorClass" />
            <span v-else class="size-1.5 shrink-0 rounded-full" :class="dotClass" />
        </template>

        {{ label }}
    </UBadge>
</template>
