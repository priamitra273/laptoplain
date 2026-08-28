<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import { computed } from 'vue';
import { dueDotClass, dueInfo } from './dueDate';
import type { TaskRow } from './types';

const props = defineProps<{
    tasks: TaskRow[];
    overdue: number;
    dueSoon: number;
    blocked: number;
}>();

const counters = computed(() =>
    [
        { key: 'overdue', count: props.overdue, label: `${props.overdue} overdue`, color: 'error' as const },
        { key: 'due-soon', count: props.dueSoon, label: `${props.dueSoon} due within 7 days`, color: 'warning' as const },
        { key: 'blocked', count: props.blocked, label: `${props.blocked} blocked`, color: 'error' as const },
    ].filter((counter) => counter.count > 0),
);

const rows = computed(() => props.tasks.map((task) => ({ task, due: dueInfo(task.due_date) })));
</script>

<template>
    <UCard :ui="{ root: 'bg-error/5 ring-error/20', body: 'p-4 sm:p-4' }">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <h2 class="text-sm font-semibold text-highlighted">Needs attention</h2>

            <div class="flex flex-wrap items-center gap-1.5">
                <UBadge
                    v-for="counter in counters"
                    :key="counter.key"
                    :color="counter.color"
                    variant="subtle"
                    size="sm"
                    :label="counter.label"
                    class="tabular-nums"
                />
                <UBadge v-if="!counters.length" color="success" variant="subtle" size="sm" label="All clear" />
            </div>

            <span class="ms-auto hidden text-xs text-muted sm:inline">Counted across all your tasks</span>
        </div>

        <ul v-if="rows.length" class="mt-3 flex flex-col gap-1.5">
            <li
                v-for="{ task, due } in rows"
                :key="task.id"
                class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-md bg-default px-3 py-2.5 ring ring-default lg:grid lg:grid-cols-[0.375rem_minmax(0,20rem)_minmax(0,14.5rem)_5.75rem_minmax(8rem,1fr)] lg:gap-x-2.5"
            >
                <span class="size-1.5 shrink-0 rounded-full" :class="dueDotClass(due.tone)" />

                <ULink :to="route('task.show', String(task.id))" class="min-w-0 flex-1 basis-full truncate text-sm font-medium sm:basis-auto">
                    {{ task.title }}
                </ULink>

                <div class="flex min-w-0 items-center gap-1.5 text-xs text-muted">
                    <template v-if="task.project">
                        <Icon v-if="task.project.emoji" :name="task.project.emoji" class="size-3.5 shrink-0" />
                        <span class="truncate">{{ task.project.title }}</span>
                    </template>
                </div>

                <div class="ms-auto flex min-w-0 lg:ms-0">
                    <UBadge
                        v-if="task.status"
                        :color="severityColor(task.status.severity)"
                        variant="subtle"
                        size="sm"
                        :label="task.status.name"
                        class="truncate"
                    />
                </div>

                <div class="flex shrink-0 justify-end">
                    <UBadge :color="due.tone === 'error' ? 'error' : 'warning'" variant="soft" size="sm" :label="due.label" class="tabular-nums" />
                </div>
            </li>
        </ul>

        <p v-else class="mt-3 rounded-md bg-default px-3 py-4 text-center text-sm text-muted ring ring-default">
            Nothing is overdue or due this week. New work shows up here as soon as it has a due date.
        </p>
    </UCard>
</template>
