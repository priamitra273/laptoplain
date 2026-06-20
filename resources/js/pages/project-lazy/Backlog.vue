<script lang="ts">
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { ShellProps } from './index';
import TabPlaceholder from './partials/TabPlaceholder.vue';

const props = defineProps<ShellProps>();
</script>

<template>
    <Head :title="`Backlog - ${props.project.title}`" />
    <TabPlaceholder icon="pi pi-inbox" title="Backlog" />
</template>
