<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';

withDefaults(
    defineProps<{
        label: string | null | undefined;
        severity: PrimeSeverity | null | undefined;
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        size: 'sm',
    },
);
</script>

<template>
    <UBadge
        v-if="label"
        :label="label"
        :color="severityColor(severity)"
        variant="subtle"
        :size="size"
        class="rounded-full"
    />
</template>
