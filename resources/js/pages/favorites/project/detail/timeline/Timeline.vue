<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import GanttChart from './GanttChart.vue';
import type { TimelineTask } from './types';

defineProps<ShellProps & { tasks: TimelineTask[] }>();
</script>

<template>
    <Head :title="`Timeline - ${project.title}`" />

    <ProjectShellLayout>
        <GanttChart :tasks="tasks" />
    </ProjectShellLayout>
</template>
