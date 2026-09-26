<script setup lang="ts">
import FieldLabel from '@/components/ui/FieldLabel.vue';
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import ProgressWithLabel from '@/components/common/ProgressWithLabel.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import TaskDiscussion from '@/components/task/TaskDiscussion.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import UserAvatarGroup from '@/components/common/UserAvatarGroup.vue';
import { daysUntil, formatDateRange } from '@/lib/date';
import { isTaskStatusDone } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { DialogTitle } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { useTaskDetailData } from './useTaskDetailData';

interface DetailBadge {
    name: string;
    severity: PrimeSeverity | null;
}

interface DetailUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

export interface TaskDetailTask {
    id: string;
    code?: string;
    title: string;
    /** Terisi kalau pemanggil sudah punya description sinkron (Kanban) — dipakai sebagai fallback saat fetch masih loading/gagal, bukan cuma sesudahnya. List tidak mengirim field ini, jadi tetap bergantung penuh pada fetch. */
    description?: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    is_overdue?: boolean;
    status: DetailBadge | null;
    type?: DetailBadge | null;
    priority?: DetailBadge | null;
    users: DetailUser[];
    sub_task_recursive?: TaskDetailTask[];
}

const props = withDefaults(
    defineProps<{
        task: TaskDetailTask;
        projectId: string;
        projectTitle?: string;
        /** List tidak mengirim `task.priority` sinkron — dipakai untuk resolve dari `detail.priority_id` setelah fetch. */
        priorities?: PriorityOption[];
        canEdit?: boolean;
        canDelete?: boolean;
        history?: boolean;
        /** Diteruskan ke daftar mention di kotak komentar. */
        assignableUsers?: { id: string; name: string; avatar_url?: string | null }[];
    }>(),
    { canEdit: false, canDelete: false, history: false },
);

const emit = defineEmits<{ close: [false | 'edit' | 'delete' | { open: TaskDetailTask }] }>();

const isDone = (task: TaskDetailTask) => isTaskStatusDone(task.status?.name);

const descriptionOpen = ref(true);
const subtasksOpen = ref(true);

const subtaskRows = computed(() => props.task.sub_task_recursive ?? []);
const doneSubtaskCount = computed(() => subtaskRows.value.filter(isDone).length);

const daysLate = computed(() => {
    if (!props.task.is_overdue) return 0;
    const days = daysUntil(props.task.due_date);
    return days !== null && days < 0 ? Math.abs(days) : 0;
});

const moreActions = computed(() => [{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => emit('close', 'delete') }]);

const { loading, failed, detail, load } = useTaskDetailData(
    () => props.projectId,
    () => props.task.id,
);

/**
 * `detail` (hasil fetch) selalu menang begitu tersedia — description bawaan kartu (Kanban)
 * cuma fallback selagi fetch masih loading/gagal, bukan menimpa hasil fetch yang sukses.
 * Kalau tidak, description yang berubah/terhapus di server tetap terlihat versi lama.
 */
const descriptionKnown = computed(() => props.task.description !== undefined);
const descriptionReady = computed(() => detail.value !== null || descriptionKnown.value);
const descriptionText = computed(() => (detail.value ? detail.value.description : descriptionKnown.value ? props.task.description : undefined));

/** Kanban sudah punya `task.priority` sinkron; List baru dapat setelah `detail.priority_id` di-resolve lewat daftar priority project. */
const resolvedPriority = computed<DetailBadge | null>(() => {
    if (props.task.priority) return props.task.priority;

    const match = props.priorities?.find((priority) => priority.id === detail.value?.priority_id);

    return match ? { name: match.name, severity: match.severity } : null;
});

/**
 * `watch` dipakai bukan `onMounted`: navigasi antar subtask di dalam panel ini membuka task
 * baru lewat `taskDetailPanel.open()` yang sama, tapi Nuxt UI mempertahankan instance komponen
 * yang sama alih-alih memasangnya ulang — `onMounted` tidak pernah jalan lagi untuk task
 * berikutnya, sehingga description/lampiran task sebelumnya tetap nyangkut.
 */
