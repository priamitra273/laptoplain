<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import { Head, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, onMounted, ref } from 'vue';
import TaskActivityModal from '../../project-lazy/task/TaskActivityModal.vue';
import TaskFormDrawer from '../../project-lazy/kanban/TaskFormDrawer.vue';
import type { KanbanBadge, KanbanTask, KanbanUser } from '../../project-lazy/kanban/types';
import type {
    TaskBadge,
    TaskCategoryOption,
    TaskDetailComment,
    TaskDetailProject,
    TaskDetailTask,
    TaskDetailUser,
    TaskParent,
    TaskStatusOption,
} from './types';

interface Props {
    task: TaskDetailTask;
    project: TaskDetailProject;
    assignedUsers: TaskDetailUser[];
    assignableUsers: TaskDetailUser[];
    creator: TaskDetailUser | null;
    statuses: TaskStatusOption[];
    priorities: TaskBadge[];
    types: TaskBadge[];
    categories: TaskCategoryOption[];
    tags: TaskBadge[];
    isTaskMember: boolean;
    isOwner: boolean;
    comments: TaskDetailComment[];
}

const props = defineProps<Props>();

const page = usePage();
const currentUserId = computed(() => (page.props.auth as { user: { id: string } }).user.id);

const toast = useToast();
const overlay = useOverlay();
const confirm = useConfirmDialog();
const taskForm = overlay.create(TaskFormDrawer);
const activityModal = overlay.create(TaskActivityModal);

const descriptionOpen = ref(true);
const subtasksOpen = ref(true);
const attachmentsOpen = ref(true);
const newCommentBody = ref('');
const postingComment = ref(false);

const parents = ref<TaskParent[]>([]);
const loadingParents = ref(true);

const category = computed(() => props.categories.find((option) => option.id === props.task.task_category_id) ?? null);

const subtaskCounts = computed(() => ({
    total: props.task.sub_task_recursive.length,
    done: props.task.sub_task_recursive.filter((child) => child.progress >= 100).length,
}));

const isOverdue = computed(() => {
    if (!props.task.due_date || props.task.progress >= 100) return false;
    return moment(props.task.due_date).isBefore(moment(), 'day');
});

const relativeDueDate = computed(() => {
    if (!props.task.due_date) return '—';

    const due = moment(props.task.due_date).startOf('day');
    const days = due.diff(moment().startOf('day'), 'days');

    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    if (days === -1) return 'Yesterday';
    if (isOverdue.value) return due.format('DD MMM YYYY');

    return due.fromNow();
});

