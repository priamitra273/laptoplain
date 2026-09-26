<script setup lang="ts">
import colors from 'tailwindcss/colors';

import { NEUTRAL_COLORS, PRIMARY_COLORS, useTheme } from '@/composables/useTheme';

const { primary, neutral } = useTheme();

const groups = [
    { label: 'Primary color', options: PRIMARY_COLORS, model: primary },
    { label: 'Base color', options: NEUTRAL_COLORS, model: neutral },
];

// Sumber yang sama dipakai runtime/plugins/colors.js — di Tailwind v4 nilainya oklch,
// jadi swatch persis sama dengan warna yang nanti dipasang ke --ui-color-*.
const swatch = (name: string) => (colors as Record<string, Record<number, string>>)[name]?.[500];
</script>

<template>
    <UPopover :content="{ align: 'end' }">
        <UButton icon="i-lucide-palette" color="neutral" variant="ghost" aria-label="Ganti tema" />

        <template #content>
            <div class="w-72 space-y-4">
                <div v-for="group in groups" :key="group.label" class="space-y-2">
                    <p class="text-xs font-medium text-muted">{{ group.label }}</p>

                    <div class="grid grid-cols-3 gap-1.5">
                        <button
                            v-for="option in group.options"
                            :key="option"
                            type="button"
                            :aria-pressed="group.model.value === option"
                            class="flex items-center gap-1.5 rounded-md border px-2 py-1.5 text-xs capitalize"
                            :class="group.model.value === option ? 'border-primary' : 'border-default hover:bg-elevated'"
                            @click="group.model.value = option"
                        >
                            <span
                                class="flex size-3.5 shrink-0 items-center justify-center rounded-full"
                                :style="{ backgroundColor: swatch(option) }"
                            >
                                <UIcon v-if="group.model.value === option" name="i-lucide-check" class="size-2.5 text-white" />
                            </span>
                            {{ option }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </UPopover>
</template>
