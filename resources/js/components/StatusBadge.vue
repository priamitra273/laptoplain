<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string | null | undefined;
        severity: PrimeSeverity | null | undefined;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    { size: 'sm' },
);

const DOT_CLASS: Record<string, string> = {
    primary: 'bg-primary',
    secondary: 'bg-neutral-400',
    success: 'bg-success',
    info: 'bg-info',
    warning: 'bg-warning',
    error: 'bg-error',
    neutral: 'bg-neutral-400',
};

const dotClass = computed(() => DOT_CLASS[severityColor(props.severity)]);
</script>

<template>
    <UBadge v-if="label" color="neutral" variant="subtle" :size="size">
        <template #leading>
            <span class="size-1.5 shrink-0 rounded-full" :class="dotClass" />
        </template>

        {{ label }}
    </UBadge>
</template>
