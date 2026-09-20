<script setup lang="ts">
import { parseDateOnly } from '@/lib/date';
import { plural } from '@/lib/utils';
import { Deferred } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivityHeatmap from './ActivityHeatmap.vue';
import type { DashboardActivity } from './types';

const props = defineProps<{
    activity?: DashboardActivity;
}>();

const busiestDay = computed(() => {
    const activity = props.activity;
    const date = parseDateOnly(activity?.busiest_date);

    if (!activity || !date) {
        return '—';
    }

    const formatted = date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });

    return `${formatted} · ${plural(activity.busiest_count, 'event')}`;
});
</script>

<template>
    <PanelCard title="Activity graph">
        <template #meta>
            <p v-if="activity" class="text-sm text-muted">{{ plural(activity.total_events, 'task event') }} · last {{ activity.weeks }} weeks</p>
        </template>

        <Deferred data="activity">
            <template #fallback>
                <div class="flex flex-col gap-4">
                    <USkeleton class="h-28 w-full" />

                    <USeparator />

                    <div class="flex flex-wrap gap-x-10 gap-y-3">
                        <USkeleton v-for="n in 3" :key="n" class="h-10 w-32" />
                    </div>
                </div>
            </template>

            <div v-if="activity" class="flex flex-col gap-4">
                <ActivityHeatmap :values="activity.values" />

                <USeparator />

                <dl class="flex flex-wrap gap-x-10 gap-y-3">
                    <div class="flex flex-col gap-1">
                        <dt class="text-xs text-muted">Busiest day</dt>
                        <dd class="text-sm font-semibold text-highlighted">{{ busiestDay }}</dd>
                    </div>

                    <div class="flex flex-col gap-1">
                        <dt class="text-xs text-muted">Current streak</dt>
                        <dd class="text-sm font-semibold text-highlighted">{{ plural(activity.current_streak, 'day') }}</dd>
                    </div>

                    <div class="flex flex-col gap-1">
                        <dt class="text-xs text-muted">Weekly average</dt>
                        <dd class="text-sm font-semibold text-highlighted">{{ plural(activity.weekly_average, 'event') }}</dd>
                    </div>
                </dl>
            </div>
        </Deferred>
    </PanelCard>
</template>
