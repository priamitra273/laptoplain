<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import PanelCard from '@/components/ui/PanelCard.vue';
import { getInitials } from '@/lib/utils';
import { Deferred, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DashboardLatestProject } from './types';

const props = defineProps<{
    // Opsional karena di-defer: daftarnya tiba setelah render pertama.
    items?: DashboardLatestProject[];
}>();

const list = computed(() => props.items ?? []);

// Warna dipilih dari id project, bukan acak, supaya avatar yang sama tidak berganti
// warna setiap halaman dimuat ulang.
const AVATAR_TONES = [
    'bg-indigo-500/15 text-indigo-500',
    'bg-emerald-500/15 text-emerald-500',
    'bg-amber-500/15 text-amber-500',
    'bg-rose-500/15 text-rose-500',
    'bg-sky-500/15 text-sky-500',
];

const toneOf = (id: string): string => {
    const sum = [...id].reduce((total, char) => total + char.charCodeAt(0), 0);

    return AVATAR_TONES[sum % AVATAR_TONES.length];
};

/** Bentuk ringkas seperti "2d ago", bukan "2 days ago", agar muat di kanan judul. */
const timeAgo = (iso: string): string => {
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    const units: [number, string][] = [
        [31536000, 'y'],
        [2592000, 'mo'],
        [604800, 'w'],
        [86400, 'd'],
        [3600, 'h'],
        [60, 'm'],
    ];

    for (const [size, suffix] of units) {
        if (seconds >= size) {
            return `${Math.floor(seconds / size)}${suffix} ago`;
        }
    }

    return 'just now';
};
</script>

<template>
    <PanelCard title="Latest projects" flush>
        <template #action>
            <Link :href="route('project.index')" class="text-sm font-medium text-primary hover:underline"> View all </Link>
        </template>

        <Deferred data="latestProjects">
            <template #fallback>
                <div v-for="n in 4" :key="n" class="flex gap-3 border-t border-default px-4 py-3">
                    <USkeleton class="size-9 shrink-0 rounded-lg" />

                    <div class="flex flex-1 flex-col gap-2">
                        <USkeleton class="h-4 w-2/3" />
                        <USkeleton class="h-3 w-full" />
                        <USkeleton class="h-4 w-24" />
                    </div>
                </div>
            </template>

            <ul class="flex flex-col">
                <li v-for="item in list" :key="item.id" class="border-t border-default">
                    <Link :href="route('project.show.kanban', item.id)" class="flex gap-3 px-4 py-3 transition-colors hover:bg-elevated">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg text-xs font-semibold" :class="toneOf(item.id)">
                            {{ getInitials(item.title) }}
                        </span>

                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="truncate text-sm font-semibold text-highlighted">{{ item.title }}</p>
                                <span class="shrink-0 text-xs text-dimmed">{{ timeAgo(item.created_at) }}</span>
                            </div>

                            <p v-if="item.description" class="line-clamp-2 text-xs text-muted">{{ item.description }}</p>

                            <div class="flex flex-wrap items-center gap-2">
                                <StatusBadge :label="item.status_name" :severity="item.status_severity" />
                                <span class="text-xs text-muted">{{ item.members_count }} members</span>
                            </div>
                        </div>
                    </Link>
                </li>

                <li v-if="list.length === 0" class="border-t border-default px-4">
                    <EmptyState title="No projects yet" size="compact" />
                </li>
            </ul>
        </Deferred>
    </PanelCard>
</template>
