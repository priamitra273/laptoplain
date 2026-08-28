<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import { computed } from 'vue';
import { dueDotClass, dueInfo, dueToneClass } from './dueDate';
import type { TaskRow } from './types';

const props = defineProps<{
    tasks: TaskRow[];
    total: number;
}>();

const rows = computed(() =>
    props.tasks.map((task) => {
        const completed = Boolean(task.completed_at);

        return {
            task,
            completed,
            // A finished task has no urgency left, so its due date stops driving the row's tone.
            due: completed ? { label: 'Completed', tone: 'neutral' as const } : dueInfo(task.due_date),
            origin: task.is_assigned ? 'Assigned to me' : 'Created by me',
            avatars: task.assignees.map((assignee) => ({ id: assignee.id, text: getInitials(assignee.name), alt: assignee.name })),
        };
    }),
);
</script>

<template>
    <UCard
        :ui="{
            root: 'overflow-hidden',
            header: 'px-4 py-3 sm:px-4 sm:py-3',
            body: 'p-0 sm:p-0',
            footer: 'bg-elevated/30 px-4 py-2.5 sm:px-4 sm:py-2.5',
        }"
    >
        <template #header>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                <h2 class="text-sm font-semibold text-highlighted">Your tasks</h2>
                <UBadge color="neutral" variant="outline" size="sm" label="5 most recent" />
            </div>
        </template>

        <ul v-if="rows.length" class="divide-y divide-default">
            <li
                v-for="{ task, completed, due, origin, avatars } in rows"
                :key="task.id"
                class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 lg:grid lg:grid-cols-[0.375rem_minmax(0,18rem)_minmax(0,14.5rem)_5.75rem_8.25rem_minmax(6.5rem,1fr)_2.5rem] lg:gap-x-2.5"
            >
                <span class="size-1.5 shrink-0 rounded-full" :class="completed ? 'bg-success' : dueDotClass(due.tone)" />

                <ULink :to="route('task.show', String(task.id))" class="min-w-0 flex-1 basis-full truncate text-sm font-medium lg:basis-auto">
                    {{ task.title }}
                </ULink>

                <div class="flex min-w-0 items-center gap-1.5 text-xs text-muted">
                    <template v-if="task.project">
                        <Icon v-if="task.project.emoji" :name="task.project.emoji" class="size-3.5 shrink-0" />
                        <span class="truncate">{{ task.project.title }}</span>
                    </template>
                </div>

                <div class="flex min-w-0">
                    <UBadge
                        v-if="task.status"
                        :color="severityColor(task.status.severity)"
                        variant="subtle"
                        size="sm"
                        :label="task.status.name"
                        class="truncate"
                    />
                </div>

                <div class="hidden min-w-0 lg:flex">
                    <UBadge color="neutral" variant="outline" size="sm" :label="origin" class="truncate" />
                </div>

                <span
                    class="ms-auto shrink-0 text-xs tabular-nums lg:ms-0 lg:text-right"
                    :class="completed ? 'text-success' : dueToneClass(due.tone)"
                >
                    {{ due.label }}
                </span>

                <div class="flex shrink-0 justify-end">
                    <UAvatarGroup v-if="avatars.length" :max="3" size="2xs">
                        <UAvatar v-for="avatar in avatars" :key="avatar.id" :text="avatar.text" :alt="avatar.alt" :title="avatar.alt" />
                    </UAvatarGroup>
                </div>
            </li>
        </ul>

        <p v-else class="px-4 py-6 text-center text-sm text-muted">
            No tasks yet. Anything you create, or that a teammate assigns to you, lands in this list.
        </p>

        <template #footer>
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs text-muted tabular-nums">Showing {{ rows.length }} of {{ total }} tasks</span>
                <UButton
                    :to="route('task.index')"
                    label="View all tasks"
                    trailing-icon="i-lucide-arrow-right"
                    variant="link"
                    size="sm"
                    class="-me-2"
                />
            </div>
        </template>
    </UCard>
</template>
