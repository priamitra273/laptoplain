<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    value: number;
    barAriaLabel: string;
    color?: 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'error' | 'neutral';
    /**
     * Label menempel di sisi bar, tanpa lebar tetap. Lebar tetap hanya berguna saat
     * beberapa bar ditumpuk agar angkanya sejajar; di baris sempit justru menyisakan
     * jarak kosong antara bar dan angkanya.
     */
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), { color: 'primary' });

const displayValue = computed(() => Math.round(Math.min(100, Math.max(0, props.value)) * 100) / 100);

const percentageLabel = computed(() => `${displayValue.value}%`);
</script>

<template>
    <div class="flex items-center gap-2.5">
        <UProgress
            :model-value="displayValue"
            :max="100"
            size="sm"
:color="color"
:get-value-label="() => barAriaLabel"
            class="min-w-0 flex-1"
        />
        <span class="text-default shrink-0 text-xs font-medium tabular-nums" :class="compact ? '' : 'w-16 text-end'">
            {{ percentageLabel }}
        </span>
    </div>
</template>
