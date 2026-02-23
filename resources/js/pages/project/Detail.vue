<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, onUnmounted, ref, watch } from 'vue';
import { ProjectMember, Tag as TagData, Task, TaskPriority, TaskStatus, TaskType } from '.';
import MemberEditForm from './member/EditFormTemp.vue';
import MemberAddForm from './member/Form.vue';
import MembersTable from './member/Table.vue';
import TaskForm from './task/Form.vue';
import TaskTable from './task/Table.vue';

import ProjectGanttChart from '@/components/ProjectGanttChart.vue';
import 'emoji-mart-vue-fast/css/emoji-mart.css';

interface User {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

interface MemberWithAvatar extends ProjectMember {
    user: User;
}

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
        status_id?: string;
        priority_id?: string;
        created_at?: string;
        updated_at?: string;
        project_members: ProjectMember[];
    };
    members: MemberWithAvatar[];
    roles: { id: string; name: string }[];
    users: User[];

    tasks: Task[];
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    tags: TagData[];
    assignableUsers: User[];

    statuses?: { id: string; name: string; severity?: string }[];
    priorities?: { id: string; name: string; severity?: string }[];
}

const props = defineProps<Props>();

const toast = useToast();

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const isMember = computed(() => {
    if (!authUser.value) return false;
    return props.project.project_members.some((member) => member.user.id === authUser.value.id);
});
const isOwner = computed(() => {
    if (!authUser.value) return false;

    return props.project.project_members.some((member) => member.user.id === authUser.value.id && member.role.name === 'Owner');
});

const visibleAdd = ref(false);
const visibleEdit = ref(false);
const visibleTaskAdd = ref(false);
const selectedMember = ref<ProjectMember | null>(null);
const selectedTask = ref<Task | null>(null);

const parentTaskId = ref<string | null>(null);

// Edit mode states
const editMode = ref({
    title: false,
    status: false,
    priority: false,
    startDate: false,
    dueDate: false,
    description: false,
});

// Local state for editable fields
const localProject = ref({ ...props.project });

let emojiIndex = new EmojiIndex(emojiData);

// Refs untuk click outside detection
const titleInputRef = ref<HTMLElement | null>(null);
const statusDropdownRef = ref<HTMLElement | null>(null);
const priorityDropdownRef = ref<HTMLElement | null>(null);
const startDatePickerRef = ref<HTMLElement | null>(null);
const dueDatePickerRef = ref<HTMLElement | null>(null);

// Event listeners storage
const clickOutsideListeners = new Map<string, (e: MouseEvent) => void>();

// Watch for props changes
watch(
    () => props.project,
    (newProject) => {
        localProject.value = { ...newProject };
    },
    { deep: true },
);

// Handler untuk click outside
const setupClickOutside = (field: keyof typeof editMode.value, elementRef: any) => {
    // Remove existing listener if any
    const existingListener = clickOutsideListeners.get(field);
    if (existingListener) {
        document.removeEventListener('click', existingListener);
    }

    // Create new listener
    const listener = (event: MouseEvent) => {
        const element = elementRef.value;
        const target = event.target as Node;

        if (!element) return;

        // Check if element has $el property (PrimeVue component)
        const domElement = element.$el || element;

        if (domElement && !domElement.contains(target)) {
            cancelEdit(field);
        }
    };

    // Store and add listener
    clickOutsideListeners.set(field, listener);
    setTimeout(() => {
        document.addEventListener('click', listener);
    }, 100);
};

const removeClickOutside = (field: keyof typeof editMode.value) => {
    const listener = clickOutsideListeners.get(field);
    if (listener) {
        document.removeEventListener('click', listener);
        clickOutsideListeners.delete(field);
    }
};

// Watch untuk menambahkan event listener ketika edit mode aktif
watch(
    () => editMode.value.title,
    (isActive) => {
        if (isActive) {
            setupClickOutside('title', titleInputRef);
        } else {
            removeClickOutside('title');
        }
    },
);

watch(
    () => editMode.value.status,
    (isActive) => {
        if (isActive) {
            setupClickOutside('status', statusDropdownRef);
        } else {
            removeClickOutside('status');
        }
    },
);

watch(
    () => editMode.value.priority,
    (isActive) => {
        if (isActive) {
            setupClickOutside('priority', priorityDropdownRef);
        } else {
            removeClickOutside('priority');
        }
    },
);

watch(
    () => editMode.value.startDate,
    (isActive) => {
        if (isActive) {
            setupClickOutside('startDate', startDatePickerRef);
        } else {
            removeClickOutside('startDate');
        }
    },
);

