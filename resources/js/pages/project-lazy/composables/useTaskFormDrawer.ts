import type { ParentTaskNode, ParentTaskOption, SavedTaskPayload, TaskFormPayload } from '@/pages/project-lazy';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

export const buildParentTree = (flat: ParentTaskOption[]): ParentTaskNode[] => {
    const byId = new Map<string, ParentTaskNode>();
    flat.forEach((node) => {
        byId.set(String(node.id), {
            id: String(node.id),
            title: node.title,
            category: node.category ?? null,
            sub_task_recursive: [],
        });
    });

    const roots: ParentTaskNode[] = [];
    flat.forEach((node) => {
        const current = byId.get(String(node.id))!;
        const pid = node.parent_id != null ? String(node.parent_id) : null;
        if (pid && byId.has(pid)) {
            byId.get(pid)!.sub_task_recursive.push(current);
        } else {
            roots.push(current);
        }
    });

    return roots;
};

export const useTaskFormDrawer = (projectId: string, emit: (e: 'saved', payload: SavedTaskPayload) => void) => {
    const toast = useToast();

    const visible = ref(false);
    const loading = ref(false);
    const task = ref<TaskFormPayload | null>(null);
    const parentTree = ref<ParentTaskNode[]>([]);
    const parentId = ref<string | null>(null);

    const header = computed(() => {
        if (task.value) {
            return 'Edit Task';
        }
        return parentId.value ? 'Create Subtask' : 'Create Task';
    });

    const fetchParentOptions = async (): Promise<void> => {
        try {
            const response = await axios.get(route('project.tasks.parent-options', { projectEncoded: projectId }));
            parentTree.value = buildParentTree(response.data.data ?? []);
        } catch {
            parentTree.value = [];
        }
    };

    const openCreate = async (parent: string | null = null): Promise<void> => {
        task.value = null;
        parentId.value = parent;
        visible.value = true;
        loading.value = true;
        try {
            await fetchParentOptions();
        } finally {
            loading.value = false;
        }
    };

    const openEdit = async (taskCard: { id: string; parent_id?: string | null }): Promise<void> => {
        parentId.value = taskCard.parent_id ?? null;
        visible.value = true;
        loading.value = true;
        try {
            const [edit] = await Promise.all([
                axios.get(route('project.tasks.edit', { projectEncoded: projectId, task: taskCard.id })),
                fetchParentOptions(),
            ]);
            task.value = edit.data.data;
        } catch {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load task', life: 3000 });
            visible.value = false;
        } finally {
            loading.value = false;
        }
    };

    const close = (): void => {
        visible.value = false;
        task.value = null;
        parentId.value = null;
        parentTree.value = [];
    };

    const onSaved = (payload: SavedTaskPayload): void => {
        emit('saved', payload);
    };

    return { visible, loading, task, parentTree, parentId, header, openCreate, openEdit, onSaved, close };
};
