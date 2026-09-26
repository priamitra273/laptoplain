<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { NEUTRAL_COLORS, PRIMARY_COLORS, useTheme } from '@/composables/useTheme';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import Heading from '@/components/ui/Heading.vue';
import { Head } from '@inertiajs/vue3';
import colors from 'tailwindcss/colors';

const { appearance, updateAppearance } = useAppearance();

const modeOptions = [
    { value: 'light', icon: 'i-lucide-sun', label: 'Light' },
    { value: 'dark', icon: 'i-lucide-moon', label: 'Dark' },
    { value: 'system', icon: 'i-lucide-monitor', label: 'System' },
] as const;

const { primary, neutral } = useTheme();

const colorGroups = [
    { label: 'Primary color', options: PRIMARY_COLORS, model: primary },
    { label: 'Base color', options: NEUTRAL_COLORS, model: neutral },
];

// Sumber yang sama dipakai runtime/plugins/colors.js — di Tailwind v4 nilainya oklch,
// jadi swatch persis sama dengan warna yang nanti dipasang ke --ui-color-*.
const swatch = (name: string) => (colors as Record<string, Record<number, string>>)[name]?.[500];
</script>

<template>
    <AppLayout title="Appearance settings">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-4">
                    <Heading size="sm" title="Appearance" description="Choose how the interface looks on this device" />

                    <div class="inline-flex w-fit gap-1 rounded-lg bg-elevated p-1">
                        <button
                            v-for="option in modeOptions"
                            :key="option.value"
                            type="button"
                            class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-sm transition-colors"
                            :class="appearance === option.value ? 'bg-default text-highlighted shadow-sm' : 'text-muted hover:text-highlighted'"
                            @click="updateAppearance(option.value)"
                        >
                            <UIcon :name="option.icon" class="size-4" />
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-4 border-t border-default pt-6">
                    <Heading size="sm" title="Accent colors" description="Pick the primary and base color used across the interface" />

                    <div v-for="group in colorGroups" :key="group.label" class="flex flex-col gap-2">
                        <p class="text-sm font-medium">{{ group.label }}</p>

                        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
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
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
