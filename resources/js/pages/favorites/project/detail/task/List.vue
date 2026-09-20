<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { fetchJson } from '@/lib/utils';
import { Deferred, Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onScopeDispose, ref } from 'vue';
import TaskDetailSlideover from '../TaskDetailSlideover.vue';
import TaskEditDrawer from '../TaskEditDrawer.vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import { useTaskDelete } from '../useTaskDelete';
import TaskCreateDrawer from './TaskCreateDrawer.vue';
import TaskTable from './TaskTable.vue';
import { flattenTasks } from './taskTree';
import type { ListProps, ListTask, TaskMove } from './types';

const props = defineProps<ListProps>();
const page = usePage();
const userId = computed(() => String(page.props.auth.user.id));
const { canAction } = useProjectPermissions(props.policy);
const canCreate = computed(() => canAction('task', 'create'));
const canEdit = computed(() => canAction('task', 'update'));
const canDelete = computed(() => canAction('task', 'delete'));
const busy = ref(false);
const reloadFailed = ref(false);
const initialLoadFailed = ref(false);
const subscriptions: (() => void)[] = [];
onMounted(() => {
    const failInitialLoad = () => {
        if (props.tasks === undefined || props.assignableUsers === undefined) {
            initialLoadFailed.value = true;
            return false;
        }
    };
    subscriptions.push(router.on('httpException', failInitialLoad), router.on('networkError', failInitialLoad));
});
onScopeDispose(() => subscriptions.forEach((unsubscribe) => unsubscribe()));
const overlay = useOverlay();
const toast = useToast();
const { deleteTask: deleteTaskRequest } = useTaskDelete(() => props.project.id);
const editDrawer = overlay.create(TaskEditDrawer);
const createDrawer = overlay.create(TaskCreateDrawer);
const viewDrawer = overlay.create(TaskDetailSlideover);
const options = () => ({
    projectId: props.project.id,
    statuses: props.taskStatuses,
    priorities: props.taskPriorities,
    types: props.taskTypes,
    categories: props.taskCategories,
    tags: props.tags,
    assignableUsers: props.assignableUsers ?? [],
});
const reload = () =>
    new Promise<void>((resolve) => {
        let succeeded = false;
        busy.value = true;
        reloadFailed.value = false;
        initialLoadFailed.value = false;
        router.reload({
            only: ['tasks', 'assignableUsers', 'project', 'tags'],
            onHttpException: () => false,
            onNetworkError: () => false,
            onSuccess: () => {
                succeeded = true;
            },
            onFinish: () => {
                reloadFailed.value = !succeeded;
                busy.value = false;
                resolve();
            },
        });
    });
const edit = async (task: ListTask) => {
    if (!canEdit.value || busy.value) return;
    // Priority tidak ada pada payload List; drawer mengambil nilai sebenarnya sebelum submit.
    if (await editDrawer.open({ ...options(), project: props.project, task: { ...task, priority: null } })) await reload();
};
const create = async (parent: ListTask | null) => {
    if (!canCreate.value || busy.value) return;
    const changed = await createDrawer.open({
        ...options(),
        project: props.project,
        parent,
        parents: flattenTasks(props.tasks ?? []).map(({ id, title, parent_id }) => ({ id, title, parent_id })),
    });
    if (changed) {
        toast.add({ title: 'Success', description: 'Task created.', color: 'success' });
        await reload();
    }
};
const view = async (task: ListTask, history = false) => {
    const result = await viewDrawer.open({
        task,
        projectId: props.project.id,
        projectTitle: props.project.title,
        priorities: props.taskPriorities,
        assignableUsers: props.assignableUsers ?? [],
        history,
        canEdit: canEdit.value && !busy.value,
        canDelete: canDelete.value && !busy.value,
    });

    if (result === 'edit') {
        await edit(task);
        return;
    }

    if (result === 'delete') {
        await remove(task);
        return;
    }

    if (result && typeof result === 'object' && 'open' in result) {
        await view(result.open as ListTask);
    }
};
const remove = async (task: ListTask) => {
    if (!canDelete.value || busy.value) return;
    busy.value = true;
    try {
        if ((await deleteTaskRequest(task)) !== undefined) {
            await reload();
        }
    } finally {
        busy.value = false;
    }
};
const move = async (value: TaskMove) => {
    if (!canEdit.value || busy.value) return;
    busy.value = true;
    try {
        const result = await fetchJson<{ success: boolean }>(
            route('project.tasks.move', { projectEncoded: props.project.id, task: value.taskId }),
            'PUT',
            { parent_id: value.parentId, position: value.position },
        );
        if (result?.success !== true) throw new Error('Could not move the task.');
        await reload();
    } catch (error) {
        toast.add({ title: 'Failed', description: error instanceof Error ? error.message : 'Could not move the task.', color: 'error' });
    } finally {
        busy.value = false;
    }
};
</script>

<template>
    <ProjectShellLayout>
        <Head :title="`List - ${project.title}`" />
        <div class="flex min-w-0 flex-col gap-4">
            <UAlert
                v-if="reloadFailed"
                color="error"
                title="Could not refresh tasks."
                description="The table may be out of date. Retry to load the latest changes."
                :actions="[{ label: 'Retry', onClick: reload }]"
            />
            <UAlert v-if="initialLoadFailed" color="error" title="Could not load tasks." :actions="[{ label: 'Retry', onClick: reload }]" />
            <Deferred v-else :data="['tasks', 'assignableUsers']">
                <template #fallback
                    ><div role="status" aria-label="Loading task list" class="flex flex-col gap-4">
                        <USkeleton class="h-10 w-full" />
                        <div class="border-default overflow-hidden rounded-xl border"><USkeleton v-for="n in 6" :key="n" class="m-4 h-9" /></div></div
                ></template>
                <template #rescue="{ reloading }"
                    ><UAlert color="error" title="Could not load tasks." :actions="[{ label: 'Retry', loading: reloading, onClick: reload }]"
                /></template>
                <TaskTable
                    :project-id="project.id"
                    :user-id="userId"
                    :tasks="tasks ?? []"
                    :statuses="taskStatuses"
                    :can-create="canCreate"
                    :can-edit="canEdit"
                    :can-delete="canDelete"
                    :busy="busy || reloadFailed"
                    @create="create"
                    @edit="edit"
                    @view="view"
                    @open="(task: ListTask) => router.visit(route('task.show', { task: task.id }))"
                    @delete="remove"
                    @move="move"
                />
            </Deferred>
        </div>
    </ProjectShellLayout>
</template>
