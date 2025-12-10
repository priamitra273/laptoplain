<script setup lang="ts">
import { computed, ref } from 'vue';
import { Emoji, Picker, EmojiIndex } from "emoji-mart-vue-fast/src";
import emojiData from "emoji-mart-vue-fast/data/all.json";

import "emoji-mart-vue-fast/css/emoji-mart.css";

interface Props {
    modelValue: string | null;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
}

const props = defineProps<Props>();
const emits = defineEmits<Emits>();

const op = ref();
let emojiIndex = new EmojiIndex(emojiData);

const value = computed({
    get() {
        return props.modelValue;
    },
    set(newValue: string) {
        emits('update:modelValue', newValue);
    },
});

const toggle = (event: MouseEvent) => {
    op.value.toggle(event);
}

const selectEmoji = (emoji: any) => {
    value.value = emoji.colons;
    op.value.hide();

    console.log('Selected emoji:', emoji);
}
</script>

<template>
    <Button severity="secondary" variant="text" class="!py-0" @click="toggle">
        <div>
            <Emoji v-if="value" :data="emojiIndex" :emoji="value" set="google" :size="16"></Emoji>
            <span v-else class="text-gray-400">Select Emoji</span>
        </div>
    </Button>

    <Popover ref="op">
        <Picker :data="emojiIndex" set="google" :show-preview="false" class="!border-0 dark:!bg-surface-800" @select="selectEmoji" />
    </Popover>
</template>