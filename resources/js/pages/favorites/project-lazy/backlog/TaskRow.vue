<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import { computed } from 'vue';
import type { KanbanBadge } from '../kanban/types';
import TaskEpicPicker from './TaskEpicPicker.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

interface Props {
    task: BacklogTask;
    epics: BacklogEpic[];
    priorities: KanbanBadge[];
    sprints: BacklogSprint[];
    currentSprintId: string | null;
    draggable?: boolean;
    canAct?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    draggable: false,
    canAct: false,
});

const emit = defineEmits<{
    edit: [task: BacklogTask];
    updatePriority: [task: BacklogTask, priorityId: string];
    moveTo: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
    assignEpic: [task: BacklogTask, epicId: string | null];
    createEpic: [];
}>();

const menuItems = computed(() => {
    const items: { label: string; icon: string; onSelect: () => void }[][] = [
        [{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => emit('edit', props.task) }],
    ];

    const moveItems: { label: string; icon: string; onSelect: () => void }[] = [];
    if (props.currentSprintId) {
        moveItems.push({
            label: 'Move to Backlog',
            icon: 'i-lucide-arrow-down',
            onSelect: () => emit('moveTo', props.task, props.currentSprintId, null),
        });
    }
    for (const sprint of props.sprints) {
        if (sprint.id === props.currentSprintId) continue;
        moveItems.push({
            label: `Move to ${sprint.name}`,
            icon: 'i-lucide-arrow-right',
            onSelect: () => emit('moveTo', props.task, props.currentSprintId, sprint.id),
        });
    }
    if (moveItems.length) items.push(moveItems);

    return items;
});
</script>

<template>
    <div class="group flex items-center gap-2 border-t border-default px-3 py-2 hover:bg-elevated/50">
        <UIcon
            v-if="draggable"
            name="i-lucide-grip-vertical"
            class="drag-handle size-3.5 shrink-0 cursor-grab text-muted opacity-0 group-hover:opacity-100 active:cursor-grabbing"
        />

        <button type="button" class="min-w-0 flex-1 truncate text-left text-sm hover:underline" :title="task.title" @click="emit('edit', task)">
            {{ task.title }}
        </button>

        <span
            v-if="task.story_points != null"
            class="hidden size-6 shrink-0 items-center justify-center rounded-full bg-elevated text-xs font-semibold text-muted sm:inline-flex"
            title="Story points"
        >
            {{ task.story_points }}
        </span>

        <TaskEpicPicker
            :task="task"
            :epics="epics"
            :can-act="canAct"
            @assign="(t, epicId) => emit('assignEpic', t, epicId)"
            @create-epic="emit('createEpic')"
        />

        <USelectMenu
            v-if="canAct"
            :model-value="task.priority?.id"
            :items="priorities"
            label-key="name"
            value-key="id"
            placeholder="Priority"
            class="w-24 shrink-0"
            :ui="{ base: 'border-0 bg-transparent shadow-none ring-0' }"
            @update:model-value="(value: string) => emit('updatePriority', task, value)"
        >
            <template #default>
                <UBadge v-if="task.priority" :color="severityColor(task.priority.severity)" variant="subtle" size="sm" class="w-full justify-center">
                    {{ task.priority.name }}
                </UBadge>
                <span v-else class="text-xs text-muted">Priority</span>
            </template>
            <template #item-label="{ item }">
                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
            </template>
        </USelectMenu>
        <UBadge
            v-else-if="task.priority"
            :color="severityColor(task.priority.severity)"
            variant="subtle"
            size="sm"
            class="w-24 shrink-0 justify-center"
        >
            {{ task.priority.name }}
        </UBadge>
        <span v-else class="w-24 shrink-0"></span>

        <UBadge
            v-if="task.status"
            :color="severityColor(task.status.severity)"
            variant="subtle"
            size="sm"
            class="w-24 shrink-0 justify-center truncate"
        >
            {{ task.status.name }}
        </UBadge>
        <span v-else class="w-24 shrink-0"></span>

        <UAvatarGroup size="xs" :max="3" class="w-20 shrink-0 justify-end">
            <UAvatar v-for="user in task.users" :key="user.id" :src="user.avatar_url ?? undefined" :alt="user.name" :text="getInitials(user.name)" />
        </UAvatarGroup>

        <UDropdownMenu :items="menuItems" :content="{ align: 'end' }" @click.stop>
            <UButton
                icon="i-lucide-ellipsis-vertical"
                color="neutral"
                variant="ghost"
                size="xs"
                class="shrink-0 opacity-0 group-hover:opacity-100"
                aria-label="More actions"
            />
        </UDropdownMenu>
    </div>
</template>
