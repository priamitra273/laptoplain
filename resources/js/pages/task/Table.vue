<script setup lang="ts">
import Button from 'primevue/button';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { Task, TaskFormatted } from '../project';

interface Props {
    tasks: Task[]
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'add'): void;
    (e: 'edit', task: Task): void;
}>();

const formatTasks = (list?: Task[]): TaskFormatted[] => {
  if (!list || !Array.isArray(list)) return [];

  return list.map((t) => ({
    key: t.id,
    original: t,
    data: {
      title: t.title,
      status: t.status,
      priority: t.priority,
      type: t.type,
    },
    children: t.sub_task_recursive
      ? formatTasks(t.sub_task_recursive)
      : [],
  }));
};

</script>

<template>
    <div class="flex flex-row justify-between">
        <h3 class="mb-4 text-lg font-semibold">Tasks</h3>
        <Button label="Add Task" icon="pi pi-plus" @click="emit('add')" />
    </div>
    <TreeTable :value="formatTasks(props.tasks)" tableStyle="min-width: 50rem">
        <Column field="title" header="Title" expander />

        <Column header="Status">
            <template #body="{ node }">
                <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
            </template>
        </Column>

        <Column header="Priority">
            <template #body="{ node }">
                <Tag :value="node.data.priority?.name" :severity="node.data.priority?.severity" />
            </template>
        </Column>

        <Column header="Type">
            <template #body="{ node }">
                <Tag :value="node.data.type?.name" :severity="node.data.type?.severity" />
            </template>
        </Column>

        <Column header="Actions">
            <template #body="{ node }">
                <Button icon="pi pi-pencil" severity="warning" size="small" @click="emit('edit', node.original)" />
            </template>
        </Column>
    </TreeTable>
</template>
