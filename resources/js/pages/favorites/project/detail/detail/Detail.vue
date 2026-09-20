<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PanelCard from '@/components/ui/PanelCard.vue';
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';

interface Props extends ShellProps {
    description: string | null;
}

defineProps<Props>();
</script>

<template>
    <Head :title="`Details - ${project.title}`" />

    <ProjectShellLayout>
        <PanelCard title="Description">
            <div v-if="description" class="prose prose-sm dark:prose-invert max-w-none [&>*:first-child]:mt-0" v-html="description"></div>

            <EmptyState v-else icon="i-lucide-file-text" title="No description yet for this project." />
        </PanelCard>
    </ProjectShellLayout>
</template>
