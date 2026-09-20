<script setup lang="ts">
import CommentReactions from '@/components/CommentReactions.vue';
import MentionEditor from '@/components/MentionEditor.vue';
import { formatRelativeDay } from '@/lib/date';
import { fetchJson, getInitials } from '@/lib/utils';
import { router, usePage } from '@inertiajs/vue3';
import type { TimelineItem } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';

interface CommentUser {
    id: string;
    name: string;
    avatar_url?: string | null;
}

interface TaskComment {
    id: string;
    body: string;
    reactions: { reaction: string; count: number }[] | null;
    current_user_reaction: string | null;
    user: CommentUser;
    replies: TaskComment[];
    created_at: string;
}

interface TaskActivity {
    id: string;
    causer: CommentUser | null;
    changed_fields: { field: string; old_value: string | null; new_value: string | null }[];
    created_at: string;
}

/** `members` mengisi daftar pilihan saat mengetik `@`. Kosong berarti mention tidak menawarkan siapa pun. */
const props = defineProps<{ taskId: string; initialTab?: 'comments' | 'history'; members?: CommentUser[] }>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as { user: CommentUser }).user);
const toast = useToast();

const tab = ref<'comments' | 'history'>(props.initialTab ?? 'comments');
const tabsRoot = ref<HTMLElement | null>(null);

