<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import type { ShellProps, TabItem } from '@/pages/project-lazy';
import { ProjectPolicyKey } from '@/types/type';
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, provide, ref, watch } from 'vue';
import ProjectHeader from '../partials/ProjectHeader.vue';
import ProjectStats from '../partials/ProjectStats.vue';

interface Props {
    liveProgress?: number;
}

const page = usePage();
const toast = useToast();

const props = defineProps<Props>();

const shell = computed(() => page.props as unknown as ShellProps);
const project = computed(() => shell.value.project);

const internalProgress = ref(props.liveProgress ?? project.value.progress);

const projectForStats = computed(() => ({ ...project.value, progress: internalProgress.value }));

// Children expect the raw policy value (see useProjectPermissions); tab pages that
// need the freshest policy read it from page props directly.
provide(ProjectPolicyKey, shell.value.policy);

const canEdit = computed(() => useProjectPermissions(shell.value.policy).canAction('project_member', 'update'));

const tabs: TabItem[] = [
    { key: 'kanban', label: 'Kanban', icon: 'pi pi-th-large' },
    { key: 'list', label: 'List', icon: 'pi pi-list' },
    { key: 'backlog', label: 'Backlog', icon: 'pi pi-inbox' },
    { key: 'detail', label: 'Details', icon: 'pi pi-info-circle' },
    { key: 'team', label: 'Team', icon: 'pi pi-users' },
    { key: 'timeline', label: 'Timeline', icon: 'pi pi-chart-bar' },
    { key: 'report', label: 'Report', icon: 'pi pi-chart-line' },
];

// page.url is reactive across Inertia visits, so the active tab tracks navigation.
const activeKey = computed(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '');
    const last = path.split('/').pop() ?? 'kanban';
    return tabs.some((t) => t.key === last) ? last : 'kanban';
});

const navigate = (key: string) => {
    if (key === activeKey.value) return;

    router.visit(route(`project.show.${key}`, { encoded: project.value.id }), {
        preserveScroll: true,
    });
};

const updateProject = (newValue: any, field: string) => {
    if (!canEdit.value) {
        toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You do not have permission to edit this project', life: 3000 });
        return;
    }

    let value = newValue;

    if (field === 'start_date' || field === 'due_date') {
        value = moment(newValue).format('YYYY-MM-DD');
    }

    // ProjectUpdateRequest uses `sometimes` rules — a partial update of just the changed field is valid.
    router.put(
        route('project.update', project.value.id),
        { [field]: value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Success', detail: 'Project updated successfully', life: 3000 });
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Error', detail: errors[Object.keys(errors)[0]] || 'Failed to update project', life: 3000 });
            },
        },
    );
};

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
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-4">
            <ProjectHeader :project="project" :members="shell.members" :canEdit="canEdit" :isMember="shell.isMember" @update="updateProject" />

            <ProjectStats
                :project="projectForStats"
                :statuses="shell.statuses || []"
                :priorities="shell.priorities || []"
                :canEdit="canEdit"
                @update="updateProject"
            />

            <Card class="shadow-sm">
                <template #content>
                    <Tabs :value="activeKey">
                        <TabList scrollable>
                            <Tab
                                v-for="tab in tabs"
                                :key="tab.key"
                                :value="tab.key"
                                v-tooltip.bottom="tab.label"
                                class="!px-3 sm:!px-4"
                                @click="navigate(tab.key)"
                            >
                                <i :class="tab.icon" class="sm:mr-2"></i>
                                <span class="hidden sm:inline">{{ tab.label }}</span>
                            </Tab>
                        </TabList>
                    </Tabs>

                    <div class="py-4">
                        <slot />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
