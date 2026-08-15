<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import { useTemplateRef } from 'vue';
import { CommentReaction } from './type';

interface Props {
    reactions: CommentReaction[];
    currentUserReaction?: string | null;
    showReply: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'react', reaction: string): void;
    (e: 'reply'): void;
}>();

const availableReactions: Record<string, string> = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

const emojies: string[] = [':thumbsup:', ':thumbsdown:', ':smiley:', ':tada:', ':confused:', ':heart:', ':rocket:', ':eyes:'];

const op = useTemplateRef('op');

const emojiIndex = new EmojiIndex(emojiData);

const selectEmoji = (emoji: string) => {
    emit('react', emoji);

    op.value?.hide();
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-1.5">
        <button
            type="button"
            aria-label="Add reaction"
            class="inline-flex size-7 items-center justify-center rounded-full border border-surface-200 bg-surface-0 text-surface-400 transition-colors hover:border-surface-300 hover:bg-surface-100 hover:text-surface-600 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-400 dark:hover:bg-surface-700 dark:hover:text-surface-200"
            @click="op?.toggle($event)"
        >
            <Icon name="Smile" class="size-4" />
        </button>

        <button
            v-for="reaction in reactions"
            :key="reaction.reaction"
            type="button"
            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 transition-colors"
            :class="
                reaction.reaction === currentUserReaction
                    ? 'border-primary-300 bg-primary-50 text-primary-700 dark:border-primary-500/40 dark:bg-primary-500/15 dark:text-primary-300'
                    : 'border-surface-200 bg-surface-0 text-surface-600 hover:bg-surface-100 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-300 dark:hover:bg-surface-700'
            "
            @click="emit('react', reaction.reaction)"
        >
            <Emoji
                v-if="reaction.reaction.startsWith(':')"
                :data="emojiIndex"
                :emoji="reaction.reaction"
                set="google"
                :size="14"
                class="!p-0"
            />
            <span v-else class="text-sm leading-none">{{ availableReactions[reaction.reaction] }}</span>
            <span class="text-xs font-semibold tabular-nums">{{ reaction.count }}</span>
        </button>

        <template v-if="showReply">
            <span class="mx-0.5 h-4 w-px bg-surface-200 dark:bg-surface-700" aria-hidden="true" />
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-700 dark:text-surface-400 dark:hover:bg-surface-700 dark:hover:text-surface-200"
                @click="emit('reply')"
            >
                <i class="pi pi-reply text-[11px]" />
                Reply
            </button>
        </template>
    </div>

    <Popover
        ref="op"
        class="before:!content-none after:!content-none"
        pt:content:class="!p-1.5"
        :style="{
            marginBlockStart: '0.5rem',
        }"
    >
        <div class="flex gap-1">
            <button
                v-for="emoji in emojies"
                :key="emoji"
                type="button"
                class="flex size-8 items-center justify-center rounded-lg transition-colors hover:bg-surface-100 dark:hover:bg-surface-700"
                @click="selectEmoji(emoji)"
            >
                <Emoji :data="emojiIndex" :emoji="emoji" :size="16" class="!p-0" />
            </button>
        </div>
    </Popover>
</template>
