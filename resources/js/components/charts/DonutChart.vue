<script setup lang="ts">
import Highcharts from 'highcharts';
import A11yInit from 'highcharts/modules/accessibility';
import { Chart } from 'highcharts-vue';
import { computed } from 'vue';
import { normalizeDonutSegments } from './donutChart';
import type { DonutChartSegment } from './types';

A11yInit(Highcharts);

const props = withDefaults(
    defineProps<{
        segments: DonutChartSegment[];
        seriesName?: string;
        size?: number;
        emptyColor?: string;
    }>(),
    {
        seriesName: 'Items',
        size: 76,
        emptyColor: 'rgba(113, 113, 122, 0.2)',
    },
);

const normalizedSegments = computed(() => normalizeDonutSegments(props.segments, props.emptyColor));
const chartData = computed(() =>
    normalizedSegments.value.data.map((segment) => ({ name: segment.label, y: segment.value, color: segment.color })),
);

const options = computed<Highcharts.Options>(() => ({
    chart: {
        type: 'pie',
        backgroundColor: 'transparent',
        height: props.size,
        width: props.size,
        margin: [0, 0, 0, 0],
        spacing: [0, 0, 0, 0],
    },
    title: { text: undefined },
    credits: { enabled: false },
    exporting: { enabled: false },
    legend: { enabled: false },
    tooltip: { enabled: normalizedSegments.value.hasData, pointFormat: '<b>{point.y}</b>' },
    plotOptions: {
        pie: {
            size: '100%',
            innerSize: '70%',
            borderWidth: 0,
            dataLabels: { enabled: false },
        },
    },
    series: [
        {
            type: 'pie',
            name: props.seriesName,
            data: chartData.value,
        },
    ],
}));
</script>

<template>
    <div class="shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
        <Chart :options="options" />
    </div>
</template>
