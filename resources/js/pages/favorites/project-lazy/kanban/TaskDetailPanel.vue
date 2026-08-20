<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, ref } from 'vue';
import type { KanbanTask, TaskActivity, TaskComment } from './types';

interface Props {
    task: KanbanTask | null;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    close: [boolean | 'edit'];
}>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as { user: { id: string; name: string; avatar_url?: string | null } }).user);
const currentUserId = computed(() => currentUser.value.id);

const activeTab = ref<'comments' | 'activity'>('comments');
const descriptionOpen = ref(true);
const subtasksOpen = ref(false);
const loadingComments = ref(false);
const loadingActivity = ref(false);
const comments = ref<TaskComment[]>([]);
const activities = ref<TaskActivity[]>([]);
const newCommentBody = ref('');
const postingComment = ref(false);

const subtaskCounts = computed(() => ({
    total: props.task?.sub_task_recursive.length ?? 0,
    done: props.task?.sub_task_recursive.filter((child) => child.progress >= 100).length ?? 0,
}));

const loadComments = async () => {
    if (!props.task) return;

    loadingComments.value = true;
    try {
        const response = await fetch(route('task.comments', props.task.id), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        comments.value = body.data ?? [];
    } finally {
        loadingComments.value = false;
    }
};

const loadActivities = async () => {
    if (!props.task) return;

    loadingActivity.value = true;
    try {
        const response = await fetch(route('task.activities', props.task.id), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        activities.value = body.activities ?? [];
    } finally {
        loadingActivity.value = false;
    }
};

const initialize = () => {
    activeTab.value = 'comments';
    descriptionOpen.value = true;
    subtasksOpen.value = false;
    comments.value = [];
    activities.value = [];
    newCommentBody.value = '';
    loadComments();
    loadActivities();
};

const submitComment = () => {
    const trimmed = newCommentBody.value.trim();
    if (!trimmed || !props.task) return;

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
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                newCommentBody.value = '';
                loadComments();
            },
            onFinish: () => {
                postingComment.value = false;
            },
        },
    );
};

const deleteComment = (comment: TaskComment) => {
    router.delete(route('comments.destroy', comment.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => loadComments(),
    });
};

const formatFieldValue = (value: string | null) => (value === null ? '—' : value);

const relativeDueDate = (task: KanbanTask) => {
    if (!task.due_date) return '—';

    const due = moment(task.due_date).startOf('day');
    const days = due.diff(moment().startOf('day'), 'days');

    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    if (days === -1) return 'Yesterday';
    if (task.is_overdue) return due.format('DD MMM YYYY');

    return due.fromNow();
};
</script>

