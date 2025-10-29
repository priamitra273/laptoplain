<script setup lang="ts">
import { computed } from 'vue';
import { Project } from '@/types';
import { Chart } from "highcharts-vue";
import Highcharts from 'highcharts';
import exportingInit from 'highcharts/modules/exporting'
import A11yInit from 'highcharts/modules/accessibility'
import loadMap from "highcharts/modules/map";

exportingInit(Highcharts)
A11yInit(Highcharts);
loadMap(Highcharts)

interface HighchartsChartOptions extends Highcharts.ChartOptions {
    custom: {
        [key: string]: any;
    }
}

interface MapValue {
    data: (string | number)[],
    geojson: Highcharts.GeoJSON
}

interface Props {
    project: Project,
    value?: MapValue
}

const props = defineProps<Props>()

const options = computed(() => {
    return {
        chart: {
            map: props.value?.geojson ?? {}
        },

        title: {
            text: 'Area Overview'
        },

        credits: false,

        accessibility: {
            typeDescription: 'Map of Jakarta.'
        },

        mapNavigation: {
            enabled: true,
            buttonOptions: {
                verticalAlign: 'bottom'
            }
        },

        colorAxis: {
            tickPixelInterval: 100
        },

        series: [{
            data: props.value?.data ?? [],
            keys: ['code', 'value'],
            joinBy: 'code',
            name: 'Total CCTV',
            dataLabels: {
                enabled: true,
                format: '{point.properties.code}'
            }
        }]
    }
})
</script>

<template>
    <Chart ref="chart" :options="options" :constructor-type="'mapChart'" class="hc h-full" />
</template>