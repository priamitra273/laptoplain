<script setup lang="ts">
const props = defineProps<{
    reactions: { [key: string]: string };
    currentUserId: string;
}>();

const emit = defineEmits<{
    (e: 'react', reaction: string): void;
}>();

const availableReactions = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

const countReactions = (reactionType: string) => {
    if (!props.reactions) return 0;
    return Object.values(props.reactions).filter((r) => r === reactionType).length;
};

const hasReacted = (reactionType: string) => {
    if (!props.reactions) return false;
    return props.reactions[props.currentUserId] === reactionType;
};
</script>

<template>
    <div class="flex items-center gap-1 pt-0.5">
        <button
            v-for="(icon, reaction) in availableReactions"
            :key="reaction"
            @click="emit('react', reaction)"
            class="flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs transition-all duration-150 hover:scale-105 hover:bg-gray-100 dark:hover:bg-gray-700"
            :class="{
                'bg-blue-50 ring-1 ring-blue-200 dark:bg-blue-900/30 dark:ring-blue-800': hasReacted(reaction),
                'hover:shadow-sm': countReactions(reaction) > 0,
            }"
        >
            <span class="text-sm">{{ icon }}</span>
            <span
                v-if="countReactions(reaction) > 0"
                class="text-xs font-medium text-gray-600 dark:text-gray-400"
                :class="{ 'text-blue-600 dark:text-blue-400': hasReacted(reaction) }"
            >
                {{ countReactions(reaction) }}
            </span>
        </button>
    </div>
</template>
