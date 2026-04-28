<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { ProjectPolicyKey } from '@/types/type';
import { InertiaForm, useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, inject, ref, watch } from 'vue';
import type { Task, TaskCategory, TaskFormData, TaskFormProps } from '..';
import InputDateRange from './partials/form-ui/InputDateRange.vue';
import InputDescription from './partials/form-ui/InputDescription.vue';
import InputTags from './partials/form-ui/InputTags.vue';
import InputTitle from './partials/form-ui/InputTitle.vue';
import SelectArchivedProgress from './partials/form-ui/SelectArchivedProgress.vue';
import SelectCategory from './partials/form-ui/SelectCategory.vue';
import SelectMembers from './partials/form-ui/SelectMembers.vue';
import SelectParentTask from './partials/form-ui/SelectParentTask.vue';
import SelectTypeStatusPriority from './partials/form-ui/SelectTypeStatusPriority.vue';

interface MemberSimple {
    id: string;
    name: string;
}

interface TagOption {
    id: string;
    name: string;
    severity: string;
}

const toast = useToast();

const policy = inject(ProjectPolicyKey, null);
const { canUpdateTaskField, canUpdateTaskStatus } = useProjectPermissions(policy);

const props = withDefaults(defineProps<TaskFormProps>(), {
    taskCategories: () => [],
    excludeEpicCategory: false,
    onlyEpicCategory: false,
    hideParentTaskField: false,
    sprintId: null,
});

const emit = defineEmits(['close', 'saved']);

const form: InertiaForm<TaskFormData> = useForm({
    _method: props?.task ? 'PUT' : 'POST',
    project_id: props.projectId,
    title: props?.task?.title ?? '',
    description: props?.task?.description ?? '',
    type_id: props?.task?.type?.id ?? null,
    status_id: props?.task?.status?.id ?? null,
    priority_id: props?.task?.priority?.id ?? null,
    task_category_id: props?.task?.category?.id ?? (props.excludeEpicCategory ? null : null),
    sprint_id: props.sprintId,
    parent_id: props.onlyEpicCategory ? null : (props?.parentId ?? null),
    start_date: props?.task?.start_date ? new Date(props.task.start_date) : null,
    due_date: props?.task?.due_date ? new Date(props.task.due_date) : null,
    is_archived: props?.task?.is_archived ?? false,
    progress_value: props?.task?.progress ?? 0,
    assign_users: [],
    unassign_users: [],
    add_tag: {
        new: [],
        exists: [],
    },
    remove_tag: [],
});

const selectedMembers = ref<MemberSimple[]>([]);
const selectedTags = ref<TagOption[]>([]);
const validationErrors = ref<Record<string, string>>({});

const authUser = computed(() => usePage().props.auth.user);

const fieldDisabled = (field: string): boolean => !canUpdateTaskField(field);

const formattedMemberOption = computed<MemberSimple[]>(() => props.members.map((m) => ({ id: m.user.id as string, name: m.user.name })));

const statusOption = computed(() => props.taskStatuses.filter((s) => canUpdateTaskStatus(s.id)));

const existedMembers = computed<MemberSimple[]>(() => props.task?.users?.map((u) => ({ id: u.id as string, name: u.name })) ?? []);

const selectedParentId = computed({
    get: () => {
        if (!form.parent_id) return null;
        const value: Record<string, boolean> = {};
        value[form.parent_id] = true;
        return value;
    },
    set: (val) => {
        form.parent_id = val ? Object.keys(val)[0] : null;
    },
});

const isEpicParentContext = computed(() => {
    if (!props.parentId) return false;
    const parent = findTaskById(props.tasks, props.parentId);
    return (parent?.category?.name ?? '').toLowerCase() === 'epic';
});

const categoryOptions = computed<TaskCategory[]>(() => {
    if (props.onlyEpicCategory) {
        return props.taskCategories.filter((category) => category.name?.toLowerCase() === 'epic');
    }
    if (!props.excludeEpicCategory && !isEpicParentContext.value) return props.taskCategories;
    return props.taskCategories.filter((category) => category.name?.toLowerCase() !== 'epic');
});

const defaultBacklogCategoryId = computed<string | null>(() => {
    const taskCategory = categoryOptions.value.find((category) => category.name?.toLowerCase() === 'task');
    return taskCategory?.id ?? null;
});

const defaultEpicCategoryId = computed<string | null>(() => {
    const epicCategory = props.taskCategories.find((category) => category.name?.toLowerCase() === 'epic');
    return epicCategory?.id ?? null;
});

const tagOptions = computed<TagOption[]>(() => props.tags);

const minDueDate = computed(() => (form.start_date ? form.start_date : undefined));

const isInProgressStatus = computed(() => {
    const status = props.taskStatuses.find((s) => s.id === form.status_id);
    return status?.name === 'In Progress';
});

const isEdit = computed(() => !!props.task);

const findTaskById = (tasks: Task[], id: string): Task | null => {
    for (const task of tasks) {
        if (task.id === id) return task;
        const children = task.sub_task_recursive ?? [];
        if (children.length > 0) {
            const found = findTaskById(children, id);
            if (found) return found;
        }
    }
    return null;
};

