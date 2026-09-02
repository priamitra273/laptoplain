<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface AttentionTask {
    id: string;
    title: string;
    due_date: string;
    days_remaining: number;
    open_subtasks: number;
    owner_name: string | null;
}

defineProps<{
    total: number;
    items: AttentionTask[];
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
    <UCard :ui="{ root: 'gap-0 py-0', body: 'flex flex-col p-0 sm:p-0' }">
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold text-highlighted">Needs attention</h2>
                <UBadge :label="String(total)" color="error" variant="subtle" size="sm" />
            </div>

            <ULink :as="Link" :href="route('task.index')" class="text-sm font-medium">View all</ULink>
        </div>

        <ul class="flex flex-col">
            <li
                v-for="item in items"
                :key="item.id"
                class="flex items-center gap-3 border-t border-default px-4 py-3"
            >
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
            </li>

            <li v-if="items.length === 0" class="border-t border-default px-4 py-6 text-center text-sm text-muted">
                Tidak ada task yang mendesak. Bagus.
            </li>
        </ul>
    </UCard>
</template>
