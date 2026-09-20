<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import { computed } from 'vue';
import { barDates } from './ganttDate';
import type { VisibleTask } from './useGantt';

const props = defineProps<{
    task: VisibleTask;
}>();

const barColor = computed(() => `var(--ui-color-${severityColor(props.task.severity)}-500)`);

// Warna pudar buat porsi garis yang belum selesai — dasarnya tetap warna status,
// cuma dicampur transparan biar kebaca sebagai "belum progress" tanpa kesan mati.
const barTrackColor = computed(() => `color-mix(in srgb, ${barColor.value} 25%, transparent)`);

const barEndLabel = computed(() => {
    const { end } = barDates(props.task);

    return `Ends ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
});
</script>

<template>
    <div class="absolute top-1/2 z-5 h-6 -translate-y-1/2" :title="`${Math.round(task.progress)}% complete`">
        <!-- cap bulat di awal -->
        <div class="absolute left-0 top-1/2 h-6 w-1.5 -translate-y-1/2 rounded-full" :style="{ backgroundColor: barColor }"></div>
        <!-- garis tipis penghubung: bagian pudar = track, bagian solid = progress -->
        <div class="absolute top-1/2 right-0.5 left-0.5 h-1 -translate-y-1/2 overflow-hidden rounded-full" :style="{ backgroundColor: barTrackColor }">
            <div class="h-full rounded-full" :style="{ width: `${task.progress}%`, backgroundColor: barColor }"></div>
        </div>
        <!-- cap bulat di akhir -->
        <div class="absolute right-0 top-1/2 h-6 w-1.5 -translate-y-1/2 rounded-full" :style="{ backgroundColor: barColor }"></div>

        <div class="absolute inset-y-0 left-3 right-3">
            <span
                class="sticky right-0 float-right rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap text-inverted"
                :style="{ backgroundColor: barColor }"
            >
                {{ barEndLabel }}
            </span>
        </div>
    </div>
</template>
