<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ComingSoon from '../ComingSoon.vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
</script>

<template>
    <Head title="Backlog" />

    <ProjectShellLayout>
        <ComingSoon label="Backlog" />
    </ProjectShellLayout>
</template>
