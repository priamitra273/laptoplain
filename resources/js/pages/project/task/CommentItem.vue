<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import Swal from 'sweetalert2';
import { onBeforeUnmount, onMounted, ref } from 'vue';
const props = defineProps<{
    comment: any;
    taskId: number;
    level?: number;
}>();
const currentLevel = props.level ?? 0;
const replyTarget = ref<number | null>(null);
const replyText = ref('');
const editingCommentId = ref<number | null>(null);
const showMenu = ref<number | null>(null);
const showAllReplies = ref<{ [key: number]: boolean }>({});
const REPLY_LIMIT = 0;
// Fungsi untuk menutup menu saat klik di luar
const closeMenuOnClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.menu-button') && !target.closest('.menu-dropdown')) {
        showMenu.value = null;
    }
};
onMounted(() => {
    document.addEventListener('click', closeMenuOnClickOutside);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenuOnClickOutside);
});
const toggleMenu = (id: number) => {
    showMenu.value = showMenu.value === id ? null : id;
};
const setReply = (id: number) => {
    replyTarget.value = id;
    replyText.value = '';
    showMenu.value = null; // Tutup menu setelah memilih opsi
};
const submitReply = (parentId: number) => {
    if (!replyText.value.trim()) return;
    router.post(
        route('comments.store'),
        {
            body: replyText.value,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.taskId,
            parent_id: parentId,
        },
        {
            onSuccess: () => {
                replyText.value = '';
                replyTarget.value = null;
                router.reload({ only: ['comments'] });
            },
        },
    );
};
const startEdit = (comment: any) => {
    editingCommentId.value = comment.id;
    replyText.value = comment.body;
    showMenu.value = null; // Tutup menu setelah memilih opsi
};
const updateComment = () => {
    if (!replyText.value.trim() || editingCommentId.value === null) return;
    router.put(
        route('comments.update', { id: editingCommentId.value }),
        { body: replyText.value },
        {
            onSuccess: () => {
                editingCommentId.value = null;
                replyText.value = '';
                router.reload({ only: ['comments'] });
            },
        },
    );
};
const cancelEdit = () => (editingCommentId.value = null);
const deleteComment = (id: number) => {
    Swal.fire({
        title: 'Delete Comment?',
        text: 'Are you sure you want to delete this comment?',
        icon: 'warning',
        showCancelButton: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('comments.destroy', { id }), {
                onSuccess: () => router.reload({ only: ['comments'] }),
            });
        }
    });
    showMenu.value = null; // Tutup menu setelah memilih opsi
};
const displayedReplies = (comment: any) => {
    const showAll = showAllReplies.value[comment.id] ?? false;
    if (showAll) return comment.replies;
    return comment.replies?.slice(0, REPLY_LIMIT) || [];
};
const remainingReplies = (comment: any) => {
    return (comment.replies?.length || 0) - REPLY_LIMIT;
};
const toggleShowAllReplies = (commentId: number) => {
    showAllReplies.value[commentId] = !showAllReplies.value[commentId];
};
</script>
<template>
    <div class="w-full space-y-2">
        <div class="w-full max-w-full break-words rounded-lg bg-white p-3 shadow transition-shadow hover:shadow-md">
            <div class="flex w-full gap-2">
                <Avatar :label="comment.user?.name[0]" size="small" class="flex-shrink-0 bg-blue-500 text-xs text-white" />
                <div class="w-full flex-1 overflow-hidden">
                    <!-- Edit Mode -->
                    <div v-if="editingCommentId === comment.id" class="w-full">
                        <Textarea v-model="replyText" rows="2" class="w-full break-words rounded border p-2 text-sm" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Button
                                label="Save"
                                icon="pi pi-check"
                                size="small"
                                class="bg-green-500 text-xs hover:bg-green-600"
                                @click="updateComment"
                            />
                            <Button label="Cancel" icon="pi pi-times" size="small" severity="secondary" class="text-xs" @click="cancelEdit" />
                        </div>
                    </div>
                    <!-- View Mode -->
                    <div v-else class="w-full">
                        <div class="mb-1 flex w-full items-center justify-between">
                            <h4 :class="['truncate font-semibold text-gray-800', currentLevel === 0 ? 'text-sm' : 'text-xs']">
                                {{ comment.user?.name }}
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">{{ moment(comment.created_at).fromNow() }}</span>
                                <!-- button titik tiga -->
                                <button @click="toggleMenu(comment.id)" class="menu-button text-gray-500 hover:text-gray-700">
                                    <i class="pi pi-ellipsis-h text-sm"></i>
                                </button>
                                <!-- dropdown -->
                                <div
                                    v-if="showMenu === comment.id"
                                    class="menu-dropdown absolute z-10 mt-6 w-28 rounded-md border bg-white shadow-md"
                                >
                                    <button
                                        v-if="currentLevel < 1"
                                        @click="setReply(comment.id)"
                                        class="block w-full px-3 py-1 text-left text-xs hover:bg-gray-100"
                                    >
                                        Reply
                                    </button>
                                    <button @click="startEdit(comment)" class="block w-full px-3 py-1 text-left text-xs hover:bg-gray-100">
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteComment(comment.id)"
                                        class="block w-full px-3 py-1 text-left text-xs text-red-600 hover:bg-gray-100"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p :class="['break-words text-gray-700', currentLevel === 0 ? 'text-sm' : 'text-xs']">
                            {{ comment.body }}
                        </p>
                        <!-- Reply Input -->
                        <div v-if="replyTarget === comment.id" class="mt-2 flex w-full flex-col gap-2" :class="currentLevel === 0 ? 'pl-3' : 'pl-2'">
                            <Textarea
                                v-model="replyText"
                                rows="2"
                                placeholder="Write a reply..."
                                class="w-full break-words rounded border p-2 text-sm"
                            />
                            <Button
                                label="Submit"
                                icon="pi pi-send"
                                size="small"
                                class="w-full bg-blue-500 text-xs hover:bg-blue-600 md:w-28"
                                @click="submitReply(comment.id)"
                            />
                        </div>
                        <!-- Nested Replies (level < 2) -->
                        <div
                            v-if="comment.replies?.length && currentLevel < 1"
                            class="mt-2 w-full space-y-2 overflow-hidden"
                            :class="currentLevel === 0 ? 'pl-3' : 'pl-2'"
                        >
                            <CommentItem
                                v-for="reply in displayedReplies(comment)"
                                :key="reply.id"
                                :comment="reply"
                                :taskId="props.taskId"
                                :level="currentLevel + 1"
                            />
                            <button
                                v-if="!showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs text-gray-500 hover:underline"
                            >
                                View {{ remainingReplies(comment) }} more replies
                            </button>
                            <button
                                v-else-if="showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs text-gray-500 hover:underline"
                            >
                                Show less
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
