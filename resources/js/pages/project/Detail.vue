<script setup lang="ts">
import ProjectGanttChart from '@/components/ProjectGanttChart.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { MemberWithAvatar, ProjectDetailProps, ProjectMember, TabListItem, Task } from './index';
import MemberEditForm from './member/EditFormTemp.vue';
import MemberAddForm from './member/Form.vue';
import MembersTable from './member/Table.vue';
import ProjectDetailsTab from './partials/ProjectDetailsTab.vue';
import ProjectHeader from './partials/ProjectHeader.vue';
import ProjectStats from './partials/ProjectStats.vue';
import BacklogBoard from './task/Backlog.vue';
import TaskForm from './task/Form.vue';
import KanbanBoard from './task/partials/TaskKanbanBoard.vue';
import TaskTable from './task/Table.vue';

const props = defineProps<ProjectDetailProps>();

const toast = useToast();
const page = usePage();

const isDeveloper = computed(() => page.props.auth?.role?.startsWith('developer-'));
const authUser = computed(() => page.props.auth?.user);
const isMember = computed(() => {
    if (!authUser.value) return false;
    return props.project.project_members.some((member) => member.user.id === authUser.value.id);
});
const isOwner = computed(() => {
    if (!authUser.value) return false;
    return props.project.project_members.some((member) => member.user.id === authUser.value.id && member.role.name === 'Owner');
});

const hasPermission = (): boolean => {
    const role = page.props.auth.role;
    return role ? role.startsWith('super-admin-') || role.startsWith('admin-') : false;
};

const canEdit = computed(() => (isOwner.value || hasPermission()) && !isDeveloper.value);

// Dialog State
const visibleAdd = ref(false);
const visibleEdit = ref(false);
const visibleTaskAdd = ref(false);
const selectedMember = ref<ProjectMember | null>(null);
const selectedTask = ref<Task | null>(null);
const parentTaskId = ref<string | null>(null);
const isBacklogCreate = ref(false);
const isAddParentCreate = ref(false);
const selectedSprintId = ref<string | null>(null);
const activeSprintTaskIds = ref<string[]>([]);

const tabListItems: TabListItem[] = [
    { label: 'Kanban', icon: 'pi pi-th-large' },
    { label: 'List', icon: 'pi pi-list' },
    { label: 'Backlog', icon: 'pi pi-inbox' },
    { label: 'Details', icon: 'pi pi-info-circle' },
    { label: 'Team', icon: 'pi pi-users' },
    { label: 'Timeline', icon: 'pi pi-chart-bar' },
];

const activeSprintTasks = computed(() =>
    activeSprintTaskIds.value.length > 0 ? props.tasks.filter((t) => activeSprintTaskIds.value.includes(String(t.id))) : [],
);

const updateProject = (newValue: any, field: string) => {
    if (!canEdit.value) {
        toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You do not have permission to edit this project', life: 3000 });
        return;
    }

    let payload: any = {
        title: props.project.title,
        description: props.project.description || '',
        emoji: props.project.emoji,
        start_date: props.project.start_date,
        due_date: props.project.due_date,
        status_id: props.project.status_id || props.project.status?.id,
        priority_id: props.project.priority_id || props.project.priority?.id,
    };

    if (field === 'start_date' || field === 'due_date') payload[field] = moment(newValue).format('YYYY-MM-DD');
    else payload[field] = newValue;

    if (payload.start_date && typeof payload.start_date !== 'string') payload.start_date = moment(payload.start_date).format('YYYY-MM-DD');
    if (payload.due_date && typeof payload.due_date !== 'string') payload.due_date = moment(payload.due_date).format('YYYY-MM-DD');

    router.put(route('project.update', props.project.id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Success', detail: 'Project updated successfully', life: 3000 });
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Error', detail: errors[Object.keys(errors)[0]] || 'Failed to update project', life: 3000 });
        },
    });
};

const openAdd = () => (visibleAdd.value = true);
const openEdit = (member: ProjectMember) => {
    selectedMember.value = member;
    visibleEdit.value = true;
};

const openTaskAdd = (parentId: string | null, _statusId?: string, source?: string, sprintId?: string | null) => {
    if (!isMember.value && !hasPermission()) {
        toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You must be a project member to create tasks', life: 3000 });
        return;
    }
    selectedTask.value = null;
    isBacklogCreate.value = source === 'backlog' || source === 'backlog-add-parent';
    isAddParentCreate.value = source === 'backlog-add-parent' || source === 'sprint-add-parent';
    selectedSprintId.value = sprintId ?? null;
    parentTaskId.value = parentId;
    visibleTaskAdd.value = true;
};

const openTaskEdit = (task: Task, parentId: string | null, source?: string) => {
    if (!isMember.value && !hasPermission()) {
        toast.add({ severity: 'warn', summary: 'Access Denied', detail: 'You must be a project member to edit tasks', life: 3000 });
        return;
    }
    isBacklogCreate.value = source === 'backlog';
    isAddParentCreate.value = false;
    selectedSprintId.value = null;
    parentTaskId.value = parentId;
    selectedTask.value = task;
    visibleTaskAdd.value = true;
};

const onSaved = () => {
    visibleAdd.value = false;
    visibleEdit.value = false;
};
const onTaskSaved = () => {
    router.reload({ only: ['tasks', 'sprints', 'backlog'] });
};
const onDialogClosed = () => {
    visibleTaskAdd.value = false;
    selectedTask.value = null;
    parentTaskId.value = null;
    isBacklogCreate.value = false;
    isAddParentCreate.value = false;
    selectedSprintId.value = null;
};

const formattedMembers = computed(() =>
    props.assignableUsers.map(
        (user) =>
            ({
                id: user.id,
                user: { id: user.id, name: user.name, email: user.email || '', avatar_url: user.avatar_url },
                role: { id: '', name: '' },
            }) as MemberWithAvatar,
    ),
);

