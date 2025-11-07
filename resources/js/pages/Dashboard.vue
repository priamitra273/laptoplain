<script setup lang="ts">
import ChartProgress from '@/components/ChartProgress.vue';
import ChartRegency from '@/components/ChartRegency.vue';
import Heading from '@/components/Heading.vue';
import StatisticCard from '@/components/StatisticCard.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { type BreadcrumbItem, Project, Statistic } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Colors from 'tailwindcss/colors';
import { onMounted, ref } from 'vue';

interface Props {
    projects: Project[];
    latestProject: Project;
}

interface MapValue {
    data: (string | number)[];
    geojson: Highcharts.GeoJSON;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const project = ref<Project>(props.latestProject);
const statistic = ref<Statistic | undefined>(undefined);
const regencyStat = ref<MapValue | undefined>();

const loading = ref({
    statistic: false,
    regencyStatistic: false,
});

const value = ref([
    { label: 'Space used', value: 15, color: 'var(--p-primary-color)' },
    { label: 'Pending', value: 30, color: Colors.amber[500] },
]);

async function getStatistics(project: Project) {
    loading.value.statistic = true;

    const response = await axios.get(route('dashboard.statistic', project.uuid));
    statistic.value = response.data.data as Statistic;

    loading.value.statistic = false;
}

async function getRegencyStatistic(project: Project) {
    loading.value.regencyStatistic = true;

    const response = await axios.get(route('dashboard.map', project.uuid));
    regencyStat.value = response.data.data as MapValue;

    loading.value.regencyStatistic = false;
}

function loadData() {
    getStatistics(project.value);
    getRegencyStatistic(project.value);
}

onMounted(() => {
    if (project.value) loadData();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex items-center justify-between">
            <Heading title="Dashboard" />
            <!-- <Select v-model="project" :options="projects" option-label="name" placeholder="Select a project" class="w-48" :loading="loading.statistic" @value-change="loadData">
            </Select> -->
        </div>

        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3 lg:grid-cols-6">
                <!-- <div v-for="i in 6" class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div> -->

                <StatisticCard
                    title="Plan CCTV"
                    :value="statistic?.plan_cctv"
                    :description="`Total from ${statistic?.plan_site ?? 0} sites.`"
                    variant="text"
                />
                <StatisticCard title="Registered Site" :value="statistic?.progress.total_site" :max="statistic?.plan_site" variant="single" />
                <StatisticCard
                    title="Total Preconfig"
                    :value="statistic?.progress.total_preconfig"
                    :max="statistic?.plan_cctv"
                    variant="single"
                    info="Based on total CCTV"
                />

                <StatisticCard
                    title="Progress Installation"
                    :value="statistic?.progress.installation.total"
                    :done="statistic?.progress.installation.done"
                    :pending="statistic?.progress.installation.pending"
                    :max="statistic?.plan_site"
                    variant="multiple"
                    info="Based on total site"
                />

                <StatisticCard
                    title="Config Streaming"
                    :value="statistic?.progress.stream_config.total"
                    :done="statistic?.progress.stream_config.done"
                    :pending="statistic?.progress.stream_config.pending"
                    :max="statistic?.plan_cctv"
                    variant="multiple"
                    info="Based on total CCTV"
                />

                <StatisticCard
                    title="Config Analytics"
                    :value="statistic?.progress.analytic_config.total"
                    :done="statistic?.progress.analytic_config.done"
                    :pending="statistic?.progress.analytic_config.pending"
                    :max="statistic?.plan_cctv"
                    variant="multiple"
                    info="Based on total CCTV"
                />

                <div class="col-span-3 aspect-video overflow-hidden rounded-xl shadow">
                    <ChartRegency :project="project" :value="regencyStat" />
                </div>

                <div class="col-span-3 aspect-video overflow-hidden rounded-xl shadow">
                    <ChartProgress :statistic="statistic" :project="project" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
