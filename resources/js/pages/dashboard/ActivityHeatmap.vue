<script setup lang="ts">
import { CalendarHeatmap, Heatmap } from 'vue3-calendar-heatmap';
import 'vue3-calendar-heatmap/dist/style.css';

// Override default day range, set to 10 months
(Heatmap as unknown as { DAYS_IN_ONE_YEAR: number }).DAYS_IN_ONE_YEAR = 7 * 4 * 10;

defineProps<{
    values: { date: string; count: number }[];
}>();

// Colors for each range, from 0 to 5.
// Colors range using active theme color.
const rangeColor = ['var(--hm-0)', 'var(--hm-1)', 'var(--hm-2)', 'var(--hm-3)', 'var(--hm-4)', 'var(--hm-5)'];
</script>

<template>
    <div class="activity-heatmap overflow-x-auto">
        <CalendarHeatmap :values="values" :end-date="new Date()" :range-color="rangeColor" :round="2"
            tooltip-unit="task events" />
    </div>
</template>

<style scoped>
.activity-heatmap {
    --hm-0: var(--ui-bg-elevated);
    --hm-1: var(--ui-color-primary-100);
    --hm-2: var(--ui-color-primary-300);
    --hm-3: var(--ui-color-primary-500);
    --hm-4: var(--ui-color-primary-600);
    --hm-5: var(--ui-color-primary-800);
}

.dark .activity-heatmap {
    --hm-1: var(--ui-color-primary-950);
    --hm-2: var(--ui-color-primary-800);
    --hm-3: var(--ui-color-primary-600);
    --hm-4: var(--ui-color-primary-500);
    --hm-5: var(--ui-color-primary-300);
}
</style>
