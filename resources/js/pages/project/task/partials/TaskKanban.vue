<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { onMounted, ref, watch } from 'vue';
import { DraggableEvent, VueDraggable } from 'vue-draggable-plus';
import { Task, TaskStatusOption } from '../type';

interface Props {
    tasks: Task[];
    statuses: TaskStatusOption[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    statusUpdate: [taskId: string, newStatusId: string];
}>();

const grouped = ref<Record<string, Task[]>>({});

const draggingItem = ref(false);

const cardClasses: Record<string, string> = {
    primary: 'bg-primary-100/50',
    secondary: 'bg-gray-100/50',
    success: 'bg-green-100/50',
    info: 'bg-blue-100/50',
    warn: 'bg-orange-100/50',
    danger: 'bg-red-100/50',
    contrast: 'bg-gray-100/50',
    default: 'bg-gray-100/50',
};

const getGroupedTasks = () => {
    const groupedTasks: Record<string, Task[]> = {};

    for (const status of props.statuses) {
        groupedTasks[status.id] = [];
    }

    props.tasks.forEach((task) => {
        const status = props.statuses.find((s) => s.id === task.status?.id);

        if (!status) return;

        const group = status.id;

        if (!groupedTasks[group]) {
            groupedTasks[group] = [];
        }

        groupedTasks[group].push(task);
    });

    return groupedTasks;
};

const getSeverityByStatus = (id: string) => {
    const status = props.statuses.find((s) => s.id === id);

    if (status) return status.severity;

    return 'primary';
};

const getStatusName = (id: string) => {
    const status = props.statuses.find((s) => s.id === id);

    if (status) return status.name;

    return 'Unknown';
};

const onGroupChange = async (task: Task, newStatusId: string) => {
    const response = await axios.post(route('task.status.update', task.id), { _method: 'PUT', status_id: newStatusId });
    if (response.status === 200) {
        emit('statusUpdate', task.id, newStatusId);
    }
};

onMounted(() => {
    grouped.value = getGroupedTasks();
});

watch(
    () => props.tasks,
    () => {
        if (!draggingItem.value) {
            grouped.value = getGroupedTasks();
        }
    },
    { deep: true },
);
</script>

<template>
    <div class="overflow-x-auto py-3">
        <div class="flex flex-nowrap gap-4">
            <div v-for="(group, index) in grouped" :key="index" class="h-fit min-h-48 rounded-lg bg-gray-100/50 p-4">
                <Tag icon="pi pi-circle-fill" :value="getStatusName(index)" :severity="getSeverityByStatus(index)" class="mb-4" />

                <VueDraggable
                    class="flex h-full w-[300px] flex-col gap-2 overflow-hidden"
                    v-model="grouped[index]"
                    :animation="150"
                    ghostClass="ghost"
                    group="people"
                    @add="(e: DraggableEvent<Task>) => onGroupChange(e.data, index)"
                    @start="draggingItem = true"
                    @end="draggingItem = false"
                >
                    <Card
                        v-for="item in group"
                        :key="item.id"
                        class="!rounded-lg border !shadow-none"
                        :class="[draggingItem ? 'cursor-grabbing' : 'cursor-pointer']"
                        @click="router.get(route('task.show', { encoded: item.id }))"
                    >
                        <template #subtitle>
                            <Link :href="route('project.show', item.project?.id)" @click.stop>
                                <span class="hover:underline">
                                    {{ item.project?.title }}
                                </span>
                            </Link>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="break-all">
                                    <Link :href="route('task.show', item.id)" @click.stop>
                                    <span class="hover:underline">
                                        {{ item.title }}
                                    </span>
                                    </Link>
                                </div>
                                <div class="flex justify-between gap-1">
                                    <Tag
                                        icon="pi pi-flag"
                                        :value="moment(item.due_date).fromNow()"
                                        class="!border !bg-transparent text-xs"
                                        style="color: var(--text-color)"
                                    />

                                    <div class="flex gap-1">
                                        <Tag :value="item.type?.name" :severity="item.type?.severity" class="text-xs" />
                                        <Tag :value="item.priority?.name" :severity="item.priority?.severity" class="text-xs" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </VueDraggable>
            </div>
        </div>
    </div>
</template>