watch(tab, () => {
    tabsRoot.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
const comments = ref<TaskComment[]>([]);
const activities = ref<TaskActivity[]>([]);

/**
 * Loading dan error dipisah per resource: comments dan activities dimuat berbarengan, tapi satu
 * gagal tidak boleh membuat yang lain ikut dibaca sebagai "memang kosong".
 */
const commentsLoading = ref(true);
const commentsError = ref<string | null>(null);
const activitiesLoading = ref(true);
const activitiesError = ref<string | null>(null);

const draft = ref('');
const replyTo = ref<string | null>(null);
const replyDraft = ref('');
const sending = ref(false);

const getJson = async (url: string) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

    if (!response.ok) {
        throw new Error('request failed');
    }

    return response.json();
};

const errorMessage = (error: unknown, fallback: string) => (error instanceof Error ? error.message : fallback);

const fetchComments = async () => {
    comments.value = (await getJson(route('task.comments', props.taskId))).data ?? [];
};

const fetchActivities = async () => {
    activities.value = (await getJson(route('task.activities', props.taskId))).activities ?? [];
};

/**
 * Dipakai untuk muat awal dan tombol Retry — keduanya perlu menampilkan status loading/error.
 *
 * Bukan `errorMessage(error, fallback)`: `getJson` selalu melempar `Error('request failed')`
 * yang generik (bukan pesan dari server), jadi `error.message`-nya tidak pernah informatif.
 * Fallback per konteks ini yang harus tampil, bukan teks generik itu.
 */
const loadComments = async () => {
    commentsLoading.value = true;
    commentsError.value = null;

    try {
        await fetchComments();
    } catch {
        commentsError.value = 'Could not load comments.';
    } finally {
        commentsLoading.value = false;
    }
};

const loadActivities = async () => {
    activitiesLoading.value = true;
    activitiesError.value = null;

    try {
        await fetchActivities();
    } catch {
        activitiesError.value = 'Could not load history.';
    } finally {
        activitiesLoading.value = false;
    }
};

/**
 * Dipakai setelah kirim/hapus/reaksi berhasil di server. Gagal di sini tidak boleh menimpa
 * daftar yang sudah tampil dengan layar error — komentarnya sudah tersimpan, cuma daftarnya
 * yang belum sinkron, jadi cukup diberi tahu lewat toast.
 *
 * Sama seperti loadComments/loadActivities: `fetchComments` bersumber dari `getJson`, jadi
 * `error.message`-nya selalu 'request failed' yang generik — pesan tetapnya yang dipakai.
 */
const refreshComments = async () => {
    try {
        await fetchComments();
    } catch {
        toast.add({ title: 'Failed', description: 'Could not refresh the comment list.', color: 'error' });
    }
};

/**
 * `watch` dipakai bukan `onMounted`: navigasi antar subtask di panel detail membuka task baru
 * lewat overlay yang sama, dan Vue mempertahankan instance komponen ini (bukan memasangnya
 * ulang) — `onMounted` tidak pernah jalan lagi untuk task berikutnya, sehingga komentar dan
 * history task sebelumnya tetap tersisa.
 */
watch(
    () => props.taskId,
    () => {
        draft.value = '';
        replyTo.value = null;
        replyDraft.value = '';
        loadComments();
        loadActivities();
    },
    { immediate: true },
);

/** Carbon mengirim timestamp penuh; `formatRelativeDay` hanya menerima tanggal, jadi jamnya dipotong. */
const relativeTime = (timestamp: string) => formatRelativeDay(timestamp.slice(0, 10));

/** Ikon dipilih dari label field yang berubah; label-labelnya dari `activityFieldLabels()` di server. */
const HISTORY_ICONS: Record<string, string> = {
    Status: 'i-lucide-circle-dot',
    Priority: 'i-lucide-flag',
    Type: 'i-lucide-tag',
    'Parent Task': 'i-lucide-corner-down-right',
    Title: 'i-lucide-pencil',
    Description: 'i-lucide-align-left',
    'Start Date': 'i-lucide-calendar',
    'Due Date': 'i-lucide-calendar-clock',
    Progress: 'i-lucide-trending-up',
    Owner: 'i-lucide-user',
    Project: 'i-lucide-folder',
    Archived: 'i-lucide-archive',
};

interface HistoryEntry {
    actor: string;
    action: string;
    at: string;
    sortKey: string;
    changes: { field: string; old: string; new: string }[];
    quote?: string;
}

/** Body komentar berupa HTML; untuk kutipan singkat di timeline tag-nya dibuang. */
const stripHtml = (value: string) => value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

/**
 * History menggabungkan dua sumber: log aktivitas dari server dan komentar yang sudah dimuat
 * komponen ini. Pembuatan task, assignee, dan lampiran tidak ada di sini — yang pertama dibuang
 * filter `'updated'` di `TaskActivityController`, dua lainnya tidak dicatat `LogsActivityTask`.
 */
const historyEntries = computed<(TimelineItem & HistoryEntry)[]>(() => {
    const fromActivities = activities.value.map((activity) => {
        const fields = activity.changed_fields ?? [];
        const single = fields.length === 1 ? fields[0].field : null;

        return {
            value: `activity-${activity.id}`,
            icon: single ? (HISTORY_ICONS[single] ?? 'i-lucide-pencil') : 'i-lucide-pencil',
            actor: activity.causer?.name ?? 'System',
            action: single ? `changed ${single.toLowerCase()}` : 'updated this task',
            at: relativeTime(activity.created_at),
            sortKey: activity.created_at,
            changes: fields.map((field) => ({
                field: field.field,
                old: field.old_value ?? 'None',
                new: field.new_value ?? 'Removed',
            })),
        };
    });

    const fromComments = comments.value
        .flatMap((comment) => [comment, ...(comment.replies ?? [])])
        .map((comment) => ({
            value: `comment-${comment.id}`,
            icon: 'i-lucide-message-square',
            actor: comment.user.name,
            action: 'commented on this task',
            at: relativeTime(comment.created_at),
            sortKey: comment.created_at,
            changes: [],
            quote: stripHtml(comment.body).slice(0, 120),
        }));

    return [...fromActivities, ...fromComments].sort((a, b) => b.sortKey.localeCompare(a.sortKey));
});

/**
 * Endpoint komentar menjawab dengan `back()`, bukan JSON, jadi jalurnya lewat router Inertia
 * — bukan `fetchJson` seperti endpoint kanban lainnya.
 */
const postComment = (body: string, parentId: string | null, onDone: () => void) => {
    const trimmed = body.trim();

    if (!trimmed || sending.value) {
        return;
    }

    sending.value = true;

    router.post(
        route('comments.store'),
        {
            body: trimmed,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.taskId,
            parent_id: parentId,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                onDone();
                refreshComments();
            },
            onError: () => {
                toast.add({ title: 'Failed', description: 'Could not send the comment.', color: 'error' });
            },
            onFinish: () => {
                sending.value = false;
            },
        },
    );
};

const submitComment = () =>
    postComment(draft.value, null, () => {
        draft.value = '';
    });

