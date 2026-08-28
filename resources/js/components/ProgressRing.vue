<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        label?: string;
        size?: number;
    }>(),
    { size: 60 },
);

const radius = 25;
const circumference = 2 * Math.PI * radius;

const dashArray = computed(() => {
    const filled = (circumference * Math.min(100, Math.max(0, props.value))) / 100;

    return `${filled.toFixed(1)} ${circumference.toFixed(1)}`;
});
</script>

<template>
    <div class="flex shrink-0 flex-col items-center gap-1.5">
        <div class="relative flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
            <svg :width="size" :height="size" viewBox="0 0 60 60" class="-rotate-90" aria-hidden="true">
                <circle cx="30" cy="30" :r="radius" fill="none" stroke-width="5" class="stroke-[var(--ui-border-accented)]" />
                <circle
                    cx="30"
                    cy="30"
                    :r="radius"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="5"
                    stroke-linecap="round"
                    :stroke-dasharray="dashArray"
                    class="text-primary"
                />
            </svg>
            <span class="absolute text-xs font-semibold tabular-nums">{{ value }}%</span>
        </div>
        <span v-if="label" class="text-xs text-muted">{{ label }}</span>
    </div>
</template>
