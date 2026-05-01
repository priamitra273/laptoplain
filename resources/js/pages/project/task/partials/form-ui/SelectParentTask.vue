<script setup lang="ts">
import Label from '@/components/Label.vue';
import { Task } from '@/pages/project';
import type { TreeNode } from 'primevue/treenode';
import { computed } from 'vue';

interface Props {
    task: Task | null;
    tasks: Task[];
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<Record<string, boolean> | null>('modelValue');

const parentTreeOptions = computed<TreeNode[]>(() => {
    const excludeIds = new Set<string>();

    if (props.task) {
        excludeIds.add(props.task.id);
        collectDescendants(props.task).forEach((id) => excludeIds.add(id));
    }

    const build = (tasks: Task[]): TreeNode[] => {
        return tasks
            .filter((t) => !excludeIds.has(t.id))
            .map((t) => ({
                key: t.id,
                label: t.title,
                children: t.sub_task_recursive ? build(t.sub_task_recursive) : undefined,
            }));
    };

    return build(props.tasks);
});

function collectDescendants(task: Task): string[] {
    const ids: string[] = [];

    const walk = (node: Task) => {
        if (!node.sub_task_recursive) return;
        for (const child of node.sub_task_recursive) {
            ids.push(child.id);
            walk(child);
        }
    };

    walk(task);
    return ids;
}
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Label value="Parent Task" icon="Link" />

        <div class="col-span-3">
            <TreeSelect
                v-model="modelValue"
                :options="parentTreeOptions"
                placeholder="Empty"
                filter
                filterMode="lenient"
                class="min-w-52 !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                panelClass="!min-w-96"
                :disabled="props.disabled"
                pt:dropdown:class="!w-0"
            />
            <small v-if="props.error" class="p-error text-red-500">{{ props.error }}</small>
        </div>
    </div>
</template>

<style lang="css" scoped>
.p-treeselect-open {
    @apply bg-surface-100 dark:bg-surface-900;
}
</style>