const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const fetchParents = async () => {
    loadingParents.value = true;
    try {
        const response = await fetch(route('task.parents', props.task.id), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        parents.value = body.data ?? [];
    } finally {
        loadingParents.value = false;
    }
};

onMounted(fetchParents);

/** TaskDetailTask doesn't carry a full recursive sub_task_recursive tree — TaskFormDrawer
 * only needs it to exclude the task's own descendants from the parent picker, so one
 * level of children (what the backend already loads) is enough for that purpose. */
const toKanbanTaskShape = (): KanbanTask => ({
    id: props.task.id,
    parent_id: props.task.parent_id,
    title: props.task.title,
    description: props.task.description,
    start_date: props.task.start_date,
    due_date: props.task.due_date,
    progress: props.task.progress,
    is_overdue: isOverdue.value,
    status: props.task.status,
    priority: props.task.priority,
    type: props.task.type,
    category: category.value,
    users: props.assignedUsers as KanbanUser[],
    tags: props.task.tags,
    sub_task_recursive: props.task.sub_task_recursive.map((child) => ({
        id: child.id,
        parent_id: props.task.id,
        title: child.title,
        description: null,
        start_date: null,
        due_date: null,
        progress: child.progress,
        is_overdue: false,
        status: child.status,
        priority: child.priority,
        type: child.type,
        users: child.users as KanbanUser[],
        tags: [],
        sub_task_recursive: [],
    })),
});

const reloadTask = () => router.reload({ only: ['task', 'comments'] });

const openEdit = async () => {
    const saved = await taskForm.open({
        task: toKanbanTaskShape(),
        projectId: props.project.id,
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories as KanbanBadge[],
        assignableUsers: props.assignableUsers as KanbanUser[],
        tags: props.tags as KanbanBadge[],
    });

    if (saved) reloadTask();
};

const openAddSubtask = async () => {
    const saved = await taskForm.open({
        projectId: props.project.id,
        defaultParentId: props.task.id,
        statuses: props.statuses,
        priorities: props.priorities,
        types: props.types,
        categories: props.categories as KanbanBadge[],
        assignableUsers: props.assignableUsers as KanbanUser[],
        tags: props.tags as KanbanBadge[],
    });

    if (saved) reloadTask();
};

const openActivityLog = () => activityModal.open({ taskId: props.task.id, taskTitle: props.task.title });

const removeTask = async () => {
    const confirmed = await confirm({
        title: 'Remove Task',
        description: `Remove task "${props.task.title}"? This action cannot be undone.`,
    });

    if (confirmed) {
        router.delete(route('project.tasks.destroy', { projectEncoded: props.project.id, task: props.task.id }), {
            onSuccess: () => router.visit(route('project.show.kanban', { encoded: props.project.id })),
            onError: () => toast.add({ title: 'Failed', description: 'Could not delete task.', color: 'error' }),
        });
    }
};

const submitComment = () => {
    const trimmed = newCommentBody.value.trim();
    if (!trimmed) return;

    postingComment.value = true;
    router.post(
        route('comments.store'),
        {
            body: trimmed,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.task.id,
            parent_id: null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                newCommentBody.value = '';
                router.reload({ only: ['comments'] });
            },
            onFinish: () => {
                postingComment.value = false;
            },
        },
    );
};

const deleteComment = (comment: TaskDetailComment) => {
    router.delete(route('comments.destroy', comment.id), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['comments'] }),
    });
};
</script>

