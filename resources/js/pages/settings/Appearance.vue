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
];

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

                    <UTabs
                        :items="modeOptions"
                        :model-value="appearance"
                        class="w-fit"
                        @update:model-value="(value) => updateAppearance(value as 'light' | 'dark' | 'system')"
                    />
                </div>

                <div class="flex flex-col gap-4 border-t border-default pt-6">
                    <Heading size="sm" title="Accent colors" description="Pick the primary and base color used across the interface" />

                    <UFormField v-for="group in colorGroups" :key="group.label" :label="group.label">
                        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                            <UButton
                                v-for="option in group.options"
                                :key="option"
                                type="button"
                                :aria-pressed="group.model.value === option"
                                color="neutral"
                                :variant="group.model.value === option ? 'outline' : 'ghost'"
                                size="xs"
                                class="capitalize"
                                @click="group.model.value = option"
                            >
                                <template #leading>
                                    <span
                                        class="flex size-3.5 shrink-0 items-center justify-center rounded-full"
                                        :style="{ backgroundColor: swatch(option) }"
                                    >
                                        <UIcon v-if="group.model.value === option" name="i-lucide-check" class="size-2.5 text-white" />
                                    </span>
                                </template>
                                {{ option }}
                            </UButton>
                        </div>
                    </UFormField>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
