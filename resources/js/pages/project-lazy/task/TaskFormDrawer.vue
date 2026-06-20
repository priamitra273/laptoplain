<script setup lang="ts">
import type { LazyMember, SlimUser, TagOption, TaskCategoryOption, TaskPriorityOption, TaskStatusOption, TaskTypeOption } from '@/pages/project-lazy';
import { computed } from 'vue';
import TaskForm from './TaskForm.vue';
import { useTaskFormDrawer } from './composables/useTaskFormDrawer';

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

const { visible, loading, task, parentTree, parentId, header, openCreate, openEdit, onSaved, close } = useTaskFormDrawer(props.projectId, emit);

// The reused TaskForm reads members as `[{ user }]`.
const members = computed<LazyMember[]>(() => props.assignableUsers.map((user) => ({ user })));

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
            @saved="onSaved"
            @close="close"
        />
    </Drawer>
</template>