watch(
    () => editMode.value.dueDate,
    (isActive) => {
        if (isActive) {
            setupClickOutside('dueDate', dueDatePickerRef);
        } else {
            removeClickOutside('dueDate');
        }
    },
);

// Cleanup on unmount
onUnmounted(() => {
    clickOutsideListeners.forEach((listener) => {
        document.removeEventListener('click', listener);
    });
    clickOutsideListeners.clear();
});

const openAdd = () => (visibleAdd.value = true);
const openEdit = (member: ProjectMember) => {
    selectedMember.value = member;
    visibleEdit.value = true;
};

const openTaskAdd = (parentId: string | null) => {
    if (!isMember && !hasPermission()) {
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

const openTaskEdit = (task: Task, parentId: string | null) => {
    if (!isMember && !hasPermission()) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member to edit tasks',
            life: 3000,
        });
        return;
    }
    parentTaskId.value = parentId;
    selectedTask.value = task;
    visibleTaskAdd.value = true;
};

const onSaved = () => {
    visibleAdd.value = false;
    visibleEdit.value = false;
};

const onDialogClosed = () => {
    visibleTaskAdd.value = false;
    selectedTask.value = null;
    parentTaskId.value = null;
};

const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('DD MMMM YYYY') : '-';
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getMemberColor = (index: number) => {
    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#6366f1', '#f43f5e'];
    return colors[index % colors.length];
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
                    avatar_url: user.avatar_url,
                },
                role: { id: '', name: '' },
            }) as MemberWithAvatar,
    );
});

const taskDialogHeader = computed(() => {
    if (selectedTask.value) {
        return 'Edit Task';
    }
    return parentTaskId.value ? 'Create Subtask' : 'Create Task';
});

const hasPermission = (): boolean => {
    const role = usePage().props.auth.role;
    return role ? role.startsWith('super-admin-') || role.startsWith('admin-') : false;
};

const canEdit = computed(() => {
    return isOwner || hasPermission();
});

// Enable edit mode for a field
const enableEditMode = (field: keyof typeof editMode.value) => {
    if (!canEdit.value) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You do not have permission to edit this project',
            life: 3000,
        });
        return;
    }
    editMode.value[field] = true;
};

// Disable edit mode for a field
const disableEditMode = (field: keyof typeof editMode.value) => {
    editMode.value[field] = false;
};

// Update project - sesuai dengan ProjectUpdateRequest di backend
const updateProject = (newValue: any, field: string, editField?: keyof typeof editMode.value) => {
    if (!canEdit.value) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You do not have permission to edit this project',
            life: 3000,
        });
        return;
    }

    // Prepare payload sesuai dengan ProjectUpdateRequest
    // Backend memerlukan: title, description, emoji, start_date, due_date, status_id, priority_id
    let payload: any = {
        title: localProject.value.title,
        description: localProject.value.description || '',
        emoji: localProject.value.emoji,
        start_date: localProject.value.start_date,
        due_date: localProject.value.due_date,
        status_id: localProject.value.status_id || localProject.value.status?.id,
        priority_id: localProject.value.priority_id || localProject.value.priority?.id,
    };

    // Update field yang diubah
    if (field === 'start_date' || field === 'due_date') {
        payload[field] = moment(newValue).format('YYYY-MM-DD');
    } else if (field === 'status_id' || field === 'priority_id') {
        payload[field] = newValue;
    } else {
        payload[field] = newValue;
    }

    // Format semua tanggal ke format YYYY-MM-DD
    if (payload.start_date) {
        payload.start_date = moment(payload.start_date).format('YYYY-MM-DD');
    }
    if (payload.due_date) {
        payload.due_date = moment(payload.due_date).format('YYYY-MM-DD');
    }

    router.put(route('project.update', props.project.id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (editField) {
                disableEditMode(editField);
            }
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Project updated successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            console.error('Update errors:', errors);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errors[Object.keys(errors)[0]] || 'Failed to update project',
                life: 3000,
            });
        },
    });
};

// Update handlers
const onTitleBlur = () => {
    if (localProject.value.title !== props.project.title) {
        updateProject(localProject.value.title, 'title', 'title');
    } else {
        disableEditMode('title');
    }
};

const onStatusChange = (event: any) => {
    localProject.value.status_id = event.value.id;
    updateProject(event.value.id, 'status_id', 'status');
};

const onPriorityChange = (event: any) => {
    localProject.value.priority_id = event.value.id;
    updateProject(event.value.id, 'priority_id', 'priority');
};

const onStartDateChange = (value: Date | Date[] | (Date | null)[] | null | undefined) => {
    if (value instanceof Date) {
        updateProject(value, 'start_date', 'startDate');
    }
};

