<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import CommentEditor from '@/components/ui/comment/CommentEditor.vue';
import CommentItem from '@/components/ui/comment/CommentItem.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { FormDataConvertible } from '@inertiajs/core';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import { ListboxChangeEvent } from 'primevue/listbox';
import { MenuItem } from 'primevue/menuitem';
import { computed, ref, useTemplateRef } from 'vue';
import type { Comment, Epic, Task, User } from '../../..';
import KanbanRow from './KanbanRow.vue';

import TaskActivity = App.Data.Task.TaskActivityData;
import TaskParent = App.Data.Task.TaskParentData;
type FormDataType = Record<string, FormDataConvertible>;

interface Props {
    task: Task | null;
    epicTasks: Epic[];
    canAct: boolean;
    projectMembers?: User[];
}

interface Emits {
    edit: [task: Task, parentId: string | null];
    delete: [task: Task];
    add: [taskId: string];
}

interface ParentFormData extends FormDataType {
    parent_id?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const visible = defineModel<boolean>('visible', { default: false });

const epicPopover = useTemplateRef('epicPopover');
const currentUser = usePage().props.auth.user as User;

const actionOptions = [
    { label: 'Comments', value: 'comments' },
    { label: 'History', value: 'history' },
];

const loading = ref({
    parent: false,
    comment: false,
    history: false,
});

const parentItems = ref<MenuItem[]>([]);
const comments = ref<Comment[]>([]);
const history = ref<TaskActivity[]>([]);
const newComment = ref('');

const action = ref('comments');
const selectedEpic = ref<Epic | null>(null);
const openedAccordion = ref(['0', '1']);

const mappedProjectMembers = computed(() => {
    return (props.projectMembers ?? []).map((member) => ({
        ...member,
        avatar: member.avatar_url,
    }));
});

const parentForm = useForm<ParentFormData>({
    parent_id: null,
});

const subtaskCount = (task: Task) => task.sub_task_recursive?.length || 0;

const doneSubtaskCount = (task: Task) => {
    return (task.sub_task_recursive || []).filter((s) => s.status?.name?.toLowerCase().includes('done')).length;
};

const fetchParents = async () => {
    if (!props.task) return;

    loading.value.parent = true;

    try {
        const { data } = await axios.get(route('task.parents', { task: props.task.id }));

        const parents = data.data as TaskParent[];

        if (parents && parents.length > 0) {
            parentItems.value = parents.map((parent) => ({
                label: parent.category?.name == 'Epic' ? parent.title : parent.key,
                value: parent.id,
                icon: parent.category?.icon ?? undefined,
            }));

            const firstParent = parents[0];

            if (!firstParent.category || firstParent.category.name !== 'Epic') {
                parentItems.value.unshift({
                    label: 'Add Epic',
                    value: null,
                    icon: 'pi pi-pen-to-square',
                    command: (event) => {
                        event.originalEvent.preventDefault();
                        epicPopover.value?.toggle(event.originalEvent);
                    },
                });
            }
        }
    } catch (error) {
        console.error('Failed to fetch parents:', error);
    } finally {
        loading.value.parent = false;
    }
};

const fetchComments = async () => {
    if (!props.task) return;

    loading.value.comment = true;

    try {
        const { data } = await axios.get(route('task.comments', props.task.id));
        comments.value = data.data;
    } catch (error) {
        console.error('Failed to fetch comments:', error);
    } finally {
        loading.value.comment = false;
    }
};

const submitComment = async () => {
    if (!newComment.value.trim() || !props.task) return;

    loading.value.comment = true;

    try {
        await axios.post(route('comments.store'), {
            body: newComment.value,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.task.id,
        });
        newComment.value = '';
        await fetchComments();
    } catch (error) {
        console.error('Failed to submit comment:', error);
    } finally {
        loading.value.comment = false;
    }
};

const fetchHistory = async () => {
    if (!props.task) return;

    loading.value.history = true;

    try {
        const { data } = await axios.get(route('task.activities', props.task.id));
        history.value = data.activities as TaskActivity[];
    } catch (error) {
        console.error('Failed to fetch history:', error);
    } finally {
        loading.value.history = false;
    }
};

const onShow = () => {
    fetchParents();
    fetchComments();
    fetchHistory();
};

const onHide = () => {
    parentItems.value = [];
    comments.value = [];
    newComment.value = '';
    history.value = [];
};

const onSelectEpic = (event: ListboxChangeEvent) => {
    if (!props.task) {
        return;
    }

    if (event.value) {
        parentItems.value[0].label = event.value.title;
        parentItems.value[0].icon = 'pi pi-bolt';
    } else {
        parentItems.value[0].label = 'Add Epic';
        parentItems.value[0].icon = 'pi pi-pen-to-square';
    }

    epicPopover.value?.hide();

    parentForm.parent_id = event.value?.id;

    parentForm.put(route('task.parents.update', props.task.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            parentForm.clearErrors();
        },
    });
};
</script>

