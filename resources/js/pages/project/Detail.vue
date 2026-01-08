<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import { ProjectMember, Tag as TagData, Task, TaskPriority, TaskStatus, TaskType } from '.';
import MemberEditForm from './member/EditFormTemp.vue';
import MemberAddForm from './member/Form.vue';
import MembersTable from './member/Table.vue';
import TaskForm from './task/Form.vue';
import TaskTable from './task/Table.vue';

import 'emoji-mart-vue-fast/css/emoji-mart.css';

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
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    tags: TagData[];
    assignableUsers: { id: string; name: string; email?: string }[];

    isPM: boolean;
    isAdmin: boolean;
    isMember: boolean;
    canManageMembers: boolean;
}

const props = defineProps<Props>();

const toast = useToast();

const visibleAdd = ref(false);
const visibleEdit = ref(false);
const visibleTaskAdd = ref(false);
const selectedMember = ref<ProjectMember | null>(null);
const selectedTask = ref<Task | null>(null);

const parentTaskId = ref<string | null>(null);

let emojiIndex = new EmojiIndex(emojiData);

const openAdd = () => (visibleAdd.value = true);
const openEdit = (member: ProjectMember) => {
    selectedMember.value = member;
    visibleEdit.value = true;
};

const openTaskAdd = (parentId: string | null) => {
    if (!props.isMember) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member to create tasks',
            life: 3000,
        });
        return;
    }
    parentTaskId.value = parentId;
    visibleTaskAdd.value = true;
};

const openTaskEdit = (task: Task) => {
    if (!props.isMember) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member to edit tasks',
            life: 3000,
        });
        return;
    }
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

const goBack = () => {
    router.visit(route('project.index'));
};
const formattedMembers = computed(() => {
    return props.assignableUsers.map(
        (user) =>
            ({
                id: user.id,
                user: {
                    id: user.id,
                    name: user.name,
                    email: user.email || '',
                },
                role: { id: '', name: '' },
            }) as ProjectMember,
    );
});
const taskDialogHeader = computed(() => {
    if (selectedTask.value) {
        return 'Edit Task';
    }
    return parentTaskId.value ? 'Create Subtask' : 'Create Task';
});
</script>

