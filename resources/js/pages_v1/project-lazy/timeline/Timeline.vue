<script lang="ts">
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import ProjectGanttChart from '@/components/ProjectGanttChart.vue';
import { Head } from '@inertiajs/vue3';
import type { ListTask, ShellProps } from '@/pages/project-lazy';

const props = defineProps<ShellProps & { tasks: ListTask[] }>();
</script>

<template>
    <Head :title="`Timeline - ${props.project.title}`" />
    <ProjectGanttChart :tasks="props.tasks as any" />
</template>
