<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, onMounted, ref } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import ReportBurndownChart from './ReportBurndownChart.vue';
import ReportTaskTable from './ReportTaskTable.vue';
import type { SprintOption } from './types';

const props = defineProps<ShellProps>();

const sprints = ref<SprintOption[]>([]);
const loadingSprints = ref(false);
const selectedSprintId = ref<string | null>(null);

const selectedSprint = computed(() => sprints.value.find((sprint) => sprint.id === selectedSprintId.value) ?? null);

const fetchSprints = async () => {
    loadingSprints.value = true;
    try {
        const response = await fetch(route('sprints.all', props.project.id), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        sprints.value = body.data ?? [];

        const active = sprints.value.find((sprint) => sprint.status?.name === 'Active');
        selectedSprintId.value = active?.id ?? sprints.value[sprints.value.length - 1]?.id ?? null;
    } finally {
        loadingSprints.value = false;
    }
};

onMounted(fetchSprints);
</script>

<template>
    <Head :title="`Report - ${project.title}`" />

    <ProjectShellLayout>
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <Label value="Sprint" />
                <USelectMenu
                    :model-value="selectedSprintId ?? undefined"
                    :items="sprints"
                    label-key="name"
                    value-key="id"
                    :loading="loadingSprints"
                    placeholder="Select a sprint"
                    class="w-full sm:w-72"
                    @update:model-value="(value: string | undefined) => (selectedSprintId = value ?? null)"
                />

                <div v-if="selectedSprint" class="flex flex-wrap items-center gap-2">
                    <UBadge v-if="selectedSprint.status" :color="severityColor(selectedSprint.status.severity)" variant="subtle">
                        {{ selectedSprint.status.name }}
                    </UBadge>
                    <span v-if="selectedSprint.start_date && selectedSprint.end_date" class="text-sm text-muted">
                        {{ moment(selectedSprint.start_date).format('DD MMMM YYYY') }} –
                        {{ moment(selectedSprint.end_date).format('DD MMMM YYYY') }}
                    </span>
                </div>
            </div>

            <template v-if="selectedSprint">
                <ReportBurndownChart :project-id="project.id" :sprint-id="selectedSprint.id" />
                <ReportTaskTable :project-id="project.id" :sprint-id="selectedSprint.id" />
            </template>

            <div v-else-if="!loadingSprints" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <UIcon name="i-lucide-chart-line" class="size-10 text-muted" />
                <div class="space-y-1">
                    <p class="text-base font-medium">No sprints yet</p>
                    <p class="text-sm text-muted">Create a sprint for this project to see its report here.</p>
                </div>
            </div>
        </div>
    </ProjectShellLayout>
</template>