const submitReply = (parentId: string) =>
    postComment(replyDraft.value, parentId, () => {
        replyDraft.value = '';
        replyTo.value = null;
    });

const deleteComment = (comment: TaskComment) => {
    router.delete(route('comments.destroy', comment.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => refreshComments(),
        onError: () => toast.add({ title: 'Failed', description: 'Could not delete the comment.', color: 'error' }),
    });
};

const react = async (comment: TaskComment, reaction: string) => {
    try {
        await fetchJson(route('comments.react', comment.id), 'POST', { reaction });
        await refreshComments();
    } catch (error) {
        toast.add({ title: 'Failed', description: errorMessage(error, 'Could not react to the comment.'), color: 'error' });
    }
};

const commentActions = (comment: TaskComment) => [
    {
        label: 'Delete',
        icon: 'i-lucide-trash-2',
        color: 'error' as const,
        onSelect: () => deleteComment(comment),
    },
];

const commentCount = computed(() => comments.value.reduce((total, comment) => total + 1 + comment.replies.length, 0));

/**
 * `slot` merutekan tiap item ke `<template #comments>`/`#history` di bawah. `variant="link"`
 * dan pola `#trailing` mengikuti UTabs milik ProjectShellLayout, satu-satunya pemakai lain
 * di proyek ini.
 */
const tabItems = computed(() => [
    { label: 'Comments', value: 'comments' as const, slot: 'comments', badge: commentCount.value },
    { label: 'History', value: 'history' as const, slot: 'history', badge: historyEntries.value.length },
]);
</script>

