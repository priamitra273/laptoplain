<script setup lang="ts">
import DonutChart from '@/components/charts/DonutChart.vue';
import { computed } from 'vue';
import { TASK_CHART_COLORS } from './chartColor';

const props = withDefaults(
    defineProps<{
        done: number;
        active: number;
        overdue: number;
        size?: number;
    }>(),
    { size: 76 },
);


const segments = computed(() => [
    { label: 'Done', value: props.done, color: TASK_CHART_COLORS.done },
    { label: 'Active', value: props.active, color: TASK_CHART_COLORS.active },
    { label: 'Overdue', value: props.overdue, color: TASK_CHART_COLORS.overdue },
]);
</script>

<template>
    <DonutChart :segments="segments" series-name="Tasks" :size="size" />
</template>
