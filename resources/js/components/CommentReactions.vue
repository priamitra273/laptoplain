<script setup lang="ts">
import { ref } from 'vue';

export interface CommentReaction {
    reaction: string;
    count: number;
}

/**
 * Reaksi disimpan sebagai shortcode (`:rocket:`). Halaman lama merendernya lewat
 * emoji-mart; di sini dipetakan langsung ke karakter emoji supaya tidak perlu
 * memuat paket emoji hanya untuk delapan pilihan.
 */
const EMOJI: Record<string, string> = {
    ':thumbsup:': '👍',
    ':thumbsdown:': '👎',
    ':smiley:': '😃',
    ':tada:': '🎉',
    ':confused:': '😕',
    ':heart:': '❤️',
    ':rocket:': '🚀',
    ':eyes:': '👀',
    // Nilai lama dari sebelum shortcode dipakai.
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

const CHOICES = [':thumbsup:', ':thumbsdown:', ':smiley:', ':tada:', ':confused:', ':heart:', ':rocket:', ':eyes:'];

withDefaults(
    defineProps<{
        reactions?: CommentReaction[];
        currentUserReaction?: string | null;
    }>(),
    {
        reactions: () => [],
        currentUserReaction: null,
    },
);

const emit = defineEmits<{
    react: [reaction: string];
}>();

const open = ref(false);

const toEmoji = (reaction: string) => EMOJI[reaction] ?? reaction;

const pick = (reaction: string) => {
    open.value = false;
    emit('react', reaction);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-1.5">
        <UPopover v-model:open="open" :content="{ align: 'start' }">
            <UButton icon="i-lucide-smile" color="neutral" variant="outline" size="xs" class="rounded-full" aria-label="Add reaction" />

            <template #content>
                <div class="flex gap-1 p-1.5">
                    <button
                        v-for="choice in CHOICES"
                        :key="choice"
                        type="button"
                        class="flex size-8 items-center justify-center rounded-lg text-base transition-colors hover:bg-elevated"
                        :class="choice === currentUserReaction ? 'bg-elevated' : ''"
                        :aria-label="choice"
                        @click="pick(choice)"
                    >
                        {{ toEmoji(choice) }}
                    </button>
                </div>
            </template>
        </UPopover>

        <button
            v-for="reaction in reactions"
            :key="reaction.reaction"
            type="button"
            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 transition-colors"
            :class="
                reaction.reaction === currentUserReaction
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-default text-muted hover:bg-elevated'
            "
            @click="emit('react', reaction.reaction)"
        >
            <span class="text-sm leading-none">{{ toEmoji(reaction.reaction) }}</span>
            <span class="text-xs font-semibold tabular-nums">{{ reaction.count }}</span>
        </button>
    </div>
</template>
