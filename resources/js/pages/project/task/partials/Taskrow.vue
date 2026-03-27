<script setup lang="ts">
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Task } from '..';

defineProps<{ task: Task; draggable?: boolean }>();
const emit = defineEmits<{ edit: [task: Task]; menu: [event: MouseEvent, task: Task] }>();

const categoryIcon = (name?: string) =>
    ({ Epic: 'pi pi-bolt', Issue: 'pi pi-exclamation-circle', Story: 'pi pi-book' })[name ?? ''] ?? 'pi pi-circle';

const categoryColor = (name?: string) => ({ Epic: '#7c3aed', Issue: '#dc2626', Story: '#16a34a' })[name ?? ''] ?? '#64748b';

const prioritySeverity = (name?: string): any => ({ High: 'danger', Medium: 'warn', Low: 'info', Critical: 'danger' })[name ?? ''] ?? 'secondary';

const statusSeverity = (name?: string): any =>
    ({ 'To Do': 'secondary', 'In Progress': 'info', Done: 'success', Completed: 'success' })[name ?? ''] ?? 'secondary';

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
const avatarColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];
const getAvatarColor = (name: string) => avatarColors[name.charCodeAt(0) % avatarColors.length];
</script>

<template>
    <div
        class="group flex cursor-pointer items-center gap-2 border-t border-surface-100 px-3 py-2 hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800/50"
        @click="emit('edit', task)"
    >
        <!-- Drag handle -->
        <i
            v-if="draggable"
            class="drag-handle pi pi-bars flex-shrink-0 cursor-grab text-xs text-surface-300 opacity-0 active:cursor-grabbing group-hover:opacity-100 dark:text-surface-600"
            @click.stop
        />

        <!-- Category icon -->
        <i :class="categoryIcon(task.category?.name)" :style="{ color: categoryColor(task.category?.name) }" class="flex-shrink-0 text-sm" />

        <!-- Task number -->
        <span class="w-16 flex-shrink-0 truncate font-mono text-xs text-surface-400">{{ task.task_number ?? '#' }}</span>

        <!-- Title -->
        <span class="min-w-0 flex-1 truncate text-sm text-surface-800 dark:text-surface-100">{{ task.title }}</span>

        <!-- Story points -->
        <span
            v-if="task.story_points != null"
            class="hidden h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-surface-100 text-xs font-semibold text-surface-600 sm:inline-flex dark:bg-surface-700 dark:text-surface-300"
            title="Story points"
        >
            {{ task.story_points }}
        </span>

        <!-- Priority -->
        <Tag
            v-if="task.priority"
            :value="task.priority.name"
            :severity="prioritySeverity(task.priority.name)"
            class="hidden flex-shrink-0 text-xs sm:inline-flex"
        />

        <!-- Status -->
        <Tag v-if="task.status" :value="task.status.name" :severity="statusSeverity(task.status.name)" class="flex-shrink-0 text-xs" />

        <!-- Assignees (max 3 avatars) -->
        <div class="flex flex-shrink-0 -space-x-1.5">
            <div
                v-for="user in (task.users ?? []).slice(0, 3)"
                :key="user.id"
                class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full text-xs text-white ring-2 ring-white dark:ring-surface-900"
                :style="{ backgroundColor: getAvatarColor(user.name) }"
                :title="user.name"
            >
                <img v-if="user.avatar_url" :src="user.avatar_url" class="h-full w-full object-cover" />
                <span v-else>{{ getInitials(user.name) }}</span>
            </div>
        </div>

        <!-- Context menu -->
        <Button
            icon="pi pi-ellipsis-v"
            size="small"
            text
            rounded
            severity="secondary"
            class="flex-shrink-0 opacity-0 group-hover:opacity-100"
            @click.stop="emit('menu', $event, task)"
        />
    </div>
</template>
