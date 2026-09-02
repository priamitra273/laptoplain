<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        value: number | string;
        icon: string;
        hint?: string;
        tone?: 'neutral' | 'success' | 'danger';
    }>(),
    { tone: 'neutral' },
);

// Satu prop `tone` menyetel ikon, angka, dan keterangan sekaligus, supaya tiap kartu
// tidak perlu mengatur tiga warna terpisah.
const iconClass = computed(
    () => ({ neutral: 'text-muted', success: 'text-success', danger: 'text-error' })[props.tone],
);

const valueClass = computed(() => (props.tone === 'danger' ? 'text-error' : 'text-highlighted'));

const hintClass = computed(() => (props.tone === 'success' ? 'text-success' : 'text-muted'));
</script>

<template>
    <UCard :ui="{ root: 'gap-0 py-0', body: 'flex flex-col gap-2 p-4 sm:p-4' }">
        <div class="flex items-center gap-2">
            <UIcon :name="icon" class="size-4 shrink-0" :class="iconClass" />
            <span class="truncate text-sm text-toned">{{ label }}</span>
        </div>

        <span class="text-3xl leading-none font-semibold tabular-nums" :class="valueClass">{{ value }}</span>

        <span v-if="hint" class="text-xs" :class="hintClass">{{ hint }}</span>
    </UCard>
</template>
