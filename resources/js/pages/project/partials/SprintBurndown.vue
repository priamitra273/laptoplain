<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import axios from 'axios';
import Highcharts from 'highcharts';
import { Chart } from 'highcharts-vue';
import A11yInit from 'highcharts/modules/accessibility';
import exportingInit from 'highcharts/modules/exporting';
import { computed, ref } from 'vue';
import { Project } from '..';

import Sprint = App.Data.Sprint;

A11yInit(Highcharts);
exportingInit(Highcharts);

const props = defineProps<{
    sprintId: string;
    project: Project;
}>();

const loading = ref(false);
const burndown = ref<Sprint.BurndownChartData[]>([]);

const options = computed<Highcharts.Options>(() => {
    return {
        chart: {
            type: 'line',
            backgroundColor: 'transparent',
        },
        title: {
            text: 'Burndown Chart',
            align: 'center',
            style: { fontSize: '16px', fontWeight: '600' },
        },
        credits: { enabled: false },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    format: '{point.name}: {point.y}',
                    distance: -40,
                    style: { fontWeight: 'bold', color: '#333' },
                },
                showInLegend: true,
            },
        },
        xAxis: {
            type: 'datetime',
            labels: {
                format: '{value:%d %b}',
            },
            title: {
                text: 'Date',
            },
            categories: burndown.value.map((item) => item.date),
        },

        series: [
            {
                type: 'line',
                name: 'Planned',
                data: burndown.value.map((item) => item.total_plan),
                color: '#64748b',
            },
            {
                type: 'line',
                name: 'Actual',
                data: burndown.value.map((item) => item.total_actual),
                color: '#ef4444',
            },
        ],
        legend: {
            layout: 'horizontal',
            align: 'center',
            verticalAlign: 'bottom',
            itemStyle: { fontWeight: '500' },
        },
    };
});

const fetchBurndown = async (sprintId: string) => {
    loading.value = true;

    const response = await axios.get(
        route('sprints.burndown', {
            project: props.project.id,
            projectSprint: sprintId,
        }),
    );

    burndown.value = response.data.data;
    loading.value = false;
};

watchDebounced(
    () => props.sprintId,
    (sprintId) => {
        if (sprintId) {
            fetchBurndown(sprintId);
        }
    },
    { debounce: 500 },
);
</script>

<template>
    <Chart :options="options" class="h-[500px] w-3/4" />
</template>
