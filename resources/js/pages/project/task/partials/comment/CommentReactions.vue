<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { CommentReaction } from '@/pages/project';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import { useTemplateRef } from 'vue';

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
    <div class="flex items-center gap-1 pt-0.5">
        <button
            class="rounded-full border bg-gray-100 p-1 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
            @click="op?.toggle($event)"
        >
            <Icon name="Smile" class="size-5" />
        </button>

        <button
            v-for="reaction in reactions"
            :key="reaction.reaction"
            class="flex items-center gap-1 rounded-full border px-2 py-1"
            :class="[reaction.reaction === currentUserReaction ? 'border-primary-300 bg-primary-50/50' : 'bg-white hover:bg-surface-100']"
            @click="emit('react', reaction.reaction)"
        >
            <span class="text-sm" :class="{ 'text-primary-500': reaction.reaction === currentUserReaction }">{{ reaction.count }}</span>
            <Emoji v-if="reaction.reaction.startsWith(':')" :data="emojiIndex" :emoji="reaction.reaction" set="google" :size="12" class="!p-0" />
            <span v-else class="text-sm">{{ availableReactions[reaction.reaction] }}</span>
        </button>

        <template v-if="showReply">
            <Divider layout="vertical" class="!mx-2" />
            <Button
                label="Reply"
                icon="pi pi-reply"
                icon-pos="left"
                size="small"
                severity="secondary"
                text
                rounded
                class="p-1"
                @click="emit('reply')"
            />
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
        <div class="flex gap-2">
            <button
                v-for="emoji in emojies"
                :key="emoji"
                class="flex size-8 items-center justify-center rounded hover:bg-blue-50 dark:hover:bg-blue-900/30"
                @click="selectEmoji(emoji)"
            >
                <Emoji :data="emojiIndex" :emoji="emoji" :size="14" class="!p-0" />
            </button>
        </div>
    </Popover>
</template>