const onDueDateChange = (value: Date | Date[] | (Date | null)[] | null | undefined) => {
    if (value instanceof Date) {
        updateProject(value, 'due_date', 'dueDate');
    }
};

const onDescriptionBlur = () => {
    if (localProject.value.description !== props.project.description) {
        updateProject(localProject.value.description, 'description', 'description');
    } else {
        disableEditMode('description');
    }
};

const cancelEdit = (field: keyof typeof editMode.value) => {
    localProject.value = { ...props.project };
    disableEditMode(field);
};
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
                    <div class="flex-1">
                        <!-- Editable Title -->
                        <div
                            v-if="!editMode.title"
                            @click="enableEditMode('title')"
                            :class="canEdit ? '-mx-2 -my-1 cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                        >
                            <h1 class="text-2xl font-semibold text-surface-900 dark:text-surface-0">
                                {{ props.project.title }}
                            </h1>
                        </div>
                        <div v-else class="flex items-center gap-2" ref="titleInputRef" @click.stop>
                            <InputText
                                v-model="localProject.title"
                                class="text-2xl font-semibold"
                                @blur="onTitleBlur"
                                @keyup.enter="onTitleBlur"
                                @keyup.escape="cancelEdit('title')"
                                autofocus
                            />
                        </div>
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
                            v-for="(member, index) in props.members.slice(0, 3)"
                            :key="member.id"
                            :image="
                                member.user.avatar_url && member.user.avatar_url !== '/images/default-avatar.png' ? member.user.avatar_url : undefined
                            "
                            :label="
                                !member.user.avatar_url || member.user.avatar_url === '/images/default-avatar.png'
                                    ? getInitials(member.user.name)
                                    : undefined
                            "
                            size="large"
                            shape="circle"
                            :style="
                                !member.user.avatar_url || member.user.avatar_url === '/images/default-avatar.png'
                                    ? { backgroundColor: getMemberColor(index), color: 'white', fontSize: '1.25rem', fontWeight: '600' }
                                    : {}
                            "
                            :title="member.user.name"
                            class="border-3 border-white dark:border-surface-900"
                        />
                        <Avatar
                            v-if="props.members.length > 3"
                            :label="`+${props.members.length - 3}`"
                            size="large"
                            shape="circle"
                            style="background-color: #64748b; color: white; font-size: 1.25rem; font-weight: 600"
                            class="border-3 border-white dark:border-surface-900"
                        />
                    </AvatarGroup>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <!-- Status Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Status</span>
                            <div v-if="!editMode.status" @click="enableEditMode('status')" :class="canEdit ? 'cursor-pointer' : ''">
                                <Tag
                                    :value="props.project.status?.name || 'In Progress'"
                                    :severity="props.project.status?.severity || 'info'"
                                    class="w-fit"
                                />
                            </div>
                            <div v-else ref="statusDropdownRef" @click.stop>
                                <Select
                                    v-model="localProject.status"
                                    :options="props.statuses"
                                    optionLabel="name"
                                    placeholder="Select Status"
                                    @change="onStatusChange"
                                    class="w-full"
                                    autofocus
                                >
                                    <template #value="slotProps">
                                        <Tag v-if="slotProps.value" :value="slotProps.value.name" :severity="slotProps.value.severity || 'info'" />
                                    </template>
                                    <template #option="slotProps">
                                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity || 'info'" />
                                    </template>
                                </Select>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Priority Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Priority</span>
                            <div v-if="!editMode.priority" @click="enableEditMode('priority')" :class="canEdit ? 'cursor-pointer' : ''">
                                <Tag
                                    :value="props.project.priority?.name || 'Medium'"
                                    :severity="props.project.priority?.severity || 'warning'"
                                    class="w-fit"
                                />
                            </div>
                            <div v-else ref="priorityDropdownRef" @click.stop>
                                <Dropdown
                                    v-model="localProject.priority"
                                    :options="props.priorities"
                                    optionLabel="name"
                                    placeholder="Select Priority"
                                    @change="onPriorityChange"
                                    class="w-full"
                                    autofocus
                                >
                                    <template #value="slotProps">
                                        <Tag v-if="slotProps.value" :value="slotProps.value.name" :severity="slotProps.value.severity || 'warning'" />
                                    </template>
                                    <template #option="slotProps">
                                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity || 'warning'" />
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Timeline Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Timeline</span>
                            <div v-if="!editMode.startDate && !editMode.dueDate" class="flex flex-col gap-2">
                                <div
                                    @click="enableEditMode('startDate')"
                                    :class="canEdit ? 'cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                                    class="text-sm text-surface-700 dark:text-surface-300"
                                >
                                    Start: {{ moment(props.project.start_date).format('MMM DD, YYYY') }}
                                </div>
                                <div
                                    @click="enableEditMode('dueDate')"
                                    :class="canEdit ? 'cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                                    class="text-sm text-surface-700 dark:text-surface-300"
                                >
                                    Due: {{ moment(props.project.due_date).format('MMM DD, YYYY') }}
                                </div>
                            </div>
                            <div v-else class="flex flex-col gap-2">
                                <div v-if="editMode.startDate" ref="startDatePickerRef" @click.stop>
                                    <DatePicker
                                        :modelValue="new Date(localProject.start_date || '')"
                                        @update:modelValue="onStartDateChange"
                                        dateFormat="dd M yy"
                                        placeholder="Start Date"
                                        class="w-full text-sm"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="px-2 py-1 text-sm text-surface-700 dark:text-surface-300">
                                    Start: {{ moment(props.project.start_date).format('MMM DD, YYYY') }}
                                </div>

                                <div v-if="editMode.dueDate" ref="dueDatePickerRef" @click.stop>
                                    <DatePicker
                                        :modelValue="new Date(localProject.due_date || '')"
                                        @update:modelValue="onDueDateChange"
                                        dateFormat="dd M yy"
                                        placeholder="Due Date"
                                        class="w-full text-sm"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="px-2 py-1 text-sm text-surface-700 dark:text-surface-300">
                                    Due: {{ moment(props.project.due_date).format('MMM DD, YYYY') }}
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Progress Card -->
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Progress</span>
                            <div class="flex items-center gap-2">
                                <ProgressBar :value="props.project.progress" class="flex-1" :showValue="true" />
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
                            <Tab value="Timeline">Timeline</Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="Board">
                                <div class="py-4">
                                    <TaskTable
                                        :projectId="project.id"
                                        :tasks="tasks"
                                        @add="openTaskAdd"
                                        @edit="openTaskEdit"
                                        :isMember="isMember"
                                        :has-permission="isOwner || hasPermission()"
                                        :taskStatuses="taskStatuses"
                                        :taskPriorities="taskPriorities"
                                        :taskTypes="taskTypes"
                                    />
                                </div>
                            </TabPanel>

                            <TabPanel value="Details">
                                <div class="grid grid-cols-1 gap-8 py-4 lg:grid-cols-3">
                                    <div class="lg:col-span-2">
                                        <div class="mb-3 flex items-center justify-between">
                                            <h3 class="text-sm font-semibold uppercase text-surface-500 dark:text-surface-400">Description</h3>
                                        </div>
                                        <!-- Editable Description -->
                                        <div
                                            v-if="!editMode.description"
                                            @click="enableEditMode('description')"
                                            :class="canEdit ? 'cursor-pointer rounded p-2 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                                        >
                                            <div
                                                class="prose dark:prose-invert max-w-none break-words text-surface-700 dark:text-surface-300"
                                                v-html="
                                                    props.project.description ||
                                                    '<p class=\'text-surface-500 italic\'>No description provided. Click to add.</p>'
                                                "
                                            />
                                        </div>
                                        <div v-else class="relative">
                                            <!-- Overlay untuk mendeteksi klik di luar -->
                                            <div class="fixed inset-0 z-10" @click="onDescriptionBlur"></div>
                                            <!-- Editor wrapper dengan z-index lebih tinggi -->
                                            <div class="relative z-20" @click.stop>
                                                <Editor v-model="localProject.description" editorStyle="height: 200px">
                                                    <template #toolbar>
                                                        <span class="ql-formats">
                                                            <button class="ql-bold"></button>
                                                            <button class="ql-italic"></button>
                                                            <button class="ql-underline"></button>
                                                            <button class="ql-list" value="ordered"></button>
                                                            <button class="ql-list" value="bullet"></button>
                                                        </span>
                                                    </template>
                                                </Editor>
                                            </div>
                                        </div>
                                    </div>

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
                                        :hasPermission="isOwner || hasPermission()"
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

        <Dialog
            v-model:visible="visibleTaskAdd"
            :header="taskDialogHeader"
            modal
            scrollable
            maximizable
            @hide="onDialogClosed"
            :style="{ width: '70rem' }"
            :contentStyle="{ maxHeight: '75vh' }"
            :breakpoints="{
                '1200px': '80vw',
                '960px': '90vw',
                '640px': '100vw',
            }"
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
                @close="
                    visibleTaskAdd = false;
                    selectedTask = null;
                    parentTaskId = null;
                "
            />
        </Dialog>
    </AppLayout>
</template>
