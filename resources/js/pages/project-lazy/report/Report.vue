<script setup lang="ts">
import type { ShellProps } from '@/pages/project-lazy';
import ProjectReportTab from '@/pages/project/partials/ProjectReportTab.vue';
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';

const props = defineProps<ShellProps>();
</script>

<template>
    <Head :title="`Report - ${props.project.title}`" />

    <ProjectShellLayout>
        <ProjectReportTab :project="props.project" />
    </ProjectShellLayout>
</template>
