<script setup lang="ts">
import Highcharts from 'highcharts';
import { Chart } from 'highcharts-vue';
import A11yInit from 'highcharts/modules/accessibility';
import exportingInit from 'highcharts/modules/exporting';
import Colors from 'tailwindcss/colors';
import { computed } from 'vue';

A11yInit(Highcharts);
exportingInit(Highcharts);

interface TaskStatistic {
    completed: number;
    inProgress: number;
    notStarted: number;
}

interface ProjectStatistic {
    total: number;
    completed: number;
    in_progress: number;
}

interface Props {
    taskStatistic: TaskStatistic;
    projectStatistic: ProjectStatistic;
    project?: { title?: string };
}

const props = defineProps<Props>();

const taskTotal = computed(() => props.taskStatistic.completed + props.taskStatistic.inProgress + props.taskStatistic.notStarted);

const projectNotStarted = computed(() => props.projectStatistic.total - props.projectStatistic.completed - props.projectStatistic.in_progress);

const options = computed(() => ({
    chart: {
        type: 'pie',
        height: '100%',
        backgroundColor: 'transparent',
    },
    title: {
        text: `Progress Overview${props.project?.title ? ` - ${props.project.title}` : ''}`,
        align: 'center',
        style: { fontSize: '16px', fontWeight: '600' },
    },
    credits: { enabled: false },
    tooltip: {
        pointFormatter: function () {
            const percent = ((this.y / (this.total ?? 1)) * 100).toFixed(1);
            return `<b>${this.name}</b>: ${this.y} (${percent}%)`;
        },
        useHTML: true,
    },
    plotOptions: {
        pie: {
            innerSize: '50%',
            borderWidth: 2,
            borderColor: '#fff',
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
                enabled: true,
                format: '{point.name}: {point.y}',
                distance: -40,
                style: { fontWeight: 'bold', color: '#333' },
            },
            showInLegend: true,
        },
    },
    series: [
        {
            name: 'Tasks',
            size: '60%',
            innerSize: '50%',
            colorByPoint: true,
            data: [
                { name: 'Completed Tasks', y: props.taskStatistic.completed, color: Colors.green[500], total: taskTotal.value },
                { name: 'In Progress Tasks', y: props.taskStatistic.inProgress, color: Colors.amber[500], total: taskTotal.value },
                { name: 'Not Started Tasks', y: props.taskStatistic.notStarted, color: Colors.gray[300], total: taskTotal.value },
            ],
        },
        {
            name: 'Projects',
            size: '90%',
            innerSize: '70%',
            colorByPoint: true,
            data: [
                { name: 'Completed Projects', y: props.projectStatistic.completed, color: Colors.green[700], total: props.projectStatistic.total },
                {
                    name: 'In Progress Projects',
                    y: props.projectStatistic.in_progress,
                    color: Colors.amber[700],
                    total: props.projectStatistic.total,
                },
                { name: 'Not Started Projects', y: projectNotStarted.value, color: Colors.gray[400], total: props.projectStatistic.total },
            ],
        },
    ],
    legend: {
        layout: 'horizontal',
        align: 'center',
        verticalAlign: 'bottom',
        itemStyle: { fontWeight: '500' },
    },
}));
</script>

<template>
    <Chart :options="options" class="h-full w-full" />
</template>
