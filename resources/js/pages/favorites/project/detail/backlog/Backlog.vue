<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { fetchJson } from '@/lib/utils';
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import BacklogSection from './BacklogSection.vue';
import CompleteSprintDialog from './CompleteSprintDialog.vue';
import SprintFormDialog from './SprintFormDialog.vue';
import SprintSection from './SprintSection.vue';
import TaskEditDrawer from '../TaskEditDrawer.vue';
import type { BacklogBadge, BacklogEpic, BacklogSprint, BacklogTask, BacklogUser } from './types';

interface Props extends ShellProps {
    sprints?: BacklogSprint[];
    backlog?: BacklogTask[];
    epics?: BacklogEpic[];
    taskPriorities?: BacklogBadge[];
    taskStatuses?: BacklogBadge[];
    taskTypes?: BacklogBadge[];
    taskCategories?: BacklogBadge[];
    tags?: BacklogBadge[];
    assignableUsers?: BacklogUser[];
}

const props = defineProps<Props>();

const toast = useToast();

const overlay = useOverlay();
const confirm = useConfirmDialog();

const sprintForm = overlay.create(SprintFormDialog);
const completeDialog = overlay.create(CompleteSprintDialog);
const taskEditDrawer = overlay.create(TaskEditDrawer);

const { canAction } = useProjectPermissions(props.policy);
const canAct = computed(() => canAction('task', 'update'));
const canSprintCreate = computed(() => canAction('sprint', 'create'));
const canSprintUpdate = computed(() => canAction('sprint', 'update'));
const canSprintDelete = computed(() => canAction('sprint', 'delete'));

/**
 * Jumlah perpindahan yang requestnya masih berjalan. Selama masih ada yang berjalan,
 * props dari server diabaikan: payload `reload` milik perpindahan lama bisa datang
 * setelah perpindahan baru diterapkan dan akan membatalkannya.
 */
const pendingWrites = ref(0);

const localSprints = ref<BacklogSprint[]>([]);
const localBacklog = ref<BacklogTask[]>([]);

watch(
    () => props.sprints,
    (sprints) => {
        if (pendingWrites.value > 0) {
            return;
        }

        localSprints.value = (sprints ?? []).map((sprint) => ({ ...sprint, tasks: [...sprint.tasks] }));
    },
    { immediate: true },
);

watch(
    () => props.backlog,
    (backlog) => {
        if (pendingWrites.value > 0) {
            return;
        }

        localBacklog.value = [...(backlog ?? [])];
    },
    { immediate: true },
);

const sortedSprints = computed(() => [...localSprints.value].sort((a, b) => (a.order ?? 0) - (b.order ?? 0)));
const sprintOptions = computed(() => sortedSprints.value.map((sprint) => ({ id: sprint.id, name: sprint.name })));

const creatingSprint = ref(false);

const createSprint = async () => {
    creatingSprint.value = true;

    try {
        await fetchJson(route('project.sprints.store', { projectEncoded: props.project.id }), 'POST');
        toast.add({ title: 'Success', description: 'New sprint created.', color: 'success' });
    } catch (error) {
        toast.add({ title: 'Failed', description: errorMessage(error, 'Could not create the sprint.'), color: 'error' });
    } finally {
        creatingSprint.value = false;
        reload();
    }
};

const openSprintForm = async (sprint: BacklogSprint, mode: 'start' | 'edit') => {
    const saved = await sprintForm.open({ projectId: props.project.id, sprint, mode });

    if (saved) {
        toast.add({ title: 'Success', description: mode === 'start' ? `${sprint.name} started.` : 'Sprint updated.', color: 'success' });
        reload();
    }
};

const completeSprint = async (sprint: BacklogSprint) => {
    const completed = await completeDialog.open({
        projectId: props.project.id,
        sprint,
        otherSprints: sprintOptions.value.filter((option) => option.id !== sprint.id),
    });

    if (completed) {
        toast.add({ title: 'Success', description: `${sprint.name} completed.`, color: 'success' });
        reload();
    }
};

const deleteSprint = async (sprint: BacklogSprint) => {
    const confirmed = await confirm({
        title: 'Delete Sprint',
        description: `Delete ${sprint.name}? Its tasks will return to the backlog.`,
    });

    if (!confirmed) {
        return;
    }

    try {
        await fetchJson(route('project.sprints.destroy', { projectEncoded: props.project.id, sprintEncoded: sprint.id }), 'DELETE');
        toast.add({ title: 'Success', description: `${sprint.name} deleted.`, color: 'success' });
    } catch (error) {
        toast.add({ title: 'Failed', description: errorMessage(error, 'Could not delete the sprint.'), color: 'error' });
    } finally {
        reload();
    }
};

const reload = () => router.reload({ only: ['sprints', 'backlog', 'epics'] });

const errorMessage = (error: unknown, fallback: string) => (error instanceof Error ? error.message : fallback);

const removeTaskLocally = (taskId: string, sprintId: string | null) => {
    if (sprintId) {
        const source = localSprints.value.find((sprint) => sprint.id === sprintId);

        if (source) {
            source.tasks = source.tasks.filter((candidate) => candidate.id !== taskId);
        }

        return;
    }

    localBacklog.value = localBacklog.value.filter((candidate) => candidate.id !== taskId);
};

const addTaskLocally = (task: BacklogTask, sprintId: string | null) => {
    if (sprintId) {
        const target = localSprints.value.find((sprint) => sprint.id === sprintId);

        if (target && !target.tasks.some((candidate) => candidate.id === task.id)) {
            target.tasks = [...target.tasks, task];
        }

        return;
    }

    if (!localBacklog.value.some((candidate) => candidate.id === task.id)) {
        localBacklog.value = [task, ...localBacklog.value];
    }
};