<template>
    <Drawer
        v-model:visible="visible"
        modal
        dismissable
        position="right"
        class="!w-full md:!w-1/2 lg:!w-[40%]"
        :block-scroll="true"
        @show="onShow"
        @hide="onHide"
    >
        <template #container="{ closeCallback }">
            <div v-if="task" class="flex h-full flex-col overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-surface-100 p-4 dark:border-surface-700">
                    <Skeleton width="18rem" height="1.25rem" v-if="loading.parent" />
                    <Breadcrumb :model="parentItems" v-else>
                        <template #separator> / </template>
                    </Breadcrumb>

                    <div class="flex shrink-0 items-center gap-1">
                        <Link :href="route('task.show', task.id)">
                            <Button icon="pi pi-external-link" text rounded severity="secondary" v-tooltip.top="'Open full page'" />
                        </Link>

                        <Button icon="pi pi-times" outlined severity="secondary" @click="closeCallback" />
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <h2 class="mb-6 text-2xl font-semibold leading-snug text-surface-800 dark:text-surface-100">
                        {{ task.title }}
                    </h2>

                    <div class="mb-4 space-y-3">
                        <KanbanRow label="Assignee" icon="UsersRound">
                            <Chip v-for="user in task.users" :label="user.name" :key="user.id" class="!py-1 !pl-1 !pr-2 text-sm">
                                <template #icon>
                                    <UserAvatar :user="user" fontSize=".75rem" />
                                </template>
                            </Chip>
                        </KanbanRow>

                        <KanbanRow label="Status" icon="Loader">
                            <Tag v-if="task.status" :value="task.status?.name" :severity="task.status?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Start Date" icon="Calendar">
                            <span>{{ task.start_date ? moment(task.start_date).format('DD MMM YYYY') : '—' }}</span>
                        </KanbanRow>

                        <KanbanRow label="Due Date" icon="CalendarCheck">
                            <span>{{ task.due_date ? moment(task.due_date).format('DD MMM YYYY') : '—' }}</span>
                        </KanbanRow>

                        <KanbanRow label="Priority" icon="Target">
                            <Tag v-if="task.priority" :value="task.priority?.name" :severity="task.priority?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Type" icon="Tag">
                            <Tag v-if="task.type" :value="task.type?.name" :severity="task.type?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Progress" icon="ClipboardCheck">
                            <div class="flex h-full items-center gap-2">
                                <ProgressBar :value="Number(task.progress) || 0" class="flex-1" style="height: 10px" :showValue="false" />
                                <span class="text-xs text-surface-500">{{ task.progress || 0 }}%</span>
                            </div>
                        </KanbanRow>
                    </div>

                    <!-- Subtasks -->
                    <div class="mb-4">
                        <Accordion v-model:value="openedAccordion" multiple>
                            <AccordionPanel value="0">
                                <AccordionHeader>Description</AccordionHeader>
                                <AccordionContent>
                                    <div v-html="task.description"></div>
                                </AccordionContent>
                            </AccordionPanel>

                            <AccordionPanel value="1">
                                <AccordionHeader>
                                    <span class="flex gap-1 font-semibold uppercase">
                                        <span>Subtask</span>
                                        <span v-if="subtaskCount(task)">({{ doneSubtaskCount(task) }}/{{ subtaskCount(task) }})</span>
                                    </span>
                                </AccordionHeader>
                                <AccordionContent pt:content:class="space-y-4">
                                    <div v-if="subtaskCount(task) > 0" class="flex flex-col gap-1.5">
                                        <div
                                            v-for="sub in task.sub_task_recursive"
                                            :key="sub.id"
                                            class="flex items-center justify-between rounded-lg border border-surface-100 bg-surface-50 px-3 py-2 dark:border-surface-700 dark:bg-surface-800"
                                        >
                                            <span class="truncate text-surface-700 dark:text-surface-200">{{ sub.title }}</span>
                                            <div class="ml-2 flex shrink-0 items-center gap-1">
                                                <Tag v-if="sub.status" :value="sub.status?.name" :severity="sub.status?.severity" />
                                                <Link :href="route('task.show', sub.id)">
                                                    <button class="rounded p-0.5 text-surface-400 hover:text-surface-700">
                                                        <i class="pi pi-external-link text-[10px]" />
                                                    </button>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    <p v-else class="mt-1 flex items-center justify-center gap-2 text-surface-400">
                                        <Icon name="Inbox" />
                                        <span>No subtasks yet.</span>
                                    </p>

                                    <Button label="Add Subtask" icon="pi pi-plus" text :disabled="!canAct" @click="emit('add', task.id)" />
                                </AccordionContent>
                            </AccordionPanel>
                        </Accordion>
                    </div>

                    <SelectButton v-model="action" :options="actionOptions" option-label="label" option-value="value" :allow-empty="false" />

                    <Tabs :value="action">
                        <TabPanel value="comments">
                            <div class="mt-4 space-y-6">
                                <CommentEditor
                                    v-model="newComment"
                                    :project-members="mappedProjectMembers"
                                    :loading="loading.comment"
                                    placeholder="Write a comment... Use @ to mention someone"
                                    submit-label="Post Comment"
                                    submit-icon="pi pi-send"
                                    @submit="submitComment"
                                    @cancel="newComment = ''"
                                />

                                <div v-if="loading.comment && comments.length === 0" class="space-y-4">
                                    <Skeleton v-for="i in 3" :key="i" height="4rem" />
                                </div>

                                <div v-else-if="comments.length > 0" class="space-y-6">
                                    <CommentItem
                                        v-for="comment in comments"
                                        :key="comment.id"
                                        :comment="comment"
                                        :task-id="task.id"
                                        :current-user-id="Number(currentUser.id)"
                                        :project-members="mappedProjectMembers"
                                    />
                                </div>

                                <div v-else class="py-8 text-center text-surface-400">
                                    <Icon name="MessageSquare" class="mx-auto mb-2 opacity-20" size="48" />
                                    <p>No comments yet.</p>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel value="history">
                            <div class="mt-4 space-y-6">
                                <div v-if="loading.history && history.length === 0" class="space-y-4">
                                    <Skeleton v-for="i in 3" :key="i" height="4rem" />
                                </div>

                                <Timeline v-else-if="history.length > 0" :value="history" align="left" class="customized-timeline">
                                    <template #marker="{ item }">
                                        <span
                                            class="z-10 flex h-8 w-8 items-center justify-center rounded-full bg-surface-100 ring-4 ring-white dark:bg-surface-700 dark:ring-surface-900"
                                        >
                                            <UserAvatar v-if="item.causer" :user="item.causer" class="h-8 w-8" />
                                            <i v-else class="pi pi-cog text-surface-500"></i>
                                        </span>
                                    </template>

                                    <template #content="{ item }">
                                        <div class="mb-6 ml-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-surface-900 dark:text-surface-100">
                                                    {{ item.causer?.name || 'System' }}
                                                    <span class="font-normal text-surface-500">{{ item.event }} this task</span>
                                                </span>
                                                <span class="text-xs text-surface-500">{{ moment(item.created_at).fromNow() }}</span>
                                            </div>

                                            <div
                                                v-if="item.changed_fields?.length > 0"
                                                class="mt-3 flex flex-col gap-2 rounded-lg border border-surface-200 bg-surface-50 p-3 dark:border-surface-700 dark:bg-surface-800"
                                            >
                                                <div v-for="(field, idx) in item.changed_fields" :key="idx" class="text-sm leading-relaxed">
                                                    <span class="font-medium capitalize text-surface-700 dark:text-surface-300">
                                                        {{ field.field?.replace(/_/g, ' ') }}
                                                    </span>
                                                    :
                                                    <span class="mr-1 text-surface-500 line-through" v-if="field.old_value">
                                                        <span v-if="field.field == 'Description'" v-html="field.old_value"></span>
                                                        <span v-else>{{ field.old_value }}</span>
                                                    </span>

                                                    <span class="text-surface-900 dark:text-surface-100">
                                                        <template v-if="field.new_value">
                                                            <span v-if="field.field == 'Description'" v-html="field.new_value"></span>
                                                            <span v-else>{{ field.new_value }}</span>
                                                        </template>
                                                        <template v-else-if="field.has_value && !field.new_value">Removed</template>
                                                        <template v-else>—</template>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </Timeline>

                                <div v-else class="py-8 text-center text-surface-400">
                                    <Icon name="Activity" class="mx-auto mb-2 opacity-20" size="48" />
                                    <p>No activity history yet.</p>
                                </div>
                            </div>
                        </TabPanel>
                    </Tabs>
                </div>
            </div>

            <Popover
                ref="epicPopover"
                class="before:!content-none after:!content-none"
                pt:content:class="!p-0"
                :style="{
                    marginBlockStart: '0.5rem',
                }"
            >
                <Listbox v-model="selectedEpic" :options="props.epicTasks" option-label="title" class="w-full md:w-56" @change="onSelectEpic" />
            </Popover>
        </template>
    </Drawer>
</template>

<style scoped>
.customized-timeline :deep(.p-timeline-event-opposite) {
    flex: 0;
    padding: 0;
}

.customized-timeline :deep(.p-timeline-event-content) {
    flex: 1;
}
</style>
