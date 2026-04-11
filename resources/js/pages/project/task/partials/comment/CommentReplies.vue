<script setup lang="ts">
import { computed, ref } from 'vue';
import CommentItem from '../../CommentItem.vue';
import { ReplyProps } from './type';

const props = defineProps<ReplyProps>();

const REPLY_LIMIT = 0;

const showAllReplies = ref(false);

const remainingReplies = computed(() => (props.comment.replies?.length || 0) - REPLY_LIMIT);

const displayedReplies = computed(() => {
    if (showAllReplies.value) return props.comment.replies || [];
    return props.comment.replies?.slice(0, REPLY_LIMIT) || [];
});

const toggleShowAllReplies = () => {
    showAllReplies.value = !showAllReplies.value;
};
</script>

<template>
    <div v-if="comment.replies?.length && currentLevel < 1" class="mt-2 space-y-1.5 border-l-2 border-gray-200 pl-2 dark:border-gray-700">
        <CommentItem
            v-for="reply in displayedReplies"
            :key="reply.id"
            :comment="reply"
            :taskId="props.taskId"
            :level="currentLevel + 1"
            :currentUserId="props.currentUserId"
            :projectMembers="props.projectMembers"
        />
        <button
            v-if="!showAllReplies && remainingReplies > 0"
            @click="toggleShowAllReplies"
            class="text-xs font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
            View {{ remainingReplies }} more {{ remainingReplies === 1 ? 'reply' : 'replies' }}
        </button>
        <button
            v-else-if="showAllReplies"
            @click="toggleShowAllReplies"
            class="text-xs font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
            Show less
        </button>
    </div>
</template>
