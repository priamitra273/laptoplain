<script setup lang="ts">
import FieldLabel from '@/components/ui/FieldLabel.vue';
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import ProgressWithLabel from '@/components/common/ProgressWithLabel.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import TaskDiscussion from '@/components/task/TaskDiscussion.vue';
import TaskCategoryBadge from '@/components/task/TaskCategoryBadge.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import TaskEditDrawer from '@/pages/favorites/project/detail/TaskEditDrawer.vue';
import type { ShellProject, TaskOption, TaskOptionUser } from '@/pages/favorites/project/detail/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { daysUntil, formatDate, formatRelativeDay } from '@/lib/date';
import { formatFileSize, getInitials, isTaskStatusDone, plural } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface DetailBadge {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface DetailUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

interface DetailMedia {
    uuid: string;
    file_name: string;
    size: number;
    mime_type: string;
    url: string;
    created_at: string;
}

interface DetailSubtask {
    id: string;
    title: string;
    status: DetailBadge | null;
}

interface DetailTask {
    id: string;
    code: string;
    title: string;
    description: string | null;
    progress: number;
    start_date: string | null;
    due_date: string | null;
    updated_at: string;
    parent_id: string | null;
    parent: { id: string; title: string } | null;
    status: DetailBadge | null;
    priority: DetailBadge | null;
    type: DetailBadge | null;
    category: (DetailBadge & { icon: string | null }) | null;
    tags: DetailBadge[];
    sub_task_recursive: DetailSubtask[];
    media: DetailMedia[];
}

const props = defineProps<{
    task: DetailTask;
    project: ShellProject;
    assignedUsers: DetailUser[];
    assignableUsers: TaskOptionUser[];
    creator: DetailUser | null;
    statuses: TaskOption[];
    priorities: TaskOption[];
    types: TaskOption[];
    categories: TaskOption[];
    tags: TaskOption[];
    isOwner: boolean;
    isTaskMember: boolean;
}>();

const overlay = useOverlay();
const editDrawer = overlay.create(TaskEditDrawer);

const subtasks = computed(() => props.task.sub_task_recursive ?? []);
const doneSubtaskCount = computed(() => subtasks.value.filter((subtask) => isTaskStatusDone(subtask.status?.name)).length);

const dueInDays = computed(() => daysUntil(props.task.due_date));
const isOverdue = computed(() => dueInDays.value !== null && dueInDays.value < 0 && !isTaskStatusDone(props.task.status?.name));

const canEdit = computed(() => props.isOwner || props.isTaskMember);

const edit = async () => {
    const changed = await editDrawer.open({
        projectId: props.project.id,
        project: props.project,
        task: {
            id: props.task.id,
            title: props.task.title,
            parent_id: props.task.parent_id,
            status: props.task.status ? { id: props.task.status.id } : null,
            priority: props.task.priority ? { id: props.task.priority.id } : null,
            category: props.task.category ? { id: props.task.category.id } : null,
        },
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories,
        tags: props.tags,
        assignableUsers: props.assignableUsers,
    });

    if (changed) {
        router.reload();
    }
};
</script>

<template>
    <AppLayout :title="task.title">
        <Head :title="task.title" />

        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-col gap-2">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <TaskTypeBadge v-if="task.type" :label="task.type.name" :severity="task.type.severity" />
                            <span class="shrink-0 font-mono text-xs text-muted">{{ task.code }}</span>
                            <span class="shrink-0 text-xs text-dimmed">·</span>
                            <span class="shrink-0 text-xs text-muted">updated {{ formatRelativeDay(task.updated_at) }}</span>
                        </div>

                        <h1 class="text-2xl leading-tight font-bold wrap-anywhere text-highlighted">{{ task.title }}</h1>
                    </div>

                    <UButton v-if="canEdit" label="Edit" icon="i-lucide-pencil" color="neutral" variant="subtle" @click="edit" />
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <UCard>
                        <UCollapsible default-open>
                            <template #default="{ open }">
                                <button type="button" class="flex w-full items-center gap-2">
                                    <UIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-4 shrink-0 text-muted" />
                                    <h2 class="text-base font-semibold text-highlighted">Description</h2>
                                </button>
                            </template>

                            <template #content>
                                <div
                                    v-if="task.description"
                                    class="mt-3 text-sm wrap-anywhere text-default [&_a]:text-primary [&_a]:underline-offset-2 hover:[&_a]:underline [&_li]:text-muted [&_ol]:list-decimal [&_ol]:ps-5 [&_ul]:list-disc [&_ul]:ps-5"
                                    v-html="task.description"
                                />
                                <p v-else class="mt-3 text-sm text-muted">No description.</p>
                            </template>
                        </UCollapsible>
                    </UCard>

                    <UCard>
                        <UCollapsible default-open>
                            <template #default="{ open }">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="flex min-w-0 items-center gap-2">
                                        <UIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-4 shrink-0 text-muted" />
                                        <h2 class="text-base font-semibold text-highlighted">Subtasks</h2>
                                        <UBadge v-if="subtasks.length" color="neutral" variant="subtle" size="sm" class="rounded-full">
                                            {{ doneSubtaskCount }}/{{ subtasks.length }}
                                        </UBadge>
                                    </button>

                                    <ProgressWithLabel
                                        v-if="subtasks.length"
                                        :value="(doneSubtaskCount / subtasks.length) * 100"
                                        bar-aria-label="Subtask completion"
                                        compact
                                        class="ms-auto max-w-40"
                                    />
                                </div>
                            </template>

                            <template #content>
                                <div v-if="subtasks.length" class="mt-3 divide-y divide-default">
                                    <Link
                                        v-for="subtask in subtasks"
                                        :key="subtask.id"
                                        :href="route('task.show', { task: subtask.id })"
                                        class="flex items-center gap-3 px-1 py-2.5 hover:bg-elevated/50"
                                    >
                                        <UIcon
                                            :name="isTaskStatusDone(subtask.status?.name) ? 'i-lucide-circle-check' : 'i-lucide-circle'"
                                            class="size-4 shrink-0"
                                            :class="isTaskStatusDone(subtask.status?.name) ? 'text-success' : 'text-dimmed'"
                                        />
                                        <span
                                            class="min-w-0 flex-1 truncate text-sm text-default"
                                            :class="isTaskStatusDone(subtask.status?.name) ? 'text-muted line-through' : ''"
                                        >
                                            {{ subtask.title }}
                                        </span>
                                        <StatusBadge v-if="subtask.status" :label="subtask.status.name" :severity="subtask.status.severity" />
                                    </Link>
                                </div>
                                <p v-else class="mt-3 text-sm text-muted">No subtasks.</p>
                            </template>
                        </UCollapsible>
                    </UCard>

                    <UCard>
                        <UCollapsible default-open>
                            <template #default="{ open }">
                                <button type="button" class="flex w-full items-center gap-2">
                                    <UIcon :name="open ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'" class="size-4 shrink-0 text-muted" />
                                    <h2 class="text-base font-semibold text-highlighted">Attachments</h2>
                                    <UBadge v-if="task.media.length" color="neutral" variant="subtle" size="sm" class="rounded-full">
                                        {{ task.media.length }}
                                    </UBadge>
                                </button>
                            </template>

                            <template #content>
                                <div v-if="task.media.length" class="mt-3 flex flex-col gap-2">
                                    <a
                                        v-for="file in task.media"
                                        :key="file.uuid"
                                        :href="file.url"
                                        target="_blank"
                                        class="flex items-center gap-3 rounded-lg border border-default bg-elevated/50 px-3 py-2 hover:border-primary"
                                    >
                                        <UIcon name="i-lucide-file-text" class="size-4 shrink-0 text-muted" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-highlighted">{{ file.file_name }}</p>
                                            <p class="text-xs text-dimmed">{{ formatRelativeDay(file.created_at) }}</p>
                                        </div>
                                        <span class="shrink-0 font-mono text-xs text-muted">{{ formatFileSize(file.size) }}</span>
                                    </a>
                                </div>
                                <p v-else class="mt-3 text-sm text-muted">No attachments.</p>
                            </template>
                        </UCollapsible>
                    </UCard>

                    <UCard>
                        <TaskDiscussion :task-id="task.id" :members="assignableUsers" />
                    </UCard>
                </div>

                <div class="flex flex-col gap-5">
                    <UCard :ui="{ body: 'flex flex-col gap-4' }">
                        <div>
                            <FieldLabel title="Status" />
                            <StatusBadge v-if="task.status" :label="task.status.name" :severity="task.status.severity" class="mt-1.5" />
                            <p v-else class="mt-1.5 text-sm text-muted">—</p>
                        </div>

                        <div>
                            <FieldLabel title="Priority" />
                            <PriorityIcon v-if="task.priority" :label="task.priority.name" :severity="task.priority.severity" class="mt-1.5" />
                            <p v-else class="mt-1.5 text-sm text-muted">—</p>
                        </div>

                        <div>
                            <FieldLabel title="Type & category" />
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <TaskTypeBadge v-if="task.type" :label="task.type.name" :severity="task.type.severity" />
                                <TaskCategoryBadge v-if="task.category" :category="task.category" />
                                <span v-if="!task.type && !task.category" class="text-sm text-muted">—</span>
                            </div>
                        </div>
                    </UCard>

                    <UCard :ui="{ body: 'flex flex-col gap-4' }">
                        <div>
                            <FieldLabel :title="plural(assignedUsers.length, 'Assignee')" />
                            <div v-if="assignedUsers.length" class="mt-2 flex flex-col gap-2">
                                <div v-for="user in assignedUsers" :key="user.id" class="flex min-w-0 items-center gap-2">
                                    <UAvatar :src="user.avatar_url ?? undefined" :alt="user.name" :text="getInitials(user.name)" size="xs" />
                                    <span class="min-w-0 truncate text-sm text-default">{{ user.name }}</span>
                                </div>
                            </div>
                            <p v-else class="mt-1.5 text-sm text-muted">Unassigned</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <FieldLabel title="Start date" />
                                <p class="mt-1.5 text-sm text-default">{{ task.start_date ? formatDate(task.start_date) : '—' }}</p>
                            </div>
                            <div>
                                <FieldLabel title="Due date" />
                                <p class="mt-1.5 text-sm" :class="isOverdue ? 'font-medium text-error' : 'text-default'">
                                    {{ task.due_date ? formatDate(task.due_date) : '—' }}
                                </p>
                                <p v-if="isOverdue && dueInDays !== null" class="text-xs text-error">
                                    {{ plural(Math.abs(dueInDays), 'day') }} overdue
                                </p>
                            </div>
                        </div>

                        <div>
                            <FieldLabel title="Progress" />
                            <ProgressWithLabel :value="task.progress" bar-aria-label="Task progress" class="mt-2" />
                            <p v-if="subtasks.length" class="mt-1 text-xs text-muted">
                                {{ doneSubtaskCount }}/{{ subtasks.length }} subtasks completed
                            </p>
                        </div>
                    </UCard>

                    <UCard :ui="{ body: 'flex flex-col gap-4' }">
                        <div>
                            <FieldLabel title="Tags" />
                            <div v-if="task.tags.length" class="mt-2 flex flex-wrap gap-1.5">
                                <StatusBadge v-for="tag in task.tags" :key="tag.id" :label="tag.name" :severity="tag.severity" />
                            </div>
                            <p v-else class="mt-1.5 text-sm text-muted">—</p>
                        </div>

                        <div v-if="task.parent">
                            <FieldLabel title="Created by" />
                            <Link
                                :href="route('task.show', { task: task.parent.id })"
                                class="mt-1.5 flex min-w-0 items-center gap-1.5 text-sm text-primary hover:underline"
                            >
                                <UIcon name="i-lucide-corner-down-right" class="size-4 shrink-0" />
                                <span class="min-w-0 truncate">{{ task.parent.title }}</span>
                            </Link>
                        </div>

                        <div v-if="creator">
                            <p class="text-xs font-semibold tracking-[0.06em] text-muted uppercase">Created by</p>
                            <div class="mt-2 flex min-w-0 items-center gap-2">
                                <UAvatar :src="creator.avatar_url ?? undefined" :alt="creator.name" :text="getInitials(creator.name)" size="xs" />
                                <span class="min-w-0 truncate text-sm text-default">{{ creator.name }}</span>
                            </div>
                        </div>
                    </UCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