const validate = (): boolean => {
    const errors: Record<string, string> = {};

    if (!form.title?.trim()) {
        errors.title = 'Title is required.';
    }

    if (isInProgressStatus.value && !form.due_date) {
        errors.due_date = 'Due date is required when status is In Progress.';
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const onStatusChange = (newStatusId: string | null) => {
    delete validationErrors.value.due_date;

    if (!newStatusId) {
        form.progress_value = 0;
        return;
    }

    const status = props.taskStatuses.find((s) => s.id === newStatusId);
    form.progress_value = status?.score ?? 0;

    if (status?.name?.toLowerCase() === 'to do') {
        form.due_date = null;
    }
};

const submit = () => {
    if (!validate()) return;

    if (props.onlyEpicCategory) {
        form.parent_id = null;
    }

    const routeName = isEdit.value ? 'project.tasks.update' : 'project.tasks.store';

    const existed = existedMembers.value.map((u) => u.id);
    const selected = selectedMembers.value.map((u) => u.id);

    form.assign_users = selected.filter((id) => !existed.includes(id));
    form.unassign_users = existed.filter((id) => !selected.includes(id));

    const oldTags = props?.task?.tags?.map((t) => t.id) ?? [];
    const tagExist = selectedTags.value.filter((t) => t.id);
    const tagExistIds = tagExist.map((t) => t.id);
    const addTagExist = tagExistIds.filter((id) => !oldTags.includes(id));
    const addTagNew = selectedTags.value.filter((t) => !t.id);
    const removeTags = oldTags.filter((id) => !tagExistIds.includes(id));

    form.add_tag.new = addTagNew;
    form.add_tag.exists = addTagExist;
    form.remove_tag = removeTags;

    const param: any = { projectEncoded: props.projectId };

    if (isEdit.value) {
        param.taskEncoded = props.task?.id;
        form.transform(function (data) {
            return {
                ...data,
                start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
                due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
            };
        }).put(route(routeName, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                validationErrors.value = {};
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update task', life: 3000 });
            },
        });
    } else {
        form.transform(function (data) {
            return {
                ...data,
                start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
                due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
            };
        }).post(route(routeName, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                validationErrors.value = {};
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to store task', life: 3000 });
            },
        });
    }
};

watch(
    existedMembers,
    (val) => {
        const members = [...val];

        if (!authUser.value) {
            selectedMembers.value = members;
            return;
        }

        const authExistsInOptions = formattedMemberOption.value.some((m) => m.id === authUser.value.id);
        const authExistsInMembers = members.some((m) => m.id === authUser.value.id);

        if (authExistsInOptions && !authExistsInMembers) {
            members.push({ id: authUser.value.id, name: authUser.value.name });
        }

        selectedMembers.value = members;
    },
    { immediate: true },
);

watch(
    () => props.task?.tags,
    (tags) => {
        if (tags && Array.isArray(tags)) {
            selectedTags.value = tags.map((t) => ({
                id: t.id,
                name: t.name,
                severity: t.severity ?? '',
            }));
        }
    },
    { immediate: true },
);

watch(
    categoryOptions,
    (options) => {
        if (form.task_category_id && !options.some((category) => category.id === form.task_category_id)) {
            form.task_category_id = null;
        }

        if (!props.task && props.excludeEpicCategory && !form.task_category_id) {
            form.task_category_id = defaultBacklogCategoryId.value;
        }
        if (!props.task && props.onlyEpicCategory && !form.task_category_id) {
            form.task_category_id = defaultEpicCategoryId.value;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <InputTitle
            v-model="form.title"
            :error="form.errors.title || validationErrors.title"
            :disabled="fieldDisabled('title')"
            @update:modelValue="() => delete validationErrors.title"
        />

        <InputDescription v-model="form.description" :error="form.errors.description" :disabled="fieldDisabled('description')" />

        <SelectCategory
            v-if="categoryOptions.length > 0"
            v-model="form.task_category_id"
            :options="categoryOptions"
            :error="form.errors.task_category_id"
            :disabled="fieldDisabled('task_category_id')"
        />

        <SelectParentTask
            v-if="!props.hideParentTaskField"
            v-model="selectedParentId"
            :task="props.task"
            :tasks="props.tasks"
            :error="form.errors.parent_id"
            :disabled="fieldDisabled('parent_id')"
        />

        <SelectMembers v-model="selectedMembers" :options="formattedMemberOption" :disabled="fieldDisabled('assign_users')" />

        <InputDateRange
            v-model:startDate="form.start_date"
            v-model:dueDate="form.due_date"
            :minDueDate="minDueDate"
            :isInProgressStatus="isInProgressStatus"
            :startDateError="form.errors.start_date"
            :dueDateError="form.errors.due_date || validationErrors.due_date"
            :disabled="fieldDisabled('start_date')"
            @update:dueDate="() => delete validationErrors.due_date"
        />

        <SelectTypeStatusPriority
            v-model:typeId="form.type_id"
            v-model:statusId="form.status_id"
            v-model:priorityId="form.priority_id"
            :typeOptions="props.taskTypes"
            :statusOptions="statusOption"
            :priorityOptions="props.taskPriorities"
            :typeError="form.errors.type_id"
            :statusError="form.errors.status_id"
            :priorityError="form.errors.priority_id"
            :typeDisabled="fieldDisabled('type_id')"
            :priorityDisabled="fieldDisabled('priority_id')"
            @update:statusId="onStatusChange"
        />

        <InputTags
            v-model="selectedTags"
            :options="tagOptions"
            :error="Object.keys(form.errors).some((k) => k.startsWith('add_tag')) ? 'Invalid tag data.' : null"
            :disabled="fieldDisabled('tags')"
        />

        <SelectArchivedProgress
            v-model:isArchived="form.is_archived"
            v-model:progressValue="form.progress_value"
            :progressError="form.errors.progress_value"
            :archivedDisabled="fieldDisabled('is_archived')"
        />

        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" :disabled="form.processing" />
            <Button v-if="!isEdit" label="Create Task" @click="submit" icon="pi pi-save" :loading="form.processing" :disabled="form.processing" />
            <Button
                v-else
                label="Update Task"
                severity="warning"
                @click="submit"
                :loading="form.processing"
                :disabled="form.processing"
                icon="pi pi-save"
            />
        </div>
    </div>
</template>
