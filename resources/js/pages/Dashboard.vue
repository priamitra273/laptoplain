<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import type { PrimeSeverity, Project } from '@/types';
import { Head } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import { computed } from 'vue';

interface Member {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

interface TaskRow {
    id: string;
    title: string;
    project?: { title: string; emoji?: string } | null;
    status: { name: string; severity: PrimeSeverity };
    priority: { name: string; severity: PrimeSeverity };
}

interface StatusCount {
    name: string;
    count: number;
    severity: PrimeSeverity;
}

const props = defineProps<{
    projects: Project[];
    tasks: TaskRow[];
    stats: {
        tasks: { total: number; progress: number; byStatus?: StatusCount[] };
        projects: { total: number; progress: number; byStatus?: StatusCount[] };
        members: { total: number; list: Member[] };
    };
}>();

/** Hitung sebaran status dari baris yang ada bila server tidak menyertakan `byStatus`. */
const breakdown = (rows: Array<{ status: { name: string; severity: PrimeSeverity } }>, provided?: StatusCount[]): StatusCount[] => {
    if (provided) {
        return provided;
    }

    const counts = new Map<string, StatusCount>();

    for (const row of rows) {
        const existing = counts.get(row.status.name);

        if (existing) {
            existing.count++;
        } else {
            counts.set(row.status.name, { name: row.status.name, count: 1, severity: row.status.severity });
        }
    }

    return [...counts.values()];
};

const taskBreakdown = computed(() => breakdown(props.tasks, props.stats.tasks.byStatus));
const projectBreakdown = computed(() => breakdown(props.projects, props.stats.projects.byStatus));

const members = computed(() => props.stats.members.list.filter(Boolean));

// Jangan dipotong di sini: UAvatarGroup menghitung badge "+N" dari jumlah anaknya sendiri,
// jadi memotong lebih dulu membuat sisanya tidak pernah terhitung.
const memberAvatars = computed(() =>
    members.value.map((member) => ({
        id: member.id,
        src: member.avatar_url && !member.avatar_url.includes('default-avatar') ? member.avatar_url : undefined,
        text: getInitials(member.name),
        alt: member.name,
    })),
);

const stats = computed(() => [
    { key: 'tasks', label: 'Tugas', icon: 'i-lucide-square-check-big', total: props.stats.tasks.total, progress: props.stats.tasks.progress },
    { key: 'projects', label: 'Proyek', icon: 'i-lucide-briefcase', total: props.stats.projects.total, progress: props.stats.projects.progress },
]);

const relative = new Intl.RelativeTimeFormat('id', { numeric: 'auto' });
const absolute = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

/** Jatuh tempo di masa depan tampil relatif ("dalam 3 hari"); yang lewat tampil absolut dan merah. */
const dueDate = (value: string | null | undefined) => {
    if (!value) {
        return { label: '—', overdue: false };
    }

    const due = new Date(value);
    const days = Math.round((due.getTime() - Date.now()) / 86_400_000);

    return days < 0 ? { label: absolute.format(due), overdue: true } : { label: relative.format(days, 'day'), overdue: false };
};

// ponytail: emoji disimpan sebagai shortcode emoji-mart (`:rocket:`) dan butuh index 1 MB
// untuk diterjemahkan. Sampai EmojiPicker dimigrasi, shortcode diganti ikon netral.
const emojiOf = (value: string | null | undefined) => (value && !value.startsWith(':') ? value : null);

const projectColumns: TableColumn<Project>[] = [
    { accessorKey: 'title', header: 'Proyek' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'priority', header: 'Prioritas' },
    { accessorKey: 'due_date', header: 'Jatuh tempo' },
];

const taskColumns: TableColumn<TaskRow>[] = [
    { accessorKey: 'title', header: 'Tugas' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'priority', header: 'Prioritas' },
];
</script>

<template>
    <AppLayout title="Dashboard">

        <Head title="Dashboard" />

        <div class="flex flex-col gap-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <UCard v-for="stat in stats" :key="stat.key">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-muted">{{ stat.label }}</p>
                            <p class="mt-2 text-3xl font-semibold">{{ stat.total }}</p>
                        </div>
                        <UIcon :name="stat.icon" class="size-5 shrink-0 text-muted" />
                    </div>

