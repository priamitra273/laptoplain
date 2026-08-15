<script setup lang="ts">
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import type { Sprint, Task } from '../type';

const props = defineProps<{
    task: Task;
    canAct: boolean;
    projectId: string;
    planningSprints?: Sprint[];
    showMoveToBacklog?: boolean;
}>();

const emit = defineEmits<{
    edit: [task: Task];
    delete: [task: Task];
    moveToSprint: [sprintId: string];
    moveToBacklog: [];
}>();

const menu = ref();

const menuItems = computed(() => {
    const items: any[] = [
        {
            label: 'View Detail',
            icon: 'pi pi-eye',
            command: () => (window.location.href = route('task.show', props.task.id)),
        },
    ];

    if (props.canAct) {
        items.push({
            label: 'Edit',
            icon: 'pi pi-pencil',
            command: () => emit('edit', props.task),
        });
    }

    if (props.canAct && (props.planningSprints?.length ?? 0) > 0) {
        items.push({ separator: true });
        items.push({ label: 'Move to Sprint', icon: 'pi pi-arrow-right', disabled: true, class: 'opacity-60 text-xs' });
        props.planningSprints!.forEach((s) =>
            items.push({
                label: s.name,
                icon: 'pi pi-flag',
                command: () => emit('moveToSprint', s.id),
            }),
        );
    }

    if (props.canAct && props.showMoveToBacklog) {
        items.push({ separator: true });
        items.push({
            label: 'Move to Backlog',
            icon: 'pi pi-arrow-left',
            command: () => emit('moveToBacklog'),
        });
    }

    if (props.canAct) {
        items.push({ separator: true });
        items.push({
            label: 'Delete',
            icon: 'pi pi-trash',
            command: () => emit('delete', props.task),
            class: 'text-rose-500',
        });
    }

    return items;
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const avatarColor = (id: string) => {
    const palette = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#f43f5e', '#3b82f6'];
    let h = 0;
    for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) % palette.length;
    return palette[h];
};

const isOverdue = computed(
    () =>
        !!props.task.due_date &&
        moment(props.task.due_date).isBefore(moment(), 'day') &&
        !props.task.status?.name?.toLowerCase().match(/complete|done/),
);

const isDone = computed(() => !!props.task.status?.name?.toLowerCase().match(/complete|done/));

const CATEGORY_STYLE: Record<string, string> = {
    Epic: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    Story: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    Issue: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
};

const PRIORITY_ICON: Record<string, string> = {
    highest: 'pi-angle-double-up',
    high: 'pi-angle-up',
    medium: 'pi-minus',
    low: 'pi-angle-down',
    lowest: 'pi-angle-double-down',
};

const priorityIcon = computed(() => PRIORITY_ICON[props.task.priority?.name?.toLowerCase() ?? ''] ?? 'pi-minus');

const priorityColor = computed(() => {
    const s = props.task.priority?.severity;
    if (s === 'danger') return '#ef4444';
    if (s === 'warn' || s === 'warning') return '#f59e0b';
    if (s === 'success') return '#10b981';
    return '#94a3b8';
});
</script>

<template>
    <div
        class="group flex cursor-grab items-center gap-3 px-4 py-2.5 transition-colors hover:bg-surface-50 active:cursor-grabbing dark:hover:bg-surface-800/40"
    >
        <!-- Drag handle -->
        <i class="pi pi-bars shrink-0 text-xs text-surface-200 opacity-0 transition group-hover:opacity-100 dark:text-surface-600" />

        <!-- Category badge -->
        <span
            v-if="task.category"
            class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold"
            :class="CATEGORY_STYLE[task.category.name] ?? 'bg-surface-100 text-surface-600'"
        >
            {{ task.category.icon }} {{ task.category.name }}
        </span>

        <!-- Priority icon -->
        <i v-if="task.priority" :class="`pi ${priorityIcon} shrink-0 text-xs`" :style="`color:${priorityColor}`" :title="task.priority.name" />

        <!-- Title -->
        <span class="min-w-0 flex-1 truncate text-sm text-surface-700 dark:text-surface-200" :class="{ 'text-surface-400 line-through': isDone }">
            {{ task.title }}
        </span>

        <!-- Status -->
        <Tag v-if="task.status" :value="task.status.name" :severity="task.status.severity" class="!shrink-0 !text-[10px]" />

        <!-- Type -->
        <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" class="!shrink-0 !text-[10px]" />

        <!-- Due date -->
        <span
            v-if="task.due_date"
            class="hidden shrink-0 text-xs sm:flex sm:items-center sm:gap-1"
            :class="isOverdue ? 'font-medium text-rose-500' : 'text-surface-400'"
        >
            <i class="pi pi-calendar text-[10px]" />
            {{ moment(task.due_date).format('DD MMM') }}
        </span>

        <!-- Assignees -->
        <div class="flex shrink-0 -space-x-1.5">
            <Avatar
                v-for="u in (task.users ?? []).slice(0, 3)"
                :key="u.id"
                :image="u.avatar_url && u.avatar_url !== '/images/default-avatar.png' ? u.avatar_url : undefined"
                :label="!u.avatar_url || u.avatar_url === '/images/default-avatar.png' ? getInitials(u.name) : undefined"
                shape="circle"
                :title="u.name"
                :style="`background:${avatarColor(u.id)};color:white;font-size:.55rem;font-weight:600`"
                class="!h-5 !w-5 border border-white dark:border-surface-800"
            />
            <span
                v-if="(task.users ?? []).length > 3"
                class="flex h-5 w-5 items-center justify-center rounded-full border border-white bg-surface-200 text-[9px] font-bold text-surface-600 dark:border-surface-800 dark:bg-surface-700"
            >
                +{{ (task.users ?? []).length - 3 }}
            </span>
        </div>

        <!-- Row actions (visible on hover) -->
        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
            <a :href="route('task.show', task.id)" @click.stop>
                <button class="rounded p-1 text-surface-400 hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-700" title="View">
                    <i class="pi pi-eye text-[11px]" />
                </button>
            </a>
            <button
                v-if="canAct"
                class="rounded p-1 text-surface-400 hover:bg-surface-100 hover:text-amber-500 dark:hover:bg-surface-700"
                title="Edit"
                @click.stop="emit('edit', task)"
            >
                <i class="pi pi-pencil text-[11px]" />
            </button>
            <button
                class="rounded p-1 text-surface-400 hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-700"
                title="More options"
                @click.stop="menu?.toggle($event)"
            >
                <i class="pi pi-ellipsis-h text-[11px]" />
            </button>
        </div>
    </div>

    <Menu ref="menu" :model="menuItems" popup />
</template>
