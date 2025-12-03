<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import { computed, onMounted, ref, watch } from 'vue';

interface TaskType {
    id: string;
    name: string;
    severity?: string;
}

interface Task {
    id: string;
    title: string;
    due_date?: string;
    project?: { id: string; title: string };
    status?: { id: string; name: string; severity?: string };
    priority?: { id: string; name: string; severity?: string };
    type?: TaskType;
    is_assigned?: boolean;
    is_created_by_me?: boolean;
}

interface Props {
    tasks: Task[];
    totalAssigned?: number;
}

const CurrentUser = usePage().props.auth.user;

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    totalAssigned: 0,
});

const tasksData = ref<Task[]>([]);
const filteredTasks = ref<Task[]>([]);
const totalAssigned = ref<number>(props.totalAssigned || 0);

// Pagination
const rows = ref<number>(6);
const first = ref<number>(0);

// Search
const searchQuery = ref<string>('');

// Format tanggal tanpa date-fns
const formatDueDate = (date?: string) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

onMounted(() => {
    tasksData.value = JSON.parse(JSON.stringify(props.tasks));
    filteredTasks.value = tasksData.value;
});

// Watch search input
watch(searchQuery, (val) => {
    if (!val) {
        filteredTasks.value = tasksData.value;
    } else {
        filteredTasks.value = tasksData.value.filter((task) => task.title.toLowerCase().includes(val.toLowerCase()));
    }
});

const statusSummary = computed(() => {
    const summary: Record<string, { count: number; severity?: string }> = {};
    tasksData.value.forEach((task) => {
        if (task.status?.name) {
            if (!summary[task.status.name]) {
                summary[task.status.name] = { count: 1, severity: task.status.severity };
            } else {
                summary[task.status.name].count += 1;
            }
        }
    });
    return summary;
});
</script>

<template>
    <Head title="Tasks" />
    <AppLayout>
        <div class="p-2">
            <Heading title="My Task List" description="Stay focused and manage your tasks beautifully" />
            <div class="flex flex-col gap-6 rounded-2xl bg-white/80 p-6 shadow-md backdrop-blur-md dark:bg-gray-900/70">
                <!-- Current User & Total Assigned + Search -->
                <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Task, {{ CurrentUser?.name || 'User' }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total tasks assigned to you: {{ totalAssigned }}</p>
                    </div>
                    <InputText v-model="searchQuery" placeholder="Search tasks..." class="w-full md:w-64" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <Tag
                        v-for="(data, status) in statusSummary"
                        :key="status"
                        :value="`${status}: ${data.count}`"
                        :severity="data.severity"
                        rounded
                        class="px-3 py-1"
                    />
                </div>

                <!-- CARD LIST -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <template v-if="filteredTasks.length > 0">
                        <div
                            v-for="task in filteredTasks.slice(first, first + rows)"
                            :key="task.id"
                            class="group transform rounded-2xl border border-gray-100 bg-white/80 p-5 shadow-lg backdrop-blur-md transition hover:-translate-y-1 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-900/70"
                        >
                            <!-- Header -->
                            <div class="flex items-start justify-between">
                                <h3 class="text-lg font-bold leading-tight text-gray-900 dark:text-white">
                                    {{ task.title }}
                                </h3>
                                <Tag
                                    v-if="task.status"
                                    :value="task.status.name"
                                    :severity="task.status.severity"
                                    rounded
                                    class="px-2 py-1 text-xs"
                                />
                            </div>

                            <!-- Project -->
                            <div class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <i class="pi pi-folder text-indigo-500"></i>
                                <Link
                                    v-if="task.project"
                                    :href="route('project.show', { encoded: task.project.id })"
                                    class="hover:text-indigo-600 hover:underline"
                                >
                                    {{ task.project.title }}
                                </Link>
                            </div>

                            <!-- Due Date -->
                            <div class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <i class="pi pi-calendar text-green-500"></i>
                                <span>Due: {{ formatDueDate(task.due_date) }}</span>
                            </div>

                            <!-- Type & Priority -->
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" rounded />
                                <Tag v-if="task.priority" :value="task.priority.name" :severity="task.priority.severity" rounded />
                            </div>

                            <!-- Assigned / Created Tags -->
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Tag v-if="task.is_assigned" value="Assigned to me" icon="pi pi-user" severity="primary" rounded />
                                <Tag v-if="task.is_created_by_me" value="Created by me" icon="pi pi-check-circle" severity="success" rounded />
                            </div>

                            <!-- View Button -->
                            <div class="mt-4 flex justify-end">
                                <Button
                                    label="View"
                                    icon="pi pi-eye"
                                    class="p-button-sm p-button-outlined p-button-primary"
                                    @click="() => router.get(route('task.show', { encoded: task.id }))"
                                />
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="col-span-full py-10 text-center text-gray-500 dark:text-gray-400">No tasks found for "{{ searchQuery }}"</div>
                    </template>
                </div>

                <!-- Paginator -->
                <div v-if="filteredTasks.length > 0" class="flex justify-center">
                    <Paginator :first="first" :rows="rows" :totalRecords="filteredTasks.length" @page="(e) => (first = e.first)" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