                    <div class="mt-4 space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Progres</span>
                            <span class="font-medium tabular-nums">{{ stat.progress }}%</span>
                        </div>
                        <UProgress :model-value="stat.progress" size="sm" />
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5 border-t border-default pt-4">
                        <UBadge v-for="status in stat.key === 'tasks' ? taskBreakdown : projectBreakdown"
                            :key="status.name" :color="severityColor(status.severity)" variant="subtle" size="sm"
                            :label="`${status.name} (${status.count})`" />
                        <span v-if="!(stat.key === 'tasks' ? taskBreakdown : projectBreakdown).length"
                            class="text-xs text-muted">Belum ada data</span>
                    </div>
                </UCard>

                <UCard>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-muted">Anggota tim</p>
                            <p class="mt-2 text-3xl font-semibold">{{ members.length }}</p>
                        </div>
                        <UIcon name="i-lucide-users" class="size-5 shrink-0 text-muted" />
                    </div>

                    <div class="mt-4 border-t border-default pt-4">
                        <UAvatarGroup v-if="members.length" :max="5" size="md">
                            <UAvatar v-for="avatar in memberAvatars" :key="avatar.id" :src="avatar.src"
                                :text="avatar.text" :alt="avatar.alt" />
                        </UAvatarGroup>
                        <p v-else class="text-xs text-muted">Belum ada anggota tim</p>
                    </div>
                </UCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <UCard title="Proyek terbaru" description="Lima proyek yang terakhir dibuat.">
                    <template #footer>
                        <UButton :to="route('project.index')" label="Lihat semua" trailing-icon="i-lucide-arrow-right"
                            variant="link" size="sm" class="-mx-2" />
                    </template>

                    <UTable :data="props.projects" :columns="projectColumns">
                        <template #title-cell="{ row }">
                            <div class="flex items-center gap-2">
                                <span v-if="emojiOf(row.original.emoji)" class="text-lg leading-none">{{
                                    emojiOf(row.original.emoji) }}</span>
                                <UIcon v-else name="i-lucide-folder" class="size-4 shrink-0 text-muted" />
                                <ULink :to="route('project.show', String(row.original.id))"
                                    class="truncate font-medium">
                                    {{ row.original.title }}
                                </ULink>
                            </div>
                        </template>

                        <template #status-cell="{ row }">
                            <UBadge :color="severityColor(row.original.status?.severity)" variant="subtle"
                                :label="row.original.status?.name" />
                        </template>

                        <template #priority-cell="{ row }">
                            <UBadge :color="severityColor(row.original.priority?.severity)" variant="subtle"
                                :label="row.original.priority?.name" />
                        </template>

                        <template #due_date-cell="{ row }">
                            <span class="tabular-nums"
                                :class="{ 'text-error': dueDate(row.original.due_date).overdue }">
                                {{ dueDate(row.original.due_date).label }}
                            </span>
                        </template>

                        <template #empty>
                            <p class="text-center text-sm text-muted">Belum ada proyek</p>
                        </template>
                    </UTable>
                </UCard>

                <UCard title="Tugas terbaru" description="Lima tugas yang terakhir dibuat.">
                    <template #footer>
                        <UButton :to="route('task.index')" label="Lihat semua" trailing-icon="i-lucide-arrow-right"
                            variant="link" size="sm" class="-mx-2" />
                    </template>

                    <UTable :data="props.tasks" :columns="taskColumns">
                        <template #title-cell="{ row }">
                            <ULink :to="route('task.show', String(row.original.id))" class="truncate font-medium">
                                {{ row.original.title }}
                            </ULink>
                        </template>

                        <template #status-cell="{ row }">
                            <UBadge :color="severityColor(row.original.status?.severity)" variant="subtle"
                                :label="row.original.status?.name" />
                        </template>

                        <template #priority-cell="{ row }">
                            <UBadge :color="severityColor(row.original.priority?.severity)" variant="subtle"
                                :label="row.original.priority?.name" />
                        </template>

                        <template #empty>
                            <p class="text-center text-sm text-muted">Belum ada tugas</p>
                        </template>
                    </UTable>
                </UCard>
            </div>
        </div>
    </AppLayout>
</template>
