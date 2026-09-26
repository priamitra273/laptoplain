<script lang="ts">
import type { PrimeSeverity } from '@/types';

/** Satu pil status: `count` boleh `null` kalau angkanya belum selesai dimuat. */
export interface StatusPillOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
    count?: number | null;
}
</script>

<script setup lang="ts">
import { severityDotClass } from '@/lib/utils';

withDefaults(
    defineProps<{
        options: StatusPillOption[];
        total?: number | null;
        showAll?: boolean;
        showCount?: boolean;
        disabled?: boolean;
        ariaLabel?: string;
    }>(),
    { total: null, showAll: true, showCount: true, disabled: false, ariaLabel: 'Filter by status' },
);

const selected = defineModel<string | null>({ required: true });

/** Menekan pil yang sedang aktif melepas filternya lagi. */
const toggle = (id: string) => {
    selected.value = selected.value === id ? null : id;
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="ariaLabel">
        <UButton
            v-if="showAll"
            label="All"
            color="neutral"
            size="sm"
            :variant="selected === null ? 'solid' : 'outline'"
            :aria-pressed="selected === null"
            :disabled="disabled"
            class="shrink-0 rounded-full"
            @click="selected = null"
        >
            <template v-if="showCount" #trailing>
                <span class="text-xs opacity-70">{{ total ?? '-' }}</span>
            </template>
        </UButton>

        <UButton
            v-for="option in options"
            :key="option.id"
            color="neutral"
            size="sm"
            :variant="selected === option.id ? 'solid' : 'outline'"
            :aria-pressed="selected === option.id"
            :disabled="disabled"
            class="shrink-0 rounded-full"
            @click="toggle(option.id)"
        >
            <template #leading>
                <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(option.severity)" />
            </template>

            {{ option.name }}

            <template v-if="showCount" #trailing>
                <span class="text-xs opacity-70">{{ option.count ?? '-' }}</span>
            </template>
        </UButton>
    </div>
</template>