watch(
    () => props.task.id,
    () => {
        if (!props.history) void load();
    },
    { immediate: true },
);
</script>

<template>
    <USlideover :title="task.title" class="w-full max-w-xl" :ui="{ body: 'p-6' }" @update:open="(open: boolean) => !open && emit('close', false)">
        <template #header>
            <div class="flex min-w-0 flex-col gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <TaskTypeBadge v-if="task.type" :label="task.type.name" :severity="task.type.severity" class="shrink-0" />
                    <span v-if="task.code" class="shrink-0 font-mono text-sm text-muted">{{ task.code }}</span>
                    <template v-if="projectTitle">
                        <span class="shrink-0 text-sm text-dimmed">·</span>
                        <span class="min-w-0 truncate text-sm text-muted">{{ projectTitle }}</span>
                    </template>

                    <div class="ms-auto flex shrink-0 items-center gap-0.5">
                        <UButton
                            v-if="canEdit"
                            icon="i-lucide-pencil"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            square
                            aria-label="Edit task"
                            @click="emit('close', 'edit')"
                        />
                        <UDropdownMenu v-if="canDelete" :items="moreActions">
                            <UButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" size="sm" square aria-label="More actions" />
                        </UDropdownMenu>
                        <UButton
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            square
                            aria-label="Close"
                            @click="emit('close', false)"
                        />
                    </div>
                </div>

                <DialogTitle as="h2" class="text-xl leading-tight font-bold wrap-anywhere text-highlighted">{{ task.title }}</DialogTitle>
            </div>
        </template>

        <template #body>
            <div class="flex flex-col gap-6">
                <div class="grid grid-cols-2 gap-x-4 gap-y-5">
                    <div class="min-w-0">
                        <FieldLabel title="Status" />
                        <StatusBadge v-if="task.status" :label="task.status.name" :severity="task.status.severity" class="mt-1.5" />
                        <p v-else class="mt-1.5 text-sm text-muted">—</p>
                    </div>

                    <div class="min-w-0">
                        <FieldLabel :title="task.users.length === 1 ? 'Assignee' : 'Assignees'" /><UserAvatarGroup
                            :users="task.users"
                            :max="3"
                            size="sm"
                            empty-label="Unassigned"
                            class="mt-1.5"
                        />
                    </div>

                    <div v-if="resolvedPriority" class="min-w-0">
                        <FieldLabel title="Priority" />
                        <PriorityIcon pill :label="resolvedPriority.name" :severity="resolvedPriority.severity" class="mt-1.5" />
                    </div>

                    <div class="min-w-0">
                        <FieldLabel title="Schedule" />
                        <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-sm">
                            <UIcon name="i-lucide-calendar" class="size-3.5 shrink-0 text-muted" />
                            <span>{{ formatDateRange(task.start_date, task.due_date) }}</span>
                            <span v-if="daysLate" class="shrink-0 text-xs font-medium text-error">{{ daysLate }}d late</span>
                        </p>
                    </div>

                    <div class="col-span-2">
                        <FieldLabel title="Progress" />
                        <ProgressWithLabel :value="task.progress" :bar-aria-label="`Progress ${task.title}`" class="mt-2" />
                        <p v-if="subtaskRows.length" class="mt-1 text-end text-xs text-muted tabular-nums">
                            {{ doneSubtaskCount }}/{{ subtaskRows.length }}
                        </p>
                    </div>
                </div>

                <USeparator />

                <template v-if="!history">
                    <UCollapsible v-model:open="descriptionOpen">
                        <template #default="{ open }">
                            <UButton type="button" color="neutral" variant="ghost" class="justify-start">
                                <UIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-4 shrink-0 text-muted" />
                                <h3 class="text-base font-semibold text-highlighted">Description</h3>
                            </UButton>
                        </template>

                        <template #content>
                            <UCard class="mt-3" :ui="{ body: 'bg-elevated p-4' }">
                                <template v-if="descriptionReady">
                                    <div
                                        v-if="descriptionText"
                                        class="text-sm wrap-anywhere text-default [&_a]:text-primary [&_a]:underline-offset-2 hover:[&_a]:underline [&_li]:text-muted [&_ol]:list-decimal [&_ol]:ps-5 [&_ul]:list-disc [&_ul]:ps-5"
                                        v-html="descriptionText"
                                    />
                                    <p v-else class="text-sm text-muted">No description.</p>
                                </template>

                                <USkeleton v-else-if="loading" class="h-16 rounded-lg" />

                                <UAlert
                                    v-else-if="failed"
                                    color="error"
                                    variant="soft"
                                    title="Could not load task details."
                                    :actions="[{ label: 'Retry', color: 'neutral', variant: 'subtle', onClick: load }]"
                                />

                                <div v-if="descriptionKnown && loading" class="mt-4 flex flex-wrap gap-2">
                                    <USkeleton v-for="n in 2" :key="n" class="h-8 w-28 rounded-lg" />
                                </div>

                                <div v-else-if="descriptionKnown && failed" class="mt-4 flex items-center gap-2 text-xs text-muted">
                                    <UIcon name="i-lucide-triangle-alert" class="size-3.5 shrink-0" />
                                    Could not load attachments.
                                    <UButton label="Retry" color="neutral" variant="link" size="xs" class="p-0" @click="load" />
                                </div>

                                <div v-else-if="detail?.media.length" class="mt-4 flex flex-wrap gap-2">
                                    <ULink
                                        v-for="file in detail.media"
                                        :key="file.uuid"
                                        :href="file.original_url?.trim() || file.url?.trim()"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm text-default ring ring-default transition-colors hover:bg-default"
                                    >
                                        <UIcon name="i-lucide-paperclip" class="size-4 shrink-0 text-muted" />
                                        {{ file.file_name }}
                                    </ULink>
                                </div>
                            </UCard>
                        </template>
                    </UCollapsible>

                    <USeparator />
                </template>

                <UCollapsible v-model:open="subtasksOpen">
                    <template #default="{ open }">
                        <UButton type="button" color="neutral" variant="ghost" class="justify-start">
                            <UIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-4 shrink-0 text-muted" />
                            <h3 class="text-base font-semibold text-highlighted">Subtasks</h3>
                            <UBadge v-if="subtaskRows.length" color="neutral" variant="subtle" size="sm" class="rounded-full">
                                {{ doneSubtaskCount }}/{{ subtaskRows.length }}
                            </UBadge>
                        </UButton>
                    </template>

                    <template #content>
                        <div class="mt-3 overflow-hidden rounded-xl ring ring-default">
                            <UButton
                                v-for="row in subtaskRows"
                                :key="row.id"
                                type="button"
                                color="neutral"
                                variant="ghost"
                                block
                                class="group flex w-full items-center gap-3 rounded-none border-t border-default p-3 text-start transition-colors first:border-t-0"
                                @click="emit('close', { open: row })"
                            >
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-md"
                                    :class="isDone(row) ? 'bg-success text-white' : 'ring ring-default'"
                                >
                                    <UIcon v-if="isDone(row)" name="i-lucide-check" class="size-3.5" />
                                </span>
                                <span
                                    class="min-w-0 flex-1 truncate text-sm"
                                    :class="isDone(row) ? 'text-dimmed line-through' : 'text-default group-hover:text-primary'"
                                >
                                    {{ row.title }}
                                </span>
                                <StatusBadge v-if="row.status" :label="row.status.name" :severity="row.status.severity" class="shrink-0" />
                            </UButton>

                            <p v-if="!subtaskRows.length" class="p-3 text-sm text-muted">No subtasks.</p>
                        </div>
                    </template>
                </UCollapsible>

                <USeparator />

                <TaskDiscussion :task-id="task.id" :initial-tab="history ? 'history' : 'comments'" :members="assignableUsers" />
            </div>
        </template>
    </USlideover>
</template>
