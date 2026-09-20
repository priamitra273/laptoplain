<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { ProjectPolicyKey } from '@/types/type';
import { router, usePage } from '@inertiajs/vue3';
import { computed, provide, ref, watch } from 'vue';
import ProjectHeader from './ProjectHeader.vue';
import ProjectStatCards from './ProjectStatCards.vue';
import type { ShellProps } from '../types';

interface Props {
    liveProgress?: number;
}

const props = defineProps<Props>();

const page = usePage();
const toast = useToast();

const shell = computed(() => page.props as unknown as ShellProps);
const project = computed(() => shell.value.project);

provide(ProjectPolicyKey, shell.value.policy);

const canEdit = computed(() => useProjectPermissions(shell.value.policy).canAction('project_member', 'update'));

const internalProgress = ref(props.liveProgress ?? project.value.progress);

const projectForStats = computed(() => ({ ...project.value, progress: internalProgress.value }));

watch(
    () => props.liveProgress,
    (value) => {
        if (value !== undefined) {
            internalProgress.value = value;
        }
    },
);

watch(
    () => project.value.progress,
    (value) => {
        internalProgress.value = value;
    },
);

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

const updateProject = (value: string, field: string) => {
    if (!canEdit.value) {
        toast.add({ title: 'Access Denied', description: 'You do not have permission to edit this project.', color: 'warning' });
        return;
    }

    router.put(
        route('project.update', project.value.id),
        { [field]: value },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                const message = Object.values(errors)[0];
                toast.add({ title: 'Failed', description: message ? String(message) : 'Could not update project.', color: 'error' });
            },
        },
    );
};
</script>

<template>
    <AppLayout :title="project.title">
        <div class="flex flex-col gap-4">
            <ProjectHeader :project="project" :members="shell.members" :can-edit="canEdit" :is-member="shell.isMember" @update="updateProject" />

            <ProjectStatCards :project="projectForStats" :statuses="shell.statuses" :priorities="shell.priorities" :can-edit="canEdit" @update="updateProject" />

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
