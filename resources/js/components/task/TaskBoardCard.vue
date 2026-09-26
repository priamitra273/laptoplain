<script setup lang="ts">
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import UserAvatarGroup from '@/components/common/UserAvatarGroup.vue';
import { dueDateTone, formatDateShort, formatRelativeDay } from '@/lib/date';
import TaskTypeBadge from './TaskTypeBadge.vue';
import type { BoardCardAction, BoardCardTask } from './types';

const props = defineProps<{
    task: BoardCardTask;
    actions: BoardCardAction[];
    canAct: boolean;
    isDragging: boolean;
}>();

const emit = defineEmits<{
    detail: [];
    dragstart: [event: DragEvent];
    dragend: [];
}>();

/** Nada due date: merah kalau lewat, kuning kalau tinggal tiga hari atau kurang, netral selebihnya. */
const dueDateClass = () =>
    ({ overdue: 'text-error', soon: 'text-warning', normal: 'text-muted', none: 'text-dimmed' })[dueDateTone(props.task.due_date, props.task.is_overdue)];
</script>

<template>
    <UContextMenu :items="actions" :disabled="!actions.length">
        <article
            class="bg-elevated ring-default group focus-visible:outline-primary relative flex flex-col gap-2 rounded-lg p-3 shadow-sm ring transition-opacity outline-none focus-visible:outline-2 focus-visible:-outline-offset-2"
            :class="[canAct ? 'cursor-grab active:cursor-grabbing' : '', isDragging ? 'opacity-40' : '']"
            :draggable="canAct"
            role="button"
            tabindex="0"
            @click="emit('detail')"
            @keydown.enter.self="emit('detail')"
            @keydown.space.self.prevent="emit('detail')"
            @dragstart="emit('dragstart', $event)"
            @dragend="emit('dragend')"
        >
            <div
                v-if="actions.length"
                class="bg-elevated ring-default pointer-events-none absolute end-2 top-2 z-10 flex items-center gap-0.5 rounded-md p-0.5 opacity-0 shadow-sm ring transition-opacity group-focus-within:pointer-events-auto group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:opacity-100"
            >
                <UButton
                    v-for="action in actions"
                    :key="action.label"
                    :icon="action.icon"
                    :color="action.color ?? 'neutral'"
                    variant="ghost"
                    size="xs"
                    square
                    :aria-label="`${action.label}: ${task.title}`"
                    @click.stop="action.onSelect()"
                />
            </div>

            <div class="flex items-center gap-1.5">
                <TaskTypeBadge v-if="task.type" :label="task.type.name" :severity="task.type.severity" />
                <PriorityIcon v-if="task.priority" pill :label="task.priority.name" :severity="task.priority.severity" />

                <span class="ms-auto shrink-0"><slot name="reference" /></span>
            </div>

            <p class="text-highlighted line-clamp-2 text-sm font-medium wrap-anywhere">{{ task.title }}</p>

            <slot name="body" />

            <div class="text-muted flex flex-wrap items-center gap-3 text-xs">
                <span v-if="task.due_date" class="flex items-center gap-1" :class="dueDateClass()">
                    <UIcon name="i-lucide-calendar" class="size-3.5 shrink-0" />
                    {{ formatDateShort(task.due_date) }}
                    <span class="opacity-70">· {{ formatRelativeDay(task.due_date) }}</span>
                </span>
                <span v-else class="text-dimmed">No due date</span>

                <slot name="meta" />

                <UserAvatarGroup v-if="task.users.length" :users="task.users" :max="3" size="xs" class="ms-auto" />
            </div>
        </article>
    </UContextMenu>
</template>
