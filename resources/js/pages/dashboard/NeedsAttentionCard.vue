<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import type { DashboardAttentionItem } from './types';

defineProps<{
    total: number;
    items: DashboardAttentionItem[];
}>();

/** Ambang kuning, murni urusan tampilan — tidak sama dengan jendela 14 hari di backend. */
const SOON_DAYS = 7;

const badgeColor = (days: number): 'error' | 'warning' | 'neutral' => {
    if (days < 0) {
        return 'error';
    }

    return days <= SOON_DAYS ? 'warning' : 'neutral';
};

const accentClass = (days: number): string => {
    if (days < 0) {
        return 'bg-error';
    }

    return days <= SOON_DAYS ? 'bg-warning' : 'bg-primary';
};

const badgeLabel = (days: number): string => {
    if (days < 0) {
        return `-${Math.abs(days)}d`;
    }

    return days === 0 ? 'today' : `in ${days}d`;
};
</script>

<template>
    <PanelCard title="Needs attention" flush>
        <template #meta>
            <UBadge :label="String(total)" color="error" variant="subtle" size="sm" />
        </template>

        <template #action>
            <ULink :as="Link" :href="route('task.index')" class="text-sm font-medium">View all</ULink>
        </template>

        <ul class="flex flex-col">
            <li v-for="item in items" :key="item.id" class="border-t border-default">
                <Link :href="route('task.show', item.id)" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-elevated">
                    <span class="h-9 w-1 shrink-0 rounded-full" :class="accentClass(item.days_remaining)" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-highlighted">{{ item.title }}</p>
                        <p class="truncate text-xs text-muted">
                            {{ item.open_subtasks }} open subtasks
                            <template v-if="item.owner_name"> · {{ item.owner_name }}</template>
                        </p>
                    </div>

                    <UBadge
                        :label="badgeLabel(item.days_remaining)"
                        :color="badgeColor(item.days_remaining)"
                        variant="subtle"
                        size="sm"
                        class="shrink-0 font-mono"
                    />
                </Link>
            </li>

            <li v-if="items.length === 0" class="border-t border-default px-4">
                <EmptyState title="No urgent tasks. Nice." size="compact" />
            </li>
        </ul>
    </PanelCard>
</template>
