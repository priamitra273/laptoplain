<script setup lang="ts">
import Highcharts from 'highcharts';
import A11yInit from 'highcharts/modules/accessibility';
import { Chart } from 'highcharts-vue';
import { computed } from 'vue';
import { TASK_CHART_COLORS } from './chartColor';

A11yInit(Highcharts);

const props = withDefaults(
    defineProps<{
        done: number;
        active: number;
        overdue: number;
        size?: number;
    }>(),
    { size: 76 },
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
    legend: { enabled: false },
    tooltip: { pointFormat: '<b>{point.y}</b>' },
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
            name: 'Tasks',
            data: [
                { name: 'Done', y: props.done, color: TASK_CHART_COLORS.done },
                { name: 'Active', y: props.active, color: TASK_CHART_COLORS.active },
                { name: 'Overdue', y: props.overdue, color: TASK_CHART_COLORS.overdue },
            ],
        },
    ],
}));
</script>

<template>
    <!-- Pembungkus berukuran tetap: tanpa ini Highcharts tergencet oleh saudara
         flex-nya, lalu menggambar melewati batas kartu. -->
    <div class="shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
        <Chart :options="options" />
    </div>
</template>