/**
 * Satu jalur untuk perpindahan satu maupun banyak task. Kedua endpoint menerima daftar id,
 * jadi memindahkan sepuluh task tetap dua request — bukan dua puluh.
 */
const runMove = async (tasks: BacklogTask[], fromSprintId: string | null, toSprintId: string | null) => {
    if (!tasks.length || fromSprintId === toSprintId) {
        return;
    }

    for (const task of tasks) {
        removeTaskLocally(task.id, fromSprintId);
        addTaskLocally(task, toSprintId);
    }

    const taskIds = tasks.map((task) => task.id);

    pendingWrites.value += 1;

    try {
        if (toSprintId) {
            await fetchJson(route('project.sprints.tasks.assign', { projectEncoded: props.project.id, sprintEncoded: toSprintId }), 'POST', {
                task_ids: taskIds,
            });
        }

        // `assignTasks` memakai syncWithoutDetaching, jadi sprint asal harus dilepas
        // terpisah supaya task tidak tampil di dua sprint sekaligus.
        if (fromSprintId) {
            await fetchJson(route('project.sprints.tasks.bulk-remove', { projectEncoded: props.project.id, sprintEncoded: fromSprintId }), 'DELETE', {
                task_ids: taskIds,
            });
        }
    } catch (error) {
        toast.add({ title: 'Failed', description: errorMessage(error, 'Could not move the task.'), color: 'error' });
    } finally {
        pendingWrites.value -= 1;

        if (pendingWrites.value === 0) {
            reload();
        }
    }
};

const moveTask = (task: BacklogTask, fromSprintId: string | null, toSprintId: string | null) => runMove([task], fromSprintId, toSprintId);

const findTaskLocally = (taskId: string) =>
    localBacklog.value.find((task) => task.id === taskId) ?? localSprints.value.flatMap((sprint) => sprint.tasks).find((task) => task.id === taskId);

const assignEpic = async (task: BacklogTask, epicId: string | null) => {
    const target = findTaskLocally(task.id);

    if (target) {
        target.parent_id = epicId;
    }

    pendingWrites.value += 1;

    try {
        await fetchJson(route('project.tasks.parent.update', { projectEncoded: props.project.id, task: task.id }), 'PUT', {
            parent_id: epicId,
        });
    } catch (error) {
        toast.add({ title: 'Failed', description: errorMessage(error, 'Could not update the epic.'), color: 'error' });
    } finally {
        pendingWrites.value -= 1;

        if (pendingWrites.value === 0) {
            reload();
        }
    }
};

const editTask = async (task: BacklogTask) => {
    const changed = await taskEditDrawer.open({
        projectId: props.project.id,
        project: props.project,
        task,
        priorities: props.taskPriorities ?? [],
        statuses: props.taskStatuses ?? [],
        types: props.taskTypes ?? [],
        categories: props.taskCategories ?? [],
        tags: props.tags ?? [],
        assignableUsers: props.assignableUsers ?? [],
    });

    if (changed) {
        reload();
    }
};

const bulkMoveTasks = (tasks: BacklogTask[], fromSprintId: string | null, toSprintId: string | null) => runMove(tasks, fromSprintId, toSprintId);
</script>

<template>
    <ProjectShellLayout>
        <Head :title="`Backlog - ${project.title}`" />
        <Deferred :data="['sprints', 'backlog']">
            <template #fallback>
                <div class="flex flex-col gap-4" role="status" aria-label="Loading backlog">
                    <USkeleton v-for="n in 2" :key="n" class="h-48 w-full rounded-xl" />
                </div>
            </template>

            <div class="flex min-w-0 flex-col gap-5">
                <div class="flex min-w-0 flex-col gap-4">
                    <SprintSection
                        v-for="sprint in sortedSprints"
                        :key="sprint.id"
                        :sprint="sprint"
                        :epics="epics ?? []"
                        :sprints="sprintOptions"
                        :can-act="canAct"
                        :can-sprint-update="canSprintUpdate"
                        :can-sprint-delete="canSprintDelete"
                        @edit="editTask"
                        @move="moveTask"
                        @assign-epic="assignEpic"
                        @start-sprint="(sprint) => openSprintForm(sprint, 'start')"
                        @edit-sprint="(sprint) => openSprintForm(sprint, 'edit')"
                        @complete-sprint="completeSprint"
                        @delete-sprint="deleteSprint"
                        @bulk-move="bulkMoveTasks"
                    />
                    <div v-if="!sortedSprints.length" class="flex items-center gap-3 rounded-xl border border-dashed border-default px-5 py-4">
                        <UIcon name="i-lucide-calendar-range" class="size-5 text-dimmed" />
                        <p class="text-sm text-muted">No sprints yet. Unscheduled work stays in the backlog below.</p>
                    </div>
                    <BacklogSection
                        :tasks="localBacklog"
                        :epics="epics ?? []"
                        :sprints="sprintOptions"
                        :can-act="canAct"
                        :can-sprint-create="canSprintCreate"
                        :creating-sprint="creatingSprint"
                        @edit="editTask"
                        @move="moveTask"
                        @assign-epic="assignEpic"
                        @create-sprint="createSprint"
                        @bulk-move="bulkMoveTasks"
                    />
                </div>
            </div>
        </Deferred>
    </ProjectShellLayout>
</template>
