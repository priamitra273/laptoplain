<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import GanttChart from './GanttChart.vue';
import type { TimelineTask } from './types';

interface Props extends ShellProps {
    tasks?: TimelineTask[];
}

withDefaults(defineProps<Props>(), {
    tasks: () => [],
});
</script>

<template>
    <Head :title="`Timeline - ${project.title}`" />

    <ProjectShellLayout>
        <UCard title="Project Timeline">
            <GanttChart :tasks="tasks" />
        </UCard>
    </ProjectShellLayout>
</template>
