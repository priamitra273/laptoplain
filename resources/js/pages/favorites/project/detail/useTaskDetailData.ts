import { onScopeDispose, ref } from 'vue';
import { isTaskDetail, type TaskDetail } from './taskDetail';

/** Menggantikan salinan logic fetch+abort+retry yang sebelumnya terpisah di TaskDetailPanel, TaskViewDrawer, dan TaskEditDrawer. */
export function useTaskDetailData(projectId: () => string, taskId: () => string) {
    const loading = ref(true);
    const failed = ref(false);
    const detail = ref<TaskDetail | null>(null);
    let controller: AbortController | null = null;

    const load = async () => {
        controller?.abort();
        const ownController = new AbortController();
        controller = ownController;
        loading.value = true;
        failed.value = false;
        detail.value = null;

        try {
            const response = await fetch(route('project.tasks.edit', { projectEncoded: projectId(), task: taskId() }), {
                headers: { Accept: 'application/json' },
                signal: ownController.signal,
            });

            if (!response.ok) throw new Error('Could not load task details.');

            const data = (await response.json()).data;

            if (!isTaskDetail(data)) throw new Error('Incomplete task details.');
            if (!ownController.signal.aborted) detail.value = data;
        } catch {
            if (!ownController.signal.aborted) failed.value = true;
        } finally {
            if (!ownController.signal.aborted) loading.value = false;
        }
    };

    onScopeDispose(() => controller?.abort());

    return { loading, failed, detail, load };
}
