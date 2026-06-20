<script setup lang="ts">
import TaskForm from '@/pages/project/task/Form.vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { ParentTaskOption, SlimUser, TagOption, TaskCategoryOption, TaskPriorityOption, TaskStatusOption, TaskTypeOption } from '../index';

interface Props {
    projectId: string;
    taskStatuses: TaskStatusOption[];
    taskPriorities: TaskPriorityOption[];
    taskTypes: TaskTypeOption[];
    taskCategories: TaskCategoryOption[];
    tags: TagOption[];
    assignableUsers: SlimUser[];
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'saved'): void }>();

const toast = useToast();

const visible = ref(false);
const loading = ref(false);
const task = ref<any | null>(null);
const parentTree = ref<any[]>([]);
const parentId = ref<string | null>(null);

// The reused TaskForm reads members as `[{ user }]`.
const members = computed(() => props.assignableUsers.map((user) => ({ user })));

const header = computed(() => {
    if (task.value) return 'Edit Task';
    return parentId.value ? 'Create Subtask' : 'Create Task';
});

const buildTree = (flat: ParentTaskOption[]): any[] => {
    const byId = new Map<string, any>();
    flat.forEach((node) => {
        byId.set(String(node.id), { id: String(node.id), title: node.title, category: node.category, sub_task_recursive: [] });
    });

    const roots: any[] = [];
    flat.forEach((node) => {
        const current = byId.get(String(node.id));
        const pid = node.parent_id != null ? String(node.parent_id) : null;
        if (pid && byId.has(pid)) {
            byId.get(pid).sub_task_recursive.push(current);
        } else {
            roots.push(current);
        }
    });

    return roots;
};

const fetchParentOptions = async () => {
    try {
        const response = await axios.get(route('project.tasks.parent-options', { projectEncoded: props.projectId }));
        parentTree.value = buildTree(response.data.data ?? []);
    } catch {
        parentTree.value = [];
    }
};

const openCreate = async (parent: string | null = null) => {
    task.value = null;
    parentId.value = parent;
    visible.value = true;
    await fetchParentOptions();
};

const openEdit = async (taskCard: { id: string; parent_id?: string | null }) => {
    parentId.value = taskCard.parent_id ?? null;
    visible.value = true;
    loading.value = true;
    try {
        const [edit] = await Promise.all([
            axios.get(route('project.tasks.edit', { projectEncoded: props.projectId, task: taskCard.id })),
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

const onSaved = () => {
    emit('saved');
    close();
};

const close = () => {
    visible.value = false;
    task.value = null;
    parentId.value = null;
    parentTree.value = [];
};

defineExpose({ openCreate, openEdit });
</script>

<template>
    <Drawer
        v-model:visible="visible"
        :header="header"
        modal
        scrollable
        maximizable
        dismissable-mask
        block-scroll
        position="right"
        :style="{ width: '50rem' }"
        :contentStyle="{ maxHeight: '75vh' }"
        :breakpoints="{ '1200px': '80vw', '960px': '90vw', '640px': '100vw' }"
        @hide="close"
    >
        <div v-if="loading" class="flex flex-col gap-3">
            <Skeleton height="2.5rem" />
            <Skeleton height="6rem" />
            <Skeleton height="2.5rem" />
            <Skeleton height="2.5rem" />
        </div>

        <TaskForm
            v-else
            :projectId="props.projectId"
            :parentId="parentId"
            :task="task"
            :tasks="parentTree"
            :taskTypes="props.taskTypes"
            :taskStatuses="props.taskStatuses"
            :taskPriorities="props.taskPriorities"
            :taskCategories="props.taskCategories"
            :tags="props.tags"
            :members="members"
            :editTask="task"
            @saved="onSaved"
            @close="close"
        />
    </Drawer>
</template>