<template>
    <div ref="tabsRoot">
    <UTabs v-model="tab" :items="tabItems" variant="link" class="w-full" :ui="{ list: 'overflow-x-auto overflow-y-hidden' }">
        <template #trailing="{ item }">
            <span class="text-dimmed text-xs tabular-nums">{{ item.badge }}</span>
        </template>

        <template #comments>
        <div class="flex flex-col gap-5 pt-5">
            <div v-if="commentsLoading" class="flex justify-center py-8">
                <UIcon name="i-lucide-loader-circle" class="text-muted size-5 animate-spin" />
            </div>

            <UAlert
                v-else-if="commentsError"
                color="error"
                variant="soft"
                :title="commentsError"
                :actions="[{ label: 'Retry', color: 'neutral', variant: 'subtle', onClick: loadComments }]"
            />

            <template v-else>
            <p v-if="!comments.length" class="text-muted py-2 text-sm">No comments yet.</p>

            <div v-for="comment in comments" :key="comment.id" class="flex flex-col gap-3">
                <div class="flex gap-3">
                    <UAvatar
                        :src="comment.user.avatar_url ?? undefined"
                        :alt="comment.user.name"
                        :text="getInitials(comment.user.name)"
                        size="md"
                        class="shrink-0"
                    />

                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-highlighted text-sm font-semibold">{{ comment.user.name }}</span>
                            <span class="text-dimmed text-xs">·</span>
                            <span class="text-muted text-xs">{{ relativeTime(comment.created_at) }}</span>
                        </div>

                        <div class="bg-elevated ring-default rounded-xl p-3 text-sm wrap-anywhere ring" v-html="comment.body" />

                        <div class="flex flex-wrap items-center gap-2">
                            <CommentReactions
                                :reactions="comment.reactions ?? []"
                                :current-user-reaction="comment.current_user_reaction"
                                @react="(reaction: string) => react(comment, reaction)"
                            />

                            <UButton
                                icon="i-lucide-reply"
                                label="Reply"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="replyTo = replyTo === comment.id ? null : comment.id"
                            />

                            <UDropdownMenu v-if="comment.user.id === currentUser.id" :items="commentActions(comment)">
                                <UButton icon="i-lucide-ellipsis" color="neutral" variant="ghost" size="xs" square aria-label="Comment actions" />
                            </UDropdownMenu>
                        </div>

                        <div v-if="replyTo === comment.id" class="flex items-start gap-2">
                            <MentionEditor
                                v-model="replyDraft"
                                :members="members"
                                placeholder="Write a reply… @ to mention"
                                min-height="64px"
                                class="min-w-0 flex-1"
                                @keydown.escape="replyTo = null"
                            />
                            <UButton
                                icon="i-lucide-send"
                                :loading="sending"
                                :disabled="!replyDraft.trim() || sending"
                                aria-label="Send reply"
                                @click="submitReply(comment.id)"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="comment.replies.length" class="ms-11 flex flex-col gap-3">
                    <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-3">
                        <UAvatar
                            :src="reply.user.avatar_url ?? undefined"
                            :alt="reply.user.name"
                            :text="getInitials(reply.user.name)"
                            size="sm"
                            class="shrink-0"
                        />

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-highlighted text-sm font-semibold">{{ reply.user.name }}</span>
                                <span class="text-dimmed text-xs">·</span>
                                <span class="text-muted text-xs">{{ relativeTime(reply.created_at) }}</span>
                            </div>

                            <div class="bg-elevated ring-default rounded-xl p-3 text-sm wrap-anywhere ring" v-html="reply.body" />

                            <div class="flex flex-wrap items-center gap-2">
                                <CommentReactions
                                    :reactions="reply.reactions ?? []"
                                    :current-user-reaction="reply.current_user_reaction"
                                    @react="(reaction: string) => react(reply, reaction)"
                                />

                                <UDropdownMenu v-if="reply.user.id === currentUser.id" :items="commentActions(reply)">
                                    <UButton
                                        icon="i-lucide-ellipsis"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        square
                                        aria-label="Reply actions"
                                    />
                                </UDropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-default flex items-start gap-3 border-t pt-4">
                <UAvatar
                    :src="currentUser.avatar_url ?? undefined"
                    :alt="currentUser.name"
                    :text="getInitials(currentUser.name)"
                    size="md"
                    class="shrink-0"
                />

                <MentionEditor
                    v-model="draft"
                    :members="members"
                    placeholder="Add a comment… @ to mention, ⌘↵ to send"
                    min-height="80px"
                    class="min-w-0 flex-1"
                    @keydown.enter.meta.prevent="submitComment"
                    @keydown.enter.ctrl.prevent="submitComment"
                />

                <UButton
                    icon="i-lucide-send"
                    :loading="sending"
                    :disabled="!draft.trim() || sending"
                    class="rounded-full"
                    aria-label="Send comment"
                    @click="submitComment"
                />
            </div>
            </template>
        </div>
        </template>

        <template #history>
        <div class="flex flex-col gap-4 pt-5">
            <div v-if="activitiesLoading" class="flex justify-center py-8">
                <UIcon name="i-lucide-loader-circle" class="text-muted size-5 animate-spin" />
            </div>

            <UAlert
                v-else-if="activitiesError"
                color="error"
                variant="soft"
                :title="activitiesError"
                :actions="[{ label: 'Retry', color: 'neutral', variant: 'subtle', onClick: loadActivities }]"
            />

            <template v-else>
            <p v-if="!historyEntries.length" class="text-muted py-2 text-sm">No history yet.</p>

            <UTimeline v-else :items="historyEntries" size="xs" :ui="{ wrapper: 'pb-4' }">
                <template #title="{ item }">
                    <span class="flex flex-wrap items-baseline gap-x-1.5">
                        <span class="text-highlighted text-sm font-medium">{{ item.actor }}</span>
                        <span class="text-muted text-sm">{{ item.action }}</span>
                        <span class="text-dimmed text-xs">{{ item.at }}</span>
                    </span>
                </template>

                <template #description="{ item }">
                    <p v-if="item.quote" class="text-muted mt-1 text-sm italic">“{{ item.quote }}”</p>

                    <div v-if="item.changes.length" class="mt-1.5 flex flex-col gap-1.5">
                        <div v-for="(change, index) in item.changes" :key="index" class="flex flex-wrap items-center gap-1.5">
                            <span v-if="item.changes.length > 1" class="text-muted text-xs">{{ change.field }}</span>
                            <UBadge color="neutral" variant="subtle" size="xs" class="line-through">{{ change.old }}</UBadge>
                            <UIcon name="i-lucide-arrow-right" class="text-dimmed size-3.5 shrink-0" />
                            <UBadge color="neutral" variant="subtle" size="xs">{{ change.new }}</UBadge>
                        </div>
                    </div>
                </template>
            </UTimeline>
            </template>
        </div>
        </template>
    </UTabs>
    </div>
</template>
