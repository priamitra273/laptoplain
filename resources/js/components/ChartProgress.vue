<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Chart } from "highcharts-vue";
import Highcharts from "highcharts";
import exportingInit from 'highcharts/modules/exporting'
import A11yInit from 'highcharts/modules/accessibility'
import { Project, Statistic } from '@/types';
import Colors from 'tailwindcss/colors';

exportingInit(Highcharts);
A11yInit(Highcharts);

interface HighchartsChartOptions extends Highcharts.ChartOptions {
    custom: {
        [key: string]: any;
    }
}

interface Props {
    project: Project,
    statistic?: Statistic
}

const props = defineProps<Props>();
const chart = ref();

const countCctvInProgress = computed<number>(() => {
    return props.statistic
        ? props.statistic.progress.total_preconfig - props.statistic.progress.analytic_config.done
        : 0
});

const countUnregisteredCctv = computed(() => {
    return props.statistic
        ? props.statistic.plan_cctv - props.statistic.progress.total_preconfig
        : 0
})

const options = computed(() => {
    return {
        chart: {
            type: 'pie',
            custom: {},
            events: {
                render(): void {
                    const chart = (this as any) as Highcharts.Chart;
                    const series = chart.series[0] as Highcharts.Series;

                    let chartOptions = chart.options.chart as HighchartsChartOptions;
                    let customLabel = chartOptions.custom.label;

                    if (!customLabel) {
                        customLabel = chartOptions.custom.label =
                            chart.renderer.label(
                                'Plan CCTV<br/>' + `<strong>${series.total}</strong>`,
                                0
                            )
                                .css({
                                    color: '#000',
                                    textAnchor: 'middle'
                                })
                                .add();
                    }
                    else {
                        customLabel.textSetter('Plan CCTV<br/>' + `<strong>${series.total}</strong>`)
                    }

                    const x = series.center[0] + chart.plotLeft,
                        y = series.center[1] + chart.plotTop -
                            (customLabel.attr('height') / 2);

                    customLabel.attr({
                        x,
                        y
                    });
                    // Set font size based on chart diameter
                    customLabel.css({
                        fontSize: `${series.center[2] / 12}px`
                    });
                }
            }
        },
        accessibility: {
            point: {
                valueSuffix: '%'
            }
        },
        credits: false,
        title: {
            text: `Project Overview`,
            style: {
                fontFamily: "'Instrument Sans', sans-serif",
            }
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.0f}%</b>',
            style: {
                fontFamily: "'Instrument Sans', sans-serif",
            }
        },
        legend: {
            enabled: false
        },
        plotOptions: {
            series: {
                allowPointSelect: true,
                cursor: 'pointer',
                borderRadius: 8,
                dataLabels: [{
                    enabled: true,
                    distance: 20,
                    format: '{point.name}'
                }, {
                    enabled: true,
                    distance: -15,
                    format: '{point.percentage:.0f}%',
                    style: {
                        fontSize: '0.9em'
                    }
                }],
                showInLegend: true
            },
            style: {
                fontFamily: "'Instrument Sans', sans-serif",
            }
        },
        series: [{
            name: 'Registrations',
            colorByPoint: true,
            innerSize: '84%',
            data: [
                {name: 'Completed', color: 'var(--p-primary-color)', y: props.statistic?.progress.analytic_config.done ?? 0},
                {name: 'In Progress', color: Colors.amber[500], y: countCctvInProgress.value},
                {name: 'Unregistered', color: Colors.slate[200], y: countUnregisteredCctv.value},
            ]
        }]
    }
});
</script>

<template>
    <Chart ref="chart" :options="options" class="h-full" />
</template>
