<script setup lang="ts">
import { EmojiIndex, Picker } from 'emoji-mart-vue-fast/src';
import emojiData from 'emoji-mart-vue-fast/data/all.json';

import 'emoji-mart-vue-fast/css/emoji-mart.css';

defineProps<{
    modelValue: string | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const emojiIndex = new EmojiIndex(emojiData);

const selectEmoji = (emoji: { native: string }) => {
    emit('update:modelValue', emoji.native);
};
</script>

<template>
    <Picker :data="emojiIndex" set="google" :show-preview="false" title="Pick an emoji" emoji="point_up" @select="selectEmoji" />
</template>
