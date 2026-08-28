<script setup lang="ts">
import { computed } from 'vue';
import type { DistributionSegment } from './types';
import { toneOf } from './workload';

const props = defineProps<{
    segments: DistributionSegment[];
    total: number;
    activeStatusCount: number;
}>();

defineEmits<{
    toggle: [statusId: number];
}>();

const bars = computed(() => props.segments.filter((segment) => segment.count > 0));
</script>

<template>
    <div class="flex flex-col gap-3 rounded-lg bg-elevated/30 p-4 ring ring-default">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <h2 class="text-sm font-semibold text-highlighted">Team workload spread</h2>

            <UBadge
                v-if="activeStatusCount"
                color="primary"
                variant="subtle"
                size="sm"
                icon="i-lucide-filter"
                :label="`Filtered · ${activeStatusCount} ${activeStatusCount === 1 ? 'status' : 'statuses'}`"
            />
            <UBadge v-else color="neutral" variant="outline" size="sm" label="All users" />
        </div>

        <div v-if="total" class="flex h-2.5 gap-0.5">
            <div
                v-for="segment in bars"
                :key="segment.id"
                class="rounded-xs transition-opacity"
                :class="[toneOf(segment.severity).fill, segment.active ? '' : 'opacity-40']"
                :style="{ width: `${(segment.count / total) * 100}%` }"
                :title="`${segment.label}: ${segment.count}`"
            />
        </div>
        <div v-else class="h-2.5 rounded-xs bg-accented" />

        <div class="flex flex-wrap gap-2">
            <button
                v-for="segment in segments"
                :key="segment.id"
                type="button"
                class="flex items-center gap-2 rounded-md px-2.5 py-1.5 ring transition-colors"
                :class="
                    segment.active && activeStatusCount
                        ? [toneOf(segment.severity).soft, toneOf(segment.severity).ring]
                        : 'ring-default hover:bg-elevated/50'
                "
                @click="$emit('toggle', segment.id)"
            >
                <span class="size-1.5 shrink-0 rounded-full" :class="toneOf(segment.severity).fill" />
                <span class="text-xs" :class="segment.active ? toneOf(segment.severity).text : 'text-muted'">{{ segment.label }}</span>
                <span class="text-xs font-medium tabular-nums" :class="segment.active ? toneOf(segment.severity).text : 'text-muted'">
                    {{ segment.count }}
                </span>
                <span class="text-xs text-muted tabular-nums">{{ segment.percent }}%</span>
            </button>
        </div>
    </div>
</template>
