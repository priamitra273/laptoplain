<script setup lang="ts">
import { priorityIcon, severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';

defineProps<{
    label: string | null | undefined;
    severity: PrimeSeverity | null | undefined;
    pill?: boolean;
}>();
</script>

<template>
    <UBadge v-if="label && pill" :color="severityColor(severity)" variant="subtle" size="sm">
        <template #leading>
            <UIcon :name="priorityIcon(label)" class="size-3" />
        </template>

        {{ label }}
    </UBadge>


    <div v-else-if="label" class="flex items-center gap-1.5" :class="`text-${severityColor(severity)}`">
        <UIcon :name="priorityIcon(label)" class="size-4" />
        {{ label }}
    </div>
</template>
