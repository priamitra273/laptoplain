<script setup>
import Button from 'primevue/button';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { computed } from 'vue';

const props = defineProps({
    tasks: { type: Array, required: true },
    statuses: Array,
    priorities: Array,
    types: Array,
});

const emit = defineEmits(['edit', 'delete']);

// Convert recursive tasks into treetable node format
const formatTasks = (taskList) => {
    return taskList.map((task) => ({
        key: task.id,
        data: {
            id: task.id,
            title: task.title,
            status: task.status,
            priority: task.priority,
            type: task.type,
            users: task.users,
        },
        children: task.children_recursive ? formatTasks(task.children_recursive) : [],
    }));
};

const nodes = computed(() => formatTasks(props.tasks));
</script>

<template>
    <TreeTable :value="nodes" tableStyle="min-width: 50rem">
        <Column field="title" header="Title" expander></Column>

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

        <Column header="Assignees">
            <template #body="{ node }">
                <span v-for="u in node.data.users" :key="u.id" class="mr-2">{{ u.name }}</span>
            </template>
        </Column>

        <Column header="Action">
            <template #body="{ node }">
                <Button icon="pi pi-pencil" rounded text @click="emit('edit', node.data)" />
                <Button icon="pi pi-trash" severity="danger" text rounded @click="emit('delete', node.data.id)" />
            </template>
        </Column>
    </TreeTable>
</template>
