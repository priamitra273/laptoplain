<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDateRange } from '@/lib/date';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import ReportBurndownChart from './ReportBurndownChart.vue';
import ReportTaskTable from './ReportTaskTable.vue';
import type { SprintOption } from './types';
import { useReportResource } from './useReportResource';

const props = defineProps<ShellProps>();

const {
    data: sprints,
    loading: loadingSprints,
    error: sprintsError,
} = useReportResource<SprintOption[]>(() => route('sprints.all', props.project.id), []);

const selectedSprintId = ref<string | null>(null);

// Opsi sprint diambil ulang saat project berganti, jadi pilihan lama tidak boleh
// tertinggal menunjuk sprint milik project sebelumnya.
watch(
    () => props.project.id,
    () => {
        selectedSprintId.value = null;
    },
);

const selectedSprint = computed(() => sprints.value.find((sprint) => sprint.id === selectedSprintId.value) ?? null);

// Endpoint sprints.all memakai SprintData yang mengirim timestamp penuh, bukan Y-m-d
// seperti BacklogSprintData, jadi jamnya dipotong di sini sebelum diformat.
const sprintRange = computed(() =>
    selectedSprint.value ? formatDateRange(selectedSprint.value.start_date?.slice(0, 10), selectedSprint.value.end_date?.slice(0, 10)) : '—',
);
</script>

<template>
    <Head :title="`Report - ${project.title}`" />

    <ProjectShellLayout>
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <UFormField label="Sprint" :error="sprintsError ?? undefined">
                    <USelectMenu
                        :model-value="selectedSprintId ?? undefined"
                        :items="sprints"
                        :loading="loadingSprints"
                        label-key="name"
                        value-key="id"
                        placeholder="Select a sprint"
                        class="w-full sm:w-72"
                        @update:model-value="(value: string | undefined) => (selectedSprintId = value ?? null)"
                    />
                </UFormField>
                <div v-if="selectedSprint" class="flex flex-wrap items-center gap-2">
                    <StatusBadge :label="selectedSprint.status?.name" :severity="selectedSprint.status?.severity" />
                    <span v-if="selectedSprint.start_date || selectedSprint.end_date" class="text-sm text-muted">{{ sprintRange }}</span>
                </div>
            </div>

            <template v-if="selectedSprint">
                <ReportBurndownChart :project-id="project.id" :sprint-id="selectedSprint.id" />
                <ReportTaskTable :project-id="project.id" :sprint-id="selectedSprint.id" />
            </template>
        </div>
    </ProjectShellLayout>
</template>
