<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, provide } from 'vue';
import type { ShellProps } from '../types';

const page = usePage();

const shell = computed(() => page.props as unknown as ShellProps);
const project = computed(() => shell.value.project);

provide(ProjectPolicyKey, shell.value.policy);

/**
 * Rentang ditulis sebagai satu baris. Tahun pada tanggal awal dibuang saat kedua
 * tanggal berada di tahun yang sama, supaya barisnya tidak mengulang angka yang sama.
 */
const timeline = computed(() => {
    const start = project.value.start_date ? moment(project.value.start_date) : null;
    const end = project.value.due_date ? moment(project.value.due_date) : null;

    if (!start && !end) {
        return 'No dates set';
    }

    if (!start) {
        return `Due ${end!.format('D MMM YYYY')}`;
    }

    if (!end) {
        return `From ${start.format('D MMM YYYY')}`;
    }

    const startFormat = start.year() === end.year() ? 'D MMM' : 'D MMM YYYY';

    return `${start.format(startFormat)} → ${end.format('D MMM YYYY')}`;
});

// Keempat kartu ringkasan memakai anatomi yang sama, jadi kelasnya ditulis sekali.
const statCardClass = 'flex flex-col items-start gap-2 rounded-xl bg-default p-3.5 shadow-sm ring ring-default';
const statLabelClass = 'text-[11px] font-semibold tracking-[0.06em] text-dimmed uppercase';

/**
 * `badge` dipakai sebagai pembawa angka, lalu dirender ulang lewat slot #trailing jadi
 * teks polos — shell hanya punya hitungan anggota, tab lain memuat datanya sendiri.
 */
const tabs = computed(() => [
    { value: 'kanban', label: 'Kanban', icon: 'i-lucide-layout-grid' },
    { value: 'list', label: 'List', icon: 'i-lucide-list' },
    { value: 'backlog', label: 'Backlog', icon: 'i-lucide-inbox' },
    { value: 'detail', label: 'Details', icon: 'i-lucide-info' },
    { value: 'team', label: 'Team', icon: 'i-lucide-users', badge: shell.value.members.length },
    { value: 'timeline', label: 'Timeline', icon: 'i-lucide-chart-gantt' },
    { value: 'report', label: 'Report', icon: 'i-lucide-chart-line' },
]);

const activeKey = computed(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '');
    const last = path.split('/').pop() ?? 'kanban';
    return tabs.value.some((tab) => tab.value === last) ? last : 'kanban';
});

const navigate = (key: string | number) => {
    if (key === activeKey.value) return;
    router.visit(route(`project.show.${key}`, { encoded: project.value.id }), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="project.title">
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <UButton
                    icon="i-lucide-arrow-left"
                    color="neutral"
                    variant="outline"
                    aria-label="Back to projects"
                    @click="router.visit(route('project.index'))"
                />

                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-elevated text-muted">
                    <Icon v-if="project.emoji" :name="project.emoji" class="size-4" />
                    <UIcon v-else name="i-lucide-folder" class="size-4" />
                </span>

                <h1 class="min-w-0 truncate text-xl font-bold text-highlighted">{{ project.title }}</h1>

                <UBadge v-if="project.project_no" color="neutral" variant="outline" size="sm" class="font-mono" :label="project.project_no" />

                <UAvatarGroup v-if="shell.members.length" :max="5" size="md" class="ms-auto">
                    <UAvatar
                        v-for="member in shell.members"
                        :key="member.id"
                        :src="member.user.avatar_url ?? undefined"
                        :alt="member.user.name"
                        :title="member.user.name"
                        :text="getInitials(member.user.name)"
                    />
                </UAvatarGroup>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div :class="statCardClass">
                    <p :class="statLabelClass">Status</p>
                    <UBadge
                        v-if="project.status"
                        :color="severityColor(project.status.severity)"
                        variant="subtle"
                        :label="project.status.name"
                        class="rounded-full"
                    />
                    <span v-else class="text-sm text-muted">—</span>
                </div>

                <div :class="statCardClass">
                    <p :class="statLabelClass">Priority</p>
                    <UBadge
                        v-if="project.priority"
                        :color="severityColor(project.priority.severity)"
                        variant="subtle"
                        :label="project.priority.name"
                        class="rounded-full"
                    />
                    <span v-else class="text-sm text-muted">—</span>
                </div>

                <div :class="statCardClass">
                    <p :class="statLabelClass">Timeline</p>
                    <p class="flex w-full min-w-0 items-center gap-2 text-[13px]">
                        <UIcon name="i-lucide-calendar" class="size-4 shrink-0 text-muted" />
                        <span class="truncate tabular-nums">{{ timeline }}</span>
                    </p>
                </div>

                <div :class="statCardClass">
                    <p :class="statLabelClass">Project progress</p>
                    <div class="flex w-full items-center gap-2.5">
                        <UProgress :model-value="project.progress" :ui="{ base: 'h-[7px]' }" />
                        <span class="shrink-0 text-sm leading-none font-semibold tabular-nums">{{ project.progress }}%</span>
                    </div>
                </div>
            </div>

            <UTabs
                :items="tabs"
                :model-value="activeKey"
                :content="false"
                variant="link"
                class="w-full"
                :ui="{ list: 'overflow-x-auto overflow-y-hidden', indicator: 'bottom-0' }"
                @update:model-value="navigate"
            >
                <template #trailing="{ item }">
                    <span v-if="item.badge !== undefined" class="text-xs text-dimmed tabular-nums">{{ item.badge }}</span>
                </template>
            </UTabs>

            <slot />
        </div>
    </AppLayout>
</template>
