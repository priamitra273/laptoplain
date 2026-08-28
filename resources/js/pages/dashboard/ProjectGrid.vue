<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import { computed } from 'vue';
import { dueDotClass, dueInfo, dueToneClass } from './dueDate';
import type { ProjectRow } from './types';

const props = defineProps<{
    projects: ProjectRow[];
    total: number;
}>();

const cards = computed(() =>
    props.projects.map((project) => ({
        project,
        due: dueInfo(project.due_date),
        progress: Math.round(project.progress ?? 0),
    })),
);
</script>

<template>
    <section class="flex flex-col gap-2.5">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <h2 class="text-sm font-semibold text-highlighted">Your projects</h2>
            <UBadge color="neutral" variant="outline" size="sm" label="5 most recent" />
            <UButton
                :to="route('project.index')"
                :label="`View all ${total} projects`"
                trailing-icon="i-lucide-arrow-right"
                variant="link"
                size="sm"
                class="ms-auto -me-2"
            />
        </div>

        <div v-if="cards.length" class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
            <div v-for="{ project, due, progress } in cards" :key="project.id" class="flex flex-col gap-2.5 rounded-lg p-3 ring ring-default">
                <div class="flex items-center justify-between gap-2">
                    <Icon v-if="project.emoji" :name="project.emoji" class="size-4 shrink-0 text-muted" />
                    <UIcon v-else name="i-lucide-folder" class="size-4 shrink-0 text-muted" />
                    <span class="truncate font-mono text-xs text-muted">{{ project.project_no }}</span>
                </div>

                <ULink :to="route('project.show', String(project.id))" class="line-clamp-2 min-h-10 text-sm leading-snug font-semibold">
                    {{ project.title }}
                </ULink>

                <div class="space-y-1.5">
                    <div class="flex justify-between text-xs">
                        <span class="text-muted">Progress</span>
                        <span class="font-medium tabular-nums">{{ progress }}%</span>
                    </div>
                    <UProgress :model-value="progress" size="sm" />
                </div>

                <div class="flex items-center gap-1.5 text-xs tabular-nums" :class="dueToneClass(due.tone)">
                    <span class="size-1.5 shrink-0 rounded-full" :class="dueDotClass(due.tone)" />
                    <span class="truncate">{{ due.label }}</span>
                </div>

                <div class="mt-auto flex items-center justify-between gap-2 border-t border-default pt-2.5">
                    <UBadge
                        v-if="project.status"
                        :color="severityColor(project.status.severity)"
                        variant="subtle"
                        size="sm"
                        :label="project.status.name"
                    />
                    <span v-else class="text-xs text-muted">No status</span>

                    <UBadge
                        v-if="project.priority"
                        :color="severityColor(project.priority.severity)"
                        variant="outline"
                        size="sm"
                        :label="project.priority.name"
                    />
                </div>
            </div>
        </div>

        <p v-else class="rounded-lg border border-dashed border-default px-4 py-6 text-center text-sm text-muted">
            No projects yet. Projects appear here once a PM adds you as a member.
        </p>
    </section>
</template>
