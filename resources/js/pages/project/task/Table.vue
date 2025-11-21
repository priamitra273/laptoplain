<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import Swal from 'sweetalert2';
import { Task, TaskFormatted } from '..';

interface Props {
    projectId: string;
    tasks: Task[];
    isPM: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: Task): void;
}>();

const formatTasks = (list?: Task[]): TaskFormatted[] => {
    if (!list || !Array.isArray(list)) return [];

    return list.map((t) => ({
        key: t.id,
        original: t,
        data: {
            id: t.id,
            title: t.title,
            status: t.status,
            priority: t.priority,
            type: t.type,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive) : [],
    }));
};

const remove = (t: Task) => {
    const text =
        t.children && t.children.length > 0 ? "This task has children. Removing it will removing it's children." : 'This action cannot be undone.';
    Swal.fire({
        icon: 'warning',
        title: `Remove ${t.title}?`,
        text: text,
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(
                route('project.tasks.destroy', {
                    projectEncoded: props.projectId,
                    taskEncoded: t.id,
                }),
                {
                    onSuccess: () => Swal.fire('Deleted', 'Task removed', 'success'),
                    preserveScroll: true,
                },
            );
        }
    });
};
</script>

<template>
    <div class="flex flex-row justify-between">
        <h3 class="mb-4 text-lg font-semibold">Tasks</h3>
        <Button label="Add Task" icon="pi pi-plus" @click="emit('add', null)" />
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
                <Button icon="pi pi-plus" severity="help" size="small" @click="emit('add', node.data.id)" />
                <Button icon="pi pi-pencil" severity="warning" size="small" @click="emit('edit', node.original)" />
                <Button icon="pi pi-user-plus" class="p-button-sm p-button-info" @click="$emit('assign', node.original)" />
                <Button icon="pi pi-trash" size="small" @click="remove(node.original)"></Button>
            </template>
        </Column>
    </TreeTable>
</template>
