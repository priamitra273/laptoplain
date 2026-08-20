<script setup lang="ts">
import Highcharts from 'highcharts';
import A11yInit from 'highcharts/modules/accessibility';
import exportingInit from 'highcharts/modules/exporting';
import { Chart } from 'highcharts-vue';
import moment from 'moment';
import { computed, onMounted, ref, watch } from 'vue';
import type { BurndownPoint } from './types';

A11yInit(Highcharts);
exportingInit(Highcharts);

interface Props {
    projectId: string;
    sprintId: string;
}

const props = defineProps<Props>();

const loading = ref(false);
const burndown = ref<BurndownPoint[]>([]);

const options = computed<Highcharts.Options>(() => ({
    chart: { type: 'line', backgroundColor: 'transparent', height: 360 },
    title: { text: undefined },
    credits: { enabled: false },
    xAxis: {
        categories: burndown.value.map((point) => moment(point.date).format('DD MMM')),
        title: { text: 'Date' },
    },
    yAxis: {
        title: { text: 'Remaining tasks' },
        allowDecimals: false,
    },
    series: [
        { type: 'line', name: 'Planned', data: burndown.value.map((point) => point.total_plan), color: '#94a3b8' },
        { type: 'line', name: 'Actual', data: burndown.value.map((point) => point.total_actual), color: '#f43f5e' },
    ],
    legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' },
}));

const fetchBurndown = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('sprints.burndown', { project: props.projectId, projectSprint: props.sprintId }), {
            headers: { Accept: 'application/json' },
        });
        const body = await response.json();
        burndown.value = body.data ?? [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.sprintId, fetchBurndown);
onMounted(fetchBurndown);
</script>

<template>
    <UCard title="Burndown Chart">
        <div v-if="loading" class="flex h-90 items-center justify-center">
            <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-muted" />
        </div>
        <p v-else-if="!burndown.length" class="py-16 text-center text-sm text-muted">No burndown data for this sprint.</p>
        <Chart v-else :options="options" class="w-full" />
    </UCard>
</template>