const taskDialogHeader = computed(() => {
    if (selectedTask.value) return 'Edit Task';
    return parentTaskId.value ? 'Create Subtask' : 'Create Task';
});

const onKanbanStatusUpdate = () => {
    router.reload({ only: ['tasks'] });
};
</script>

<template>
    <Head :title="`Project Detail - ${props.project.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-4">
            <ProjectHeader :project="project" :members="members" :canEdit="canEdit" :isMember="isMember" @update="updateProject" />

            <ProjectStats :project="project" :statuses="statuses || []" :priorities="priorities || []" :canEdit="canEdit" @update="updateProject" />

            <!-- Main Tabs -->
            <Card class="shadow-sm">
                <template #content>
                    <Tabs value="Kanban">
                        <TabList scrollable>
                            <Tab v-for="tab in tabListItems" :key="tab.label" :value="tab.label" v-tooltip.bottom="tab.label" class="!px-3 sm:!px-4">
                                <i :class="tab.icon" class="sm:mr-2"></i>
                                <span class="hidden sm:inline">{{ tab.label }}</span>
                            </Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel value="Kanban">
                                <div class="py-4">
                                    <KanbanBoard
                                        :projectId="project.id"
                                        :tasks="activeSprintTasks"
                                        :statuses="taskStatuses"
                                        :taskStatuses="taskStatuses"
                                        :taskPriorities="taskPriorities"
                                        :taskTypes="taskTypes"
                                        :isMember="isMember"
                                        :hasPermission="isOwner || hasPermission()"
                                        :assignableUsers="assignableUsers"
                                        @statusUpdate="onKanbanStatusUpdate"
                                        @add="openTaskAdd"
                                        @edit="openTaskEdit"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="List">
                                <div class="py-4">
                                    <TaskTable
                                        :projectId="project.id"
                                        :tasks="tasks"
                                        :isMember="isMember"
                                        :has-permission="isOwner || hasPermission()"
                                        :taskStatuses="taskStatuses"
                                        :taskPriorities="taskPriorities"
                                        :taskTypes="taskTypes"
                                        :taskCategories="taskCategories"
                                        :isDeveloper="isDeveloper"
                                        @add="openTaskAdd"
                                        @edit="openTaskEdit"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="Backlog">
                                <div class="py-4">
                                    <BacklogBoard
                                        :project-id="project.id"
                                        :sprints="sprints"
                                        :backlog="backlog"
                                        :epics="epics"
                                        :task-statuses="taskStatuses"
                                        :task-priorities="taskPriorities"
                                        :task-types="taskTypes"
                                        :task-categories="taskCategories"
                                        :is-member="isMember"
                                        :has-permission="isOwner || hasPermission()"
                                        :assignable-users="assignableUsers"
                                        @add="openTaskAdd"
                                        @addBacklog="() => openTaskAdd(null, undefined, 'backlog')"
                                        @edit="(task, parentId) => openTaskEdit(task, parentId, 'backlog')"
                                        @activeSprintTaskIds="(ids) => (activeSprintTaskIds = ids)"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="Details">
                                <ProjectDetailsTab :project="project" :canEdit="canEdit" @update="updateProject" />
                            </TabPanel>

                            <TabPanel value="Team">
                                <div class="py-4">
                                    <MembersTable
                                        :projectId="props.project.id"
                                        :members="props.members"
                                        :roles="props.roles"
                                        :users="props.users"
                                        :hasPermission="(isOwner || hasPermission()) && !isDeveloper"
                                        @add="openAdd"
                                        @edit="openEdit"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="Timeline">
                                <div class="py-4">
                                    <ProjectGanttChart :tasks="props.tasks" />
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>
        </div>

        <!-- Dialogs -->
        <Dialog v-model:visible="visibleAdd" header="Add Member" modal class="w-96">
            <MemberAddForm :projectId="props.project.id" :users="props.users" :roles="props.roles" @close="visibleAdd = false" @saved="onSaved" />
        </Dialog>

        <Dialog v-model:visible="visibleEdit" header="Edit Member" modal class="w-96">
            <MemberEditForm
                v-if="selectedMember"
                :projectId="props.project.id"
                :member="selectedMember"
                :roles="props.roles"
                :users="props.users"
                @close="visibleEdit = false"
                @saved="onSaved"
            />
        </Dialog>

        <Dialog
            v-model:visible="visibleTaskAdd"
            :header="taskDialogHeader"
            modal
            scrollable
            maximizable
            @hide="onDialogClosed"
            :style="{ width: '70rem' }"
            :contentStyle="{ maxHeight: '75vh' }"
            :breakpoints="{ '1200px': '80vw', '960px': '90vw', '640px': '100vw' }"
        >
            <TaskForm
                :projectId="props.project.id"
                :parentId="parentTaskId"
                :task="selectedTask"
                :tasks="tasks"
                :taskTypes="props.taskTypes"
                :taskStatuses="props.taskStatuses"
                :taskPriorities="props.taskPriorities"
                :tags="props.tags"
                :editTask="selectedTask"
                :members="formattedMembers"
                :isMember="isMember"
                :isDeveloper="isDeveloper"
                :taskCategories="taskCategories"
                :excludeEpicCategory="isBacklogCreate || (!selectedTask && (!!selectedSprintId || !!parentTaskId))"
                :onlyEpicCategory="!selectedTask && isAddParentCreate"
                :hideParentTaskField="isBacklogCreate || isAddParentCreate || (!selectedTask && !!selectedSprintId)"
                :sprintId="selectedSprintId"
                @saved="onTaskSaved"
                @close="onDialogClosed"
            />
        </Dialog>
    </AppLayout>
</template>
