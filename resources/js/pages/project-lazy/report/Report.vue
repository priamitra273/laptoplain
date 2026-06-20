<script lang="ts">
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { ShellProps } from '@/pages/project-lazy';
import TabPlaceholder from '../partials/TabPlaceholder.vue';

const props = defineProps<ShellProps>();
</script>

<template>
    <Head :title="`Report - ${props.project.title}`" />
    <TabPlaceholder icon="pi pi-chart-line" title="Report" />
</template>
