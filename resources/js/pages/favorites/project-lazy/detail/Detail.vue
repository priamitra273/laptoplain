<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';

interface Props extends ShellProps {
    description?: string | null;
}

defineProps<Props>();
</script>

<template>
    <Head :title="`Details - ${project.title}`" />

    <ProjectShellLayout>
        <UCard title="Description">
            <div v-if="description" class="text-sm wrap-break-word" v-html="description" />
            <div v-else class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                <UIcon name="i-lucide-file-text" class="size-10 text-muted" />
                <p class="text-sm text-muted">No description yet for this project.</p>
            </div>
        </UCard>
    </ProjectShellLayout>
</template>
