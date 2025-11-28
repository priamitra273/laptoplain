<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import moment from 'moment';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import { ProjectMember, Task, TaskPriority, TaskStatus, TaskType } from '.';
import MemberEditForm from './member/EditFormTemp.vue';
import MemberAddForm from './member/Form.vue';
import MembersTable from './member/Table.vue';
import TaskForm from './task/Form.vue';
import TaskTable from './task/Table.vue';

interface Props {
    project: {
        id: string;
        title: string;
        description?: string;
        emoji: string;
        progress: number;
        start_date?: string;
        due_date?: string;
        status?: { id: string; name: string; severity?: string };
        priority?: { id: string; name: string; severity?: string };
        created_at?: string;
        updated_at?: string;
    };
    members: ProjectMember[];
    roles: { id: string; name: string }[];
    users: { id: string; name: string }[];

    tasks: Task[];
    taskTypes?: TaskType[];
    taskStatuses?: TaskStatus[];
    taskPriorities?: TaskPriority[];

    isPM: boolean;
}

const props = defineProps<Props>();

const visibleAdd = ref(false);
const visibleEdit = ref(false);
const visibleTaskAdd = ref(false);
const selectedMember = ref<ProjectMember | null>(null);
const selectedTask = ref<Task | null>(null);

const parentTaskId = ref<string | null>(null);

const openAdd = () => (visibleAdd.value = true);
const openEdit = (member: ProjectMember) => {
    selectedMember.value = member;
    visibleEdit.value = true;
};
const openTaskAdd = (parentId: string | null) => {
    parentTaskId.value = parentId;
    visibleTaskAdd.value = true;
};
const openTaskEdit = (task: Task) => {
    selectedTask.value = task;
    visibleTaskAdd.value = true;
};

const onSaved = () => {
    visibleAdd.value = false;
    visibleEdit.value = false;
    router.reload({ only: ['members', 'users'] });
};

const onDialogClosed = () => {
    visibleTaskAdd.value = false;
    selectedTask.value = null;
    parentTaskId.value = null;
};

const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('DD MMMM YYYY') : '-';
};

// Navigasi kembali
const goBack = () => {
    router.visit(route('project.index'));
};
</script>

<template>
    <Head :title="`Project Detail - ${props.project.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Detail" description="Detail information about this project" />

            <Card class="flex flex-row shadow-md">
                <template #title>
                    <div class="flex flex-row justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ props.project?.emoji }}</span>
                            <h2 class="text-xl font-semibold">{{ props.project.title }}</h2>
                        </div>
                        <Button label="Back to Projects" icon="pi pi-arrow-left" severity="secondary" @click="router.get(route('project.index'))" />
                    </div>
                </template>
            </Card>

            <!-- Card Detail -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="flex w-full flex-col gap-8 p-8 xl:flex-row">
                        <!-- LEFT CONTENT -->
                        <div class="flex flex-col xl:w-2/5">
                            <div
                                class="prose dark:prose-invert max-w-none overflow-hidden break-words"
                                v-html="props.project.description || '<p><em>No description</em></p>'"
                            />

                            <Divider />

                            <div class="mt-4 grid grid-cols-1 gap-16 md:grid-cols-2">
                                <div class="space-y-3">
                                    <div>
                                        <p class="mb-1 font-semibold">Status</p>
                                        <Tag :value="props.project.status?.name || '-'" :severity="props.project.status?.severity" />
                                    </div>

                                    <div>
                                        <p class="mb-1 font-semibold">Priority</p>
                                        <Tag :value="props.project.priority?.name || '-'" :severity="props.project.priority?.severity" />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <p class="mb-1 font-semibold">Start Date</p>
                                        <p>{{ moment(props.project.start_date).format('YYYY-MM-DD') }}</p>
                                    </div>

                                    <div>
                                        <p class="mb-1 font-semibold">Due Date</p>
                                        <p>{{ moment(props.project.due_date).format('YYYY-MM-DD') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="mb-1 font-semibold">Progress</p>
                                <Tag :value="`${props.project.progress}%`" severity="success" class="px-3 py-1 text-base" />
                            </div>
                        </div>

                        <!-- RIGHT CONTENT -->
                        <MembersTable
                            class="xl:w-3/5"
                            :projectId="props.project.id"
                            :members="props.members"
                            :roles="props.roles"
                            :users="props.users"
                            @add="openAdd"
                            @edit="openEdit"
                        />
                    </div>

                    <!-- TASK TABLE: FULL WIDTH -->
                    <div class="mx-8 rounded p-8 shadow-lg">
                        <TaskTable
                            class="w-full"
                            :projectId="props.project.id"
                            :tasks="props.tasks"
                            @add="openTaskAdd"
                            @edit="openTaskEdit"
                            :isPM="props.isPM"
                        />
                    </div>
                </template>

                <template #footer>
                    <div class="flex justify-between">
                        <!-- <Button label="Back to Projects" icon="pi pi-arrow-left" severity="secondary"
                            @click="router.get(route('project.index'))" /> -->
                    </div>
                </template>
            </Card>
        </div>

        <Dialog v-model:visible="visibleAdd" header="Add Member" modal class="w-96">
            <MemberAddForm :projectId="props.project.id" :users="props.users" :roles="props.roles" @close="visibleAdd = false" @saved="onSaved" />
        </Dialog>

        <Dialog v-model:visible="visibleEdit" header="Edit Member" modal class="w-96">
            <MemberEditForm
                :projectId="props.project.id"
                :member="selectedMember as ProjectMember"
                :roles="props.roles"
                @close="visibleEdit = false"
                @saved="onSaved"
            />
        </Dialog>

        <Dialog v-model:visible="visibleTaskAdd" :header="selectedTask ? 'Edit Task' : 'Add Task'" @hide="onDialogClosed" modal class="w-[600px]">
            <TaskForm
                :projectId="props.project.id"
                :parentId="parentTaskId"
                :task="selectedTask"
                :taskTypes="props.taskTypes"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :editTask="selectedTask"
                :members="props.members"
                @close="
                    visibleTaskAdd = false;
                    selectedTask = null;
                    parentTaskId = null;
                "
                @saved="router.reload({ only: ['tasks', 'project'] })"
            />
        </Dialog>
        <Dialog v-model:visible="visibleEdit" header="Edit Member" modal class="w-96">
            <MemberEditForm
                :projectId="props.project.id"
                :member="selectedMember as ProjectMember"
                :roles="props.roles"
                :users="props.users"
                @close="visibleEdit = false"
                @saved="onSaved"
            />
        </Dialog>
    </AppLayout>
</template>