<template>
    <Head :title="`Task Detail - ${task.title}`" />

    <AppLayout :title="task.title">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 p-6">
            <div>
                <nav class="mb-2 flex items-center gap-1.5 overflow-x-auto text-sm text-muted">
                    <USkeleton v-if="loadingParents" class="h-4 w-48" />
                    <template v-else>
                        <ULink :href="route('project.show.kanban', { encoded: project.id })" class="shrink-0 hover:text-default">
                            {{ project.title }}
                        </ULink>
                        <template v-for="(parent, index) in parents" :key="parent.id">
                            <UIcon name="i-lucide-chevron-right" class="size-3.5 shrink-0" />
                            <ULink
                                v-if="index < parents.length - 1"
                                :href="route('task.show', parent.id)"
                                class="shrink-0 truncate hover:text-default"
                            >
                                {{ parent.title }}
                            </ULink>
                            <span v-else class="shrink-0 truncate font-medium text-default">{{ parent.title }}</span>
                        </template>
                    </template>
                </nav>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p v-if="task.type" class="text-xs text-muted">{{ task.type.name }}</p>
                        <h1 class="mt-0.5 text-2xl font-bold wrap-break-word">{{ task.title }}</h1>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <UButton label="Edit" icon="i-lucide-pencil" color="neutral" variant="outline" @click="openEdit" />
                        <UDropdownMenu
                            :items="[
                                [
                                    { label: 'Add Subtask', icon: 'i-lucide-list-plus', onSelect: openAddSubtask },
                                    { label: 'History Log', icon: 'i-lucide-history', onSelect: openActivityLog },
                                ],
                                [{ label: 'Delete Task', icon: 'i-lucide-trash', color: 'error', onSelect: removeTask }],
                            ]"
                            :content="{ align: 'end' }"
                        >
                            <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="outline" aria-label="More actions" />
                        </UDropdownMenu>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                <div class="order-last flex min-w-0 flex-col gap-6 lg:order-none lg:col-span-2">
                    <div class="rounded-xl border border-default p-4">
                        <button type="button" class="flex w-full items-center justify-between" @click="descriptionOpen = !descriptionOpen">
                            <span class="text-base font-semibold">Description</span>
                            <UIcon :name="descriptionOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4 text-muted" />
                        </button>
                        <div v-if="descriptionOpen" class="mt-3">
                            <div v-if="task.description" class="text-sm wrap-break-word" v-html="task.description" />
                            <p v-else class="text-sm text-muted">No description</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-default p-4">
                        <button type="button" class="flex w-full items-center justify-between" @click="subtasksOpen = !subtasksOpen">
                            <span class="flex items-center gap-2 text-base font-semibold">
                                Subtasks
                                <UBadge color="neutral" variant="subtle" size="sm">{{ subtaskCounts.done }}/{{ subtaskCounts.total }}</UBadge>
                            </span>
                            <UIcon :name="subtasksOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4 text-muted" />
                        </button>
                        <div v-if="subtasksOpen" class="mt-3 flex flex-col gap-2">
                            <ULink
                                v-for="child in task.sub_task_recursive"
                                :key="child.id"
                                :href="route('task.show', child.id)"
                                class="flex items-center justify-between gap-2 rounded-lg border border-default p-2 hover:bg-elevated"
                            >
                                <span class="truncate text-sm">{{ child.title }}</span>
                                <UBadge v-if="child.status" :color="severityColor(child.status.severity)" variant="subtle" size="sm">
                                    {{ child.status.name }}
                                </UBadge>
                            </ULink>
                            <p v-if="!task.sub_task_recursive.length" class="text-sm text-muted">No subtasks</p>
                            <UButton
                                label="Add Subtask"
                                icon="i-lucide-plus"
                                color="neutral"
                                variant="ghost"
                                class="mt-1 justify-start"
                                @click="openAddSubtask"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-default p-4">
                        <button type="button" class="flex w-full items-center justify-between" @click="attachmentsOpen = !attachmentsOpen">
                            <span class="flex items-center gap-2 text-base font-semibold">
                                Attachments
                                <UBadge color="neutral" variant="subtle" size="sm">{{ task.media.length }}</UBadge>
                            </span>
                            <UIcon :name="attachmentsOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4 text-muted" />
                        </button>
                        <div v-if="attachmentsOpen" class="mt-3 flex flex-col gap-2">
                            <a
                                v-for="attachment in task.media"
                                :key="attachment.uuid"
                                :href="attachment.url"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center gap-2 rounded-lg border border-default p-2 hover:bg-elevated"
                            >
                                <UIcon name="i-lucide-file" class="size-4 shrink-0 text-muted" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm">{{ attachment.file_name }}</p>
                                    <p class="text-xs text-muted">{{ formatFileSize(attachment.size) }}</p>
                                </div>
                                <UIcon name="i-lucide-download" class="size-4 shrink-0 text-muted" />
                            </a>
                            <p v-if="!task.media.length" class="text-sm text-muted">No attachments</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-default p-4">
                        <span class="text-base font-semibold">Comments</span>

                        <div class="mt-3 flex flex-col gap-4">
                            <p v-if="!comments.length" class="py-4 text-center text-sm text-muted">No comments yet.</p>

                            <div v-for="comment in comments" :key="comment.id" class="flex flex-col gap-2">
                                <div class="flex items-start gap-2">
                                    <UAvatar
                                        :src="comment.user.avatar_url ?? undefined"
                                        :alt="comment.user.name"
                                        :text="getInitials(comment.user.name)"
                                        size="sm"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold">{{ comment.user.name }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-muted">{{ moment(comment.created_at).fromNow() }}</span>
                                                <UButton
                                                    v-if="comment.user.id === currentUserId"
                                                    icon="i-lucide-trash"
                                                    color="error"
                                                    variant="ghost"
                                                    size="xs"
                                                    @click="deleteComment(comment)"
                                                />
                                            </div>
                                        </div>
                                        <div class="mt-1 rounded-lg bg-elevated p-2.5 text-sm wrap-break-word" v-html="comment.body" />
                                    </div>
                                </div>

                                <div v-if="comment.replies.length" class="ml-8 flex flex-col gap-2">
                                    <div v-for="reply in comment.replies" :key="reply.id" class="flex items-start gap-2">
                                        <UAvatar
                                            :src="reply.user.avatar_url ?? undefined"
                                            :alt="reply.user.name"
                                            :text="getInitials(reply.user.name)"
                                            size="xs"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold">{{ reply.user.name }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-muted">{{ moment(reply.created_at).fromNow() }}</span>
                                                    <UButton
                                                        v-if="reply.user.id === currentUserId"
                                                        icon="i-lucide-trash"
                                                        color="error"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="deleteComment(reply)"
                                                    />
                                                </div>
                                            </div>
                                            <div class="mt-1 rounded-lg bg-elevated p-2 text-xs wrap-break-word" v-html="reply.body" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 border-t border-default pt-4">
                                <UInput
                                    v-model="newCommentBody"
                                    placeholder="Add a comment..."
                                    class="w-full"
                                    :ui="{ base: 'rounded-full' }"
                                    @keydown.enter="submitComment"
                                />
                                <UButton
                                    icon="i-lucide-send"
                                    :loading="postingComment"
                                    :disabled="!newCommentBody.trim() || postingComment"
                                    class="rounded-full"
                                    @click="submitComment"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="order-first flex min-w-0 flex-col gap-4 rounded-xl border border-default p-4 lg:sticky lg:top-20 lg:order-none">
                    <div>
                        <p class="text-xs text-muted">Status</p>
                        <UBadge v-if="task.status" :color="severityColor(task.status.severity)" variant="subtle" class="mt-1">
                            {{ task.status.name }}
                        </UBadge>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Priority</p>
                        <UBadge v-if="task.priority" :color="severityColor(task.priority.severity)" variant="subtle" class="mt-1">
                            {{ task.priority.name }}
                        </UBadge>
                    </div>
                    <div v-if="category">
                        <p class="text-xs text-muted">Category</p>
                        <UBadge :color="severityColor(category.severity)" variant="subtle" class="mt-1">
                            <UIcon v-if="category.icon" :name="category.icon" class="size-3.5" />
                            {{ category.name }}
                        </UBadge>
                    </div>

                    <USeparator />

                    <div>
                        <p class="text-xs text-muted">Assignee<span v-if="assignedUsers.length > 1">s</span></p>
                        <div v-if="assignedUsers.length" class="mt-1.5 flex flex-col gap-1.5">
                            <div v-for="user in assignedUsers" :key="user.id" class="flex items-center gap-1.5">
                                <UAvatar :src="user.avatar_url ?? undefined" :alt="user.name" :text="getInitials(user.name)" size="xs" />
                                <span class="truncate text-sm">{{ user.name }}</span>
                            </div>
                        </div>
                        <p v-else class="mt-1 text-sm text-muted">Unassigned</p>
                    </div>

                    <USeparator />

                    <div>
                        <p class="text-xs text-muted">Start Date</p>
                        <p class="mt-1 text-sm">{{ task.start_date ? moment(task.start_date).format('DD MMM YYYY') : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Due Date</p>
                        <p class="mt-1 flex items-center gap-1 text-sm" :class="isOverdue ? 'font-medium text-error' : ''">
                            <UIcon name="i-lucide-calendar" class="size-3.5" />
                            {{ relativeDueDate }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Progress</p>
                        <UProgress :model-value="task.progress" class="mt-1.5" />
                        <p class="mt-1 text-xs text-muted">{{ task.progress }}%</p>
                    </div>

                    <USeparator v-if="creator" />

                    <div v-if="creator">
                        <p class="text-xs text-muted">Created by</p>
                        <div class="mt-1.5 flex items-center gap-1.5">
                            <UAvatar :src="creator.avatar_url ?? undefined" :alt="creator.name" :text="getInitials(creator.name)" size="xs" />
                            <span class="truncate text-sm">{{ creator.name }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