<template>
    <USlideover :close="{ onClick: () => emits('close', false) }" @enter="initialize">
        <template #header="{ close }">
            <div v-if="task" class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p v-if="task.type" class="text-xs text-muted">{{ task.type.name }}</p>
                    <h2 class="mt-0.5 line-clamp-2 text-xl leading-tight font-bold">{{ task.title }}</h2>
                </div>
                <div class="flex shrink-0 items-center gap-0.5">
                    <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" aria-label="Edit task" @click="emits('close', 'edit')" />
                    <UButton icon="i-lucide-x" color="neutral" variant="ghost" aria-label="Close" @click="close" />
                </div>
            </div>
        </template>

        <template #body>
            <div v-if="task" class="flex flex-col gap-6">
                <div v-if="task.tags.length" class="flex flex-wrap gap-1">
                    <UBadge v-for="tag in task.tags" :key="tag.id" :color="severityColor(tag.severity)" variant="soft" size="sm">
                        {{ tag.name }}
                    </UBadge>
                </div>

                <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                    <div>
                        <p class="text-xs text-muted">Status</p>
                        <UBadge v-if="task.status" :color="severityColor(task.status.severity)" variant="subtle" class="mt-1">
                            {{ task.status.name }}
                        </UBadge>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Assignee<span v-if="task.users.length > 1">s</span></p>
                        <div v-if="task.users.length" class="mt-1 flex items-center gap-1.5">
                            <UAvatar
                                :src="task.users[0].avatar_url ?? undefined"
                                :alt="task.users[0].name"
                                :text="getInitials(task.users[0].name)"
                                size="xs"
                            />
                            <span class="truncate text-sm">{{ task.users[0].name }}</span>
                            <UBadge v-if="task.users.length > 1" color="neutral" variant="subtle" size="sm"> +{{ task.users.length - 1 }} </UBadge>
                        </div>
                        <p v-else class="mt-1 text-sm text-muted">Unassigned</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Priority</p>
                        <UBadge v-if="task.priority" :color="severityColor(task.priority.severity)" variant="subtle" class="mt-1">
                            {{ task.priority.name }}
                        </UBadge>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Due Date</p>
                        <p class="mt-1 flex items-center gap-1 text-sm" :class="task.is_overdue ? 'font-medium text-error' : ''">
                            <UIcon name="i-lucide-calendar" class="size-3.5" />
                            {{ relativeDueDate(task) }}
                        </p>
                    </div>
                </div>

                <USeparator />

                <div>
                    <button type="button" class="flex w-full items-center justify-between" @click="descriptionOpen = !descriptionOpen">
                        <span class="text-base font-semibold">Description</span>
                        <UIcon :name="descriptionOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4 text-muted" />
                    </button>
                    <div v-if="descriptionOpen" class="mt-2">
                        <div v-if="task.description" class="text-sm wrap-break-word" v-html="task.description" />
                        <p v-else class="text-sm text-muted">No description</p>
                    </div>
                </div>

                <div>
                    <button type="button" class="flex w-full items-center justify-between" @click="subtasksOpen = !subtasksOpen">
                        <span class="flex items-center gap-2 text-base font-semibold">
                            Subtasks
                            <UBadge color="neutral" variant="subtle" size="sm">{{ subtaskCounts.done }}/{{ subtaskCounts.total }}</UBadge>
                        </span>
                        <UIcon :name="subtasksOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4 text-muted" />
                    </button>
                    <div v-if="subtasksOpen" class="mt-2 flex flex-col gap-2">
                        <div v-if="task.sub_task_recursive.length" class="flex flex-col gap-2">
                            <div
                                v-for="child in task.sub_task_recursive"
                                :key="child.id"
                                class="flex items-center justify-between gap-2 rounded-lg border border-default p-2"
                            >
                                <span class="truncate text-sm">{{ child.title }}</span>
                                <UBadge v-if="child.status" :color="severityColor(child.status.severity)" variant="subtle" size="sm">
                                    {{ child.status.name }}
                                </UBadge>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted">No subtasks</p>
                    </div>
                </div>

                <USeparator />

                <div class="flex items-center gap-4 border-b border-default">
                    <button
                        type="button"
                        class="border-b-2 pb-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'comments' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-default'"
                        @click="activeTab = 'comments'"
                    >
                        Comments
                    </button>
                    <button
                        type="button"
                        class="border-b-2 pb-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'activity' ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-default'"
                        @click="activeTab = 'activity'"
                    >
                        Activity
                    </button>
                </div>

                <div v-if="activeTab === 'comments'" class="flex flex-col gap-4">
                    <div v-if="loadingComments" class="flex justify-center py-4">
                        <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
                    </div>

                    <p v-else-if="!comments.length" class="py-4 text-center text-sm text-muted">No comments yet.</p>

                    <div v-else class="flex flex-col gap-4">
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
                    </div>
                </div>

                <div v-else class="flex flex-col gap-4">
                    <div v-if="loadingActivity" class="flex justify-center py-4">
                        <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
                    </div>

                    <p v-else-if="!activities.length" class="py-4 text-center text-sm text-muted">No history yet.</p>

                    <div v-else class="flex flex-col gap-4">
                        <div v-for="activity in activities" :key="activity.id" class="flex flex-col gap-1.5 border-l-2 border-default pl-3">
                            <p class="text-sm">
                                <span class="font-medium">{{ activity.causer?.name ?? 'System' }}</span>
                                updated this task
                                <span class="text-muted">· {{ moment(activity.created_at).fromNow() }}</span>
                            </p>
                            <ul class="flex flex-col gap-1">
                                <li v-for="(field, index) in activity.changed_fields" :key="index" class="text-xs">
                                    <span class="font-medium">{{ field.field }}</span
                                    >:
                                    <span class="text-muted line-through">
                                        <span v-if="field.field === 'Description'" v-html="formatFieldValue(field.old_value)" />
                                        <span v-else>{{ formatFieldValue(field.old_value) }}</span>
                                    </span>
                                    →
                                    <span v-if="field.field === 'Description'" v-html="field.new_value ?? 'Removed'" />
                                    <span v-else>{{ field.new_value ?? 'Removed' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-if="activeTab === 'comments'" #footer>
            <div class="flex w-full items-center gap-2">
                <UAvatar :src="currentUser.avatar_url ?? undefined" :alt="currentUser.name" :text="getInitials(currentUser.name)" size="sm" />
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
        </template>
    </USlideover>
</template>