<template>
    <Head :title="`Project Detail - ${props.project.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-surface-200 pb-4 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <Button
                        icon="pi pi-arrow-left"
                        text
                        rounded
                        severity="secondary"
                        @click="router.get(route('project.index'))"
                        class="hover:bg-surface-100 dark:hover:bg-surface-800"
                    />
                    <Emoji
                        v-if="props.project?.emoji.startsWith(':')"
                        :data="emojiIndex"
                        :emoji="props.project.emoji"
                        set="google"
                        :size="36"
                    ></Emoji>
                    <span v-else class="text-4xl">{{ props.project?.emoji }}</span>
                    <div>
                        <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">
                            {{ props.project.title }}
                        </h1>
                        <p class="text-sm text-surface-600 dark:text-surface-400">
                            Software project
                            <span v-if="isMember" class="ml-2 text-green-600 dark:text-green-400"> <i class="pi pi-check-circle"></i> Member </span>
                            <span v-else class="ml-2 text-gray-500 dark:text-gray-400"> <i class="pi pi-eye"></i> Viewer </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <AvatarGroup v-if="props.members.length > 0">
                        <Avatar
                            v-for="member in props.members.slice(0, 3)"
                            :key="member.id"
                            :label="member.user.name.charAt(0).toUpperCase()"
                            size="normal"
                            shape="circle"
                            class="border-2 border-white dark:border-surface-900"
                        />
                        <Avatar
                            v-if="props.members.length > 3"
                            :label="`+${props.members.length - 3}`"
                            size="normal"
                            shape="circle"
                            class="border-2 border-white dark:border-surface-900"
                        />
                    </AvatarGroup>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Status</span>
                            <Tag
                                :value="props.project.status?.name || 'In Progress'"
                                :severity="props.project.status?.severity || 'info'"
                                class="w-fit"
                            />
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Priority</span>
                            <Tag
                                :value="props.project.priority?.name || 'Medium'"
                                :severity="props.project.priority?.severity || 'warning'"
                                class="w-fit"
                            />
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Timeline</span>
                            <div class="text-sm text-surface-700 dark:text-surface-300">
                                {{ moment(props.project.start_date).format('MMM DD') }} -
                                {{ moment(props.project.due_date).format('MMM DD, YYYY') }}
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Progress</span>
                            <div class="flex items-center gap-2">
                                <ProgressBar :value="props.project.progress" class="flex-1" :showValue="false" />
                                <span class="text-sm font-semibold text-surface-700 dark:text-surface-300"> {{ props.project.progress }}% </span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            <Card class="shadow-sm">
                <template #content>
                    <Tabs value="Board">
                        <TabList>
                            <Tab value="Board">Board</Tab>
                            <Tab value="Details">Details</Tab>
                            <Tab value="Team">Team</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="Board">
                                <div class="py-4">
                                    <TaskTable
                                        :projectId="props.project.id"
                                        :tasks="props.tasks"
                                        @add="openTaskAdd"
                                        @edit="openTaskEdit"
                                        :isPM="props.isPM"
                                        :isMember="props.isMember"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="Details">
                                <div class="grid grid-cols-1 gap-8 py-4 lg:grid-cols-3">
                                    <div class="lg:col-span-2">
                                        <h3 class="mb-3 text-sm font-semibold uppercase text-surface-500 dark:text-surface-400">Description</h3>
                                        <div
                                            class="prose dark:prose-invert max-w-none break-words text-surface-700 dark:text-surface-300"
                                            v-html="props.project.description || '<p class=\'text-surface-500 italic\'>No description provided</p>'"
                                        />
                                    </div>

                                    <!-- Sidebar Info -->
                                    <div class="flex flex-col gap-6">
                                        <div>
                                            <h3 class="mb-3 text-sm font-semibold uppercase text-surface-500 dark:text-surface-400">Details</h3>
                                            <div class="flex flex-col gap-3">
                                                <div class="flex items-start justify-between">
                                                    <span class="text-sm text-surface-600 dark:text-surface-400">Created</span>
                                                    <span class="text-sm font-medium text-surface-800 dark:text-surface-200">
                                                        {{ moment(props.project.created_at).format('MMM DD, YYYY') }}
                                                    </span>
                                                </div>
                                                <div class="flex items-start justify-between">
                                                    <span class="text-sm text-surface-600 dark:text-surface-400">Updated</span>
                                                    <span class="text-sm font-medium text-surface-800 dark:text-surface-200">
                                                        {{ moment(props.project.updated_at).fromNow() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="Team">
                                <div class="py-4">
                                    <MembersTable
                                        :projectId="props.project.id"
                                        :members="props.members"
                                        :roles="props.roles"
                                        :users="props.users"
                                        :isPM="props.isPM"
                                        :isAdmin="props.isAdmin"
                                        @add="openAdd"
                                        @edit="openEdit"
                                    />
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
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
                :users="props.users"
                @close="visibleEdit = false"
                @saved="onSaved"
            />
        </Dialog>

        <Dialog v-model:visible="visibleTaskAdd" :header="taskDialogHeader" @hide="onDialogClosed" modal class="w-[600px]">
            <TaskForm
                :projectId="props.project.id"
                :parentId="parentTaskId"
                :task="selectedTask"
                :taskTypes="props.taskTypes"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :tags="props.tags"
                :editTask="selectedTask"
                :members="formattedMembers"
                :isMember="props.isMember"
                @close="
                    visibleTaskAdd = false;
                    selectedTask = null;
                    parentTaskId = null;
                "
                @saved="router.reload({ only: ['tasks', 'project'] })"
            />
        </Dialog>

        <Toast />
    </AppLayout>
</template>
