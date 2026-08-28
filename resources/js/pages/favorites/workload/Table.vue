<script setup lang="ts">
import { getInitials } from '@/lib/utils';
import { computed } from 'vue';
import type { WorkloadSortColumn, WorkloadStatusOption, WorkloadUser } from './types';
import { toneOf } from './workload';

const props = defineProps<{
    data: WorkloadUser[];
    statusOptions: WorkloadStatusOption[];
    sort: WorkloadSortColumn;
    direction: 'asc' | 'desc';
    hasFilters: boolean;
}>();

defineEmits<{
    sort: [column: WorkloadSortColumn];
    clear: [];
}>();

const columns: { key: WorkloadSortColumn; label: string }[] = [
    { key: 'name', label: 'Name' },
    { key: 'total_tasks', label: 'Tasks' },
    { key: 'remaining_work_percent', label: 'Remaining work' },
];

const severityByStatus = computed(() => new Map(props.statusOptions.map((option) => [option.name, option.severity])));

const rows = computed(() =>
    props.data.map((user) => ({
        user,
        tone: toneOf(severityByStatus.value.get(user.workload_status)),
        initials: getInitials(user.name),
        avatar: user.avatar_url && !user.avatar_url.includes('default-avatar') ? user.avatar_url : undefined,
    })),
);

const sortIcon = (column: WorkloadSortColumn): string => {
    if (props.sort !== column) {
        return 'i-lucide-chevrons-up-down';
    }

    return props.direction === 'asc' ? 'i-lucide-arrow-up' : 'i-lucide-arrow-down';
};
</script>

<template>
    <div>
        <div
            v-if="rows.length"
            class="hidden h-9 items-center border-b border-default px-4 lg:grid lg:grid-cols-[0.375rem_minmax(0,1fr)_4.5rem_15rem_7.5rem] lg:gap-x-3.5"
        >
            <span />
            <button
                v-for="column in columns"
                :key="column.key"
                type="button"
                class="flex items-center gap-1.5 text-start text-xs tracking-wide uppercase"
                :class="sort === column.key ? 'text-primary' : 'text-muted hover:text-highlighted'"
                @click="$emit('sort', column.key)"
            >
                <span class="truncate">{{ column.label }}</span>
                <UIcon :name="sortIcon(column.key)" class="size-3 shrink-0" />
            </button>
            <span class="text-xs tracking-wide text-muted uppercase">Status</span>
        </div>

        <ul v-if="rows.length" class="divide-y divide-default">
            <li
                v-for="{ user, tone, initials, avatar } in rows"
                :key="user.id"
                class="grid grid-cols-[0.375rem_minmax(0,1fr)] items-center gap-x-2.5 px-4 py-2.5 lg:h-12 lg:grid-cols-[0.375rem_minmax(0,1fr)_4.5rem_15rem_7.5rem] lg:gap-x-3.5 lg:py-0"
            >
                <span
                    class="h-6 w-[3px] shrink-0 rounded-full"
                    :class="user.workload_status === 'Overloaded' ? 'bg-error' : 'bg-transparent'"
                    aria-hidden="true"
                />

                <div class="flex min-w-0 items-center gap-2.5">
                    <UAvatar :src="avatar" :text="initials" :alt="user.name" size="2xs" class="shrink-0" />
                    <ULink :to="route('reports.tasks.index', { names: [user.id] })" class="truncate text-sm">
                        {{ user.name }}
                    </ULink>
                </div>

                <div class="col-start-2 mt-2 flex flex-wrap items-center gap-x-2.5 gap-y-2 lg:col-start-auto lg:mt-0 lg:contents">
                    <UBadge color="neutral" variant="outline" size="sm" class="tabular-nums" :label="String(user.total_tasks)" />

                    <div class="flex min-w-0 flex-1 items-center gap-2.5 lg:flex-none">
                        <div class="h-1.5 min-w-8 flex-1 overflow-hidden rounded-full bg-accented">
                            <div class="h-1.5 rounded-full" :class="tone.fill" :style="{ width: `${user.remaining_work_percent}%` }" />
                        </div>
                        <span class="w-10 shrink-0 text-end text-xs font-medium tabular-nums" :class="tone.text">
                            {{ user.remaining_work_percent }}%
                        </span>
                        <span class="hidden w-16 shrink-0 text-xs text-dimmed tabular-nums xl:block">
                            {{ 100 - user.remaining_work_percent }}% done
                        </span>
                    </div>

                    <span class="shrink-0 rounded-md px-2 py-1 text-xs" :class="[tone.soft, tone.text]">{{ user.workload_status }}</span>
                </div>
            </li>
        </ul>

        <div v-else-if="hasFilters" class="flex flex-col items-center gap-2.5 px-5 py-14 text-center">
            <span class="flex size-9 items-center justify-center rounded-lg bg-elevated">
                <UIcon name="i-lucide-filter" class="size-4 text-muted" />
            </span>
            <p class="text-sm font-semibold text-highlighted">No matching users</p>
            <p class="max-w-md text-xs leading-relaxed text-muted">
                No active user matches the filters you have applied. Try removing a status, or search a different name.
            </p>
            <UButton label="Clear all filters" color="neutral" variant="outline" size="sm" class="mt-1" @click="$emit('clear')" />
        </div>

        <div v-else class="flex flex-col items-center gap-2.5 px-5 py-14 text-center">
            <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                <UIcon name="i-lucide-users" class="size-4 text-primary" />
            </span>
            <p class="text-sm font-semibold text-highlighted">No active users yet</p>
            <p class="max-w-md text-xs leading-relaxed text-muted">Workload shows up here once there are active users with tasks assigned to them.</p>
            <UButton :to="route('user.index')" label="Manage users" color="neutral" variant="outline" size="sm" class="mt-1" />
        </div>
    </div>
</template>
