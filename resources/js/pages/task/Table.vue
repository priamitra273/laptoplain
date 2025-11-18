<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';

interface Task {
    id: number;
    parent_id: number | null;
    title: string;
    project?: { title: string };
    status?: { name: string; severity?: string | null };
    priority?: { name: string; severity?: string | null };
    type?: { name: string; severity?: string | null };
    start_date?: string;
    due_date?: string;
    progress?: number;
    description?: string;
    is_archived?: boolean;
}

interface Props {
    tasks: Task[];
}

const props = defineProps<Props>();

// ⬅️ emits
const emit = defineEmits(['add', 'edit', 'delete']);

// ⬅️ Toast instance
const toast = useToast();

const buildTree = (tasks: Task[]) => {
    const map: Record<number, any> = {};

    tasks.forEach((task) => {
        map[task.id] = {
            key: task.id,
            data: {
                id: task.id,
                title: task.title,
                project: task.project?.title ?? '-',
                status: {
                    name: task.status?.name ?? '-',
                    severity: task.status?.severity ?? null,
                },
                priority: {
                    name: task.priority?.name ?? '-',
                    severity: task.priority?.severity ?? null,
                },
                type: {
                    name: task.type?.name ?? '-',
                    severity: task.type?.severity ?? null,
                },
                start_date: task.start_date,
                due_date: task.due_date,
                progress: task.progress ?? 0,
                description: task.description ?? '',
                is_archived: task.is_archived ? 'Yes' : 'No',
            },
            children: [],
        };
    });

    const roots: any[] = [];

    tasks.forEach((task) => {
        if (task.parent_id && map[task.parent_id]) {
            map[task.parent_id].children.push(map[task.id]);
        } else {
            roots.push(map[task.id]);
        }
    });

    return roots;
};

const treeNodes = computed(() => buildTree(props.tasks));

const destroy = (id: number) => {
    emit('delete', id);

    router.delete(route('task.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Deleted',
                detail: 'Task berhasil dihapus',
                life: 2000,
            });
        },
    });
};

const truncateHtmlPreserve = (html: string, maxLength = 20) => {
    if (!html) return '';

    const div = document.createElement('div');
    div.innerHTML = html;

    let totalLength = 0;

    const truncateNode = (node: Node): Node | null => {
        if (totalLength >= maxLength) return null;

        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.nodeValue || '';
            if (totalLength + text.length <= maxLength) {
                totalLength += text.length;
                return document.createTextNode(text);
            } else {
                const truncated = text.substring(0, maxLength - totalLength) + '...';
                totalLength = maxLength;
                return document.createTextNode(truncated);
            }
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
            const clone = node.cloneNode(false);
            for (const child of Array.from(node.childNodes)) {
                const truncatedChild = truncateNode(child);
                if (truncatedChild) clone.appendChild(truncatedChild);
                if (totalLength >= maxLength) break;
            }
            return clone;
        }

        return null;
    };

    const result = truncateNode(div);
    return result ? result.innerHTML : '';
};

const handleAdd = () => {
    emit('add');
};

const handleEdit = (id: number) => {
    emit('edit', id);
};
</script>

<template>
    <div class="mb-3 flex items-center justify-end">
        <Button icon="pi pi-plus" label="Add Task" severity="success" @click="handleAdd" />
    </div>
    <div class="card">
        <TreeTable :value="treeNodes" tableStyle="min-width: 60rem" scrollable scrollHeight="flex">
            <Column field="title" header="Title" expander></Column>
            <Column field="project" header="Project"></Column>
            <Column field="description" header="Description">
                <template #body="{ node }">
                    <div v-html="truncateHtmlPreserve(node.data.description, 20)"></div>
                </template>
            </Column>

            <Column field="status" header="Status">
                <template #body="{ node }">
                    <Tag :value="node.data.status.name" :severity="node.data.status.severity" />
                </template>
            </Column>

            <Column field="priority" header="Priority">
                <template #body="{ node }">
                    <Tag :value="node.data.priority.name" :severity="node.data.priority.severity" />
                </template>
            </Column>

            <Column field="type" header="Type">
                <template #body="{ node }">
                    <Tag :value="node.data.type.name" :severity="node.data.type.severity" />
                </template>
            </Column>

            <Column field="start_date" header="Start"></Column>
            <Column field="due_date" header="Due"></Column>

            <Column header="Progress">
                <template #body="{ node }">
                    <div class="w-full">
                        <div class="h-2 rounded bg-gray-200">
                            <div class="h-2 rounded bg-blue-500" :style="{ width: node.data.progress + '%' }"></div>
                        </div>
                        <div class="mt-1 text-xs text-gray-600">{{ node.data.progress }}%</div>
                    </div>
                </template>
            </Column>
            <Column field="is_archived" header="Archived">
                <template #body="{ node }">
                    <Tag :value="node.data.is_archived" :severity="node.data.is_archived === 'Yes' ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column header="Action" style="width: 150px">
                <template #body="{ node }">
                    <div class="flex gap-2">
                        <Button icon="pi pi-pencil" severity="warning" text @click="handleEdit(node.data.id)" />

                        <Button icon="pi pi-trash" severity="danger" text @click="destroy(node.data.id)" />
                    </div>
                </template>
            </Column>
        </TreeTable>
    </div>
    <Toast />
</template>
