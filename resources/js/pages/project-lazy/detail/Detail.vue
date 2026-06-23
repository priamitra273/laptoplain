<script lang="ts">
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { ShellProps } from '@/pages/project-lazy';
import TabPlaceholder from '../partials/TabPlaceholder.vue';

const props = defineProps<ShellProps & { description?: string | null }>();
</script>

<template>
    <Head :title="`Details - ${props.project.title}`" />

    <div v-if="props.description" class="prose max-w-none dark:prose-invert">
        <h3 class="mb-2 text-lg font-semibold">Description</h3>
        <div class="text-sm text-surface-700 dark:text-surface-300" v-html="props.description"></div>
    </div>
    <TabPlaceholder v-else icon="pi pi-info-circle" title="Details" description="No description yet for this project." />
</template>
