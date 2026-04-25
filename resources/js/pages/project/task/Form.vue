<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { ProjectPolicyKey } from '@/types/type';
import { InertiaForm, useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { TreeNode } from 'primevue/treenode';
import { useToast } from 'primevue/usetoast';
import { computed, inject, ref, watch } from 'vue';
import type { ProjectMember, Tag as TagData, Task, TaskCategory, TaskPriority, TaskStatus, TaskType } from '..';

interface Props {
    parentId: string | null;
    projectId: string;
    task: Task | null;
    sprintId?: string | null;
    tasks: Task[];
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskCategories?: TaskCategory[];
    excludeEpicCategory?: boolean;
    onlyEpicCategory?: boolean;
    hideParentTaskField?: boolean;
    tags: TagData[];
    members: ProjectMember[];
}

interface Form {
    _method: 'POST' | 'PUT';
    title: string;
    description: string;
    project_id: string;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    task_category_id: string | null;
    sprint_id: string | null;
    parent_id: string | null;
    start_date: Date | null;
    due_date: Date | null;
    is_archived: boolean;
    progress_value: number;
    assign_users: string[];
    unassign_users: string[];
    add_tag: {
        new: {
            name: string;
            severity: string;
        }[];
        exists: string[];
    };
    remove_tag: string[];
    [key: string]: any;
}

interface ProjectMemberSimple {
    id: string;
    name: string;
}

const toDate = (value?: string | null): Date | null => (value ? new Date(value) : null);

const minDueDate = computed(() => (form.start_date ? form.start_date : undefined));

const props = withDefaults(defineProps<Props>(), {
    taskCategories: () => [], // ← Default value jika tidak dikirim
    excludeEpicCategory: false,
    onlyEpicCategory: false,
    hideParentTaskField: false,
    sprintId: null,
});

const emit = defineEmits(['close', 'saved']);

const policy = inject(ProjectPolicyKey, null);

const toast = useToast();

const { canUpdateTaskField, canUpdateTaskStatus } = useProjectPermissions(policy);

const existedMembers = computed<ProjectMemberSimple[]>(() => props.task?.users?.map((u) => ({ id: u.id as string, name: u.name })) ?? []);

const selectedMembers = ref<ProjectMemberSimple[]>([]);

const authUser = computed(() => usePage().props.auth.user);

const fieldDisabled = (field: string): boolean => !canUpdateTaskField(field);

const formattedMemberOption = computed<ProjectMemberSimple[]>(() => props.members.map((m) => ({ id: m.user.id as string, name: m.user.name })));

const statusOption = computed(() => props.taskStatuses.filter((s) => canUpdateTaskStatus(s.id)));

const selectedParentId = computed({
    get: () => {
        if (!form.parent_id) {
            return null;
        }

        const value: Record<string, boolean> = {};
        value[form.parent_id] = true;

        return value;
    },
    set: (val) => {
        form.parent_id = val ? Object.keys(val)[0] : null;
    },
});

const collectDescendants = (task: Task): string[] => {
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
};

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

const isEpicParentContext = computed(() => {
    if (!props.parentId) return false;
    const parent = findTaskById(props.tasks, props.parentId);
    return (parent?.category?.name ?? '').toLowerCase() === 'epic';
});

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
            members.push({
                id: authUser.value.id,
                name: authUser.value.name,
            });
        }

        selectedMembers.value = members;
    },
    { immediate: true },
);

const form: InertiaForm<Form> = useForm({
    _method: props?.task ? 'PUT' : 'POST',
    project_id: props.projectId,
    title: props?.task?.title ?? '',
    description: props?.task?.description ?? '',
    type_id: props?.task?.type?.id ?? null,
    status_id: props?.task?.status?.id ?? null,
    priority_id: props?.task?.priority?.id ?? null,
    task_category_id: props?.task?.category?.id ?? (props.excludeEpicCategory ? defaultBacklogCategoryId.value : null),
    sprint_id: props.sprintId,
    parent_id: props.onlyEpicCategory ? null : (props?.parentId ?? null),
    start_date: toDate(props?.task?.start_date),
    due_date: toDate(props?.task?.due_date),
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

watch(
    categoryOptions,
    (options) => {
        if (form.task_category_id && !options.some((category) => category.id === form.task_category_id)) {
            form.task_category_id = null;
        }

        // Backlog create: default category to Task if user has not selected one.
        if (!props.task && props.excludeEpicCategory && !form.task_category_id) {
            form.task_category_id = defaultBacklogCategoryId.value;
        }
        if (!props.task && props.onlyEpicCategory && !form.task_category_id) {
            form.task_category_id = defaultEpicCategoryId.value;
        }
    },
    { immediate: true },
);

// =====================
// Validation (Jira-style: only title is required)
// =====================
const validationErrors = ref<Record<string, string>>({});

const isInProgressStatus = computed(() => {
    const status = props.taskStatuses.find((s) => s.id === form.status_id);
    return status?.name === 'In Progress';
});

const validate = (): boolean => {
    const errors: Record<string, string> = {};

    if (!form.title?.trim()) {
        errors.title = 'Title is required.';
    }

    // Validasi due_date jika status adalah "In Progress"
    if (isInProgressStatus.value && !form.due_date) {
        errors.due_date = 'Due date is required when status is In Progress.';
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Auto-clear validation errors when title changes
watch(
    () => form.title,
    () => {
        delete validationErrors.value.title;
    },
);

// Auto-clear due_date error when it changes
watch(
    () => form.due_date,
    () => {
        delete validationErrors.value.due_date;
    },
);

// Auto-clear due_date error when status changes from In Progress
watch(
    () => form.status_id,
    () => {
        if (!isInProgressStatus.value) {
            delete validationErrors.value.due_date;
        }
    },
);

// =====================
// Tags
// =====================
const tagOptions = computed<TagData[]>(() => props.tags);
const selectedTags = ref<TagData[]>([]);
const filteredTags = ref<TagData[]>([]);

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

// =====================
// Status watch: update progress + clear due_date if "To Do"
// =====================
watch(
    () => form.status_id,
    (newStatusId) => {
        if (!newStatusId) {
            form.progress_value = 0;
            return;
        }

        const status = props.taskStatuses.find((s) => s.id === newStatusId);

        form.progress_value = status?.score ?? 0;

        if (status?.name?.toLowerCase() === 'to do') {
            form.due_date = null;
        }
    },
    { immediate: true },
);

const search = (event: any) => {
    const query = event.query.trim().toLowerCase();

    if (!query.length) {
        filteredTags.value = tagOptions.value.filter((tag) => !selectedTags.value.some((sel) => sel.id === tag.id));
        return;
    }

    let result = tagOptions.value
        .filter((tag) => tag.name.toLowerCase().includes(query))
        .filter((tag) => !selectedTags.value.some((sel) => sel.id === tag.id));

    const existsInExisting = tagOptions.value.some((tag) => tag.name.toLowerCase() === query);
    const existsInSelected = selectedTags.value.some((tag) => tag.name.toLowerCase() === query);

    if (!existsInExisting && !existsInSelected) {
        const newTag = {
            id: '',
            name: event.query.trim(),
            severity: '',
        };
        result = [newTag, ...result];
    }

    filteredTags.value = result;
};

const addNewTag = (event: any) => {
    const inputValue = event.target.value.trim();
    if (!inputValue) return;

    const normalized = inputValue.toLowerCase();

    const existsInExisting = tagOptions.value.some((tag) => tag.name.toLowerCase() === normalized);
    const existsInSelected = selectedTags.value.some((tag) => tag.name.toLowerCase() === normalized);

    if (existsInExisting || existsInSelected) {
        event.target.value = '';
        return;
    }

    const severities = ['primary', 'secondary', 'success', 'info', 'warn', 'danger', 'contrast'];
    const randomSeverity = severities[Math.floor(Math.random() * severities.length)];

    const newTag: TagData = {
        id: '',
        name: inputValue,
        severity: randomSeverity,
    };

    selectedTags.value.push(newTag);
    event.target.value = '';
};

const isEdit = computed(() => !!props.task);
const routeName = computed(() => (isEdit.value ? 'project.tasks.update' : 'project.tasks.store'));

const submit = () => {
    if (!validate()) return;

    // Add-parent flow creates Epic only; it must not carry parent_id.
    if (props.onlyEpicCategory) {
        form.parent_id = null;
    }

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
        }).put(route(routeName.value, param), {
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
        }).post(route(routeName.value, param), {
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

const onProgressChange = (val: number | null) => {
    if (val === null) {
        form.progress_value = 0;
        return;
    }

    if (val > 100) {
        form.progress_value = 100;
    } else if (val < 0) {
        form.progress_value = 0;
    } else {
        form.progress_value = val;
    }
};

const getSelectValue = <T extends { id: string }>(id: string, options: T[]): T | null => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div>
            <label class="font-semibold">Title <span class="text-red-500">*</span></label>
            <InputText
                v-model="form.title"
                class="w-full"
                placeholder="Task title"
                :class="{ 'p-invalid': form.errors.title || validationErrors.title }"
            />
            <small v-if="form.errors.title || validationErrors.title" class="p-error text-red-500">
                {{ form.errors.title || validationErrors.title }}
            </small>
        </div>

        <div>
            <label class="font-semibold">Description</label>
            <div
                v-if="fieldDisabled('description')"
                class="min-h-[200px] rounded-md border bg-surface-50 p-3 dark:bg-surface-900"
                v-html="form.description"
            ></div>
            <Editor v-else v-model="form.description" editorStyle="height: 200px" :class="{ 'p-invalid': form.errors.description }">
                <template #toolbar>
                    <span class="ql-formats">
                        <button class="ql-bold"></button>
                        <button class="ql-italic"></button>
                        <button class="ql-underline"></button>
                        <button class="ql-strike"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-header">
                            <option value="1">Heading 1</option>
                            <option value="2">Heading 2</option>
                            <option value="3">Heading 3</option>
                            <option selected></option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered"></button>
                        <button class="ql-list" value="bullet"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link"></button>
                        <button class="ql-code-block"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-clean"></button>
                    </span>
                </template>
            </Editor>
            <small v-if="form.errors.description" class="p-error text-red-500">{{ form.errors.description }}</small>
        </div>

        <!-- Only show Category field if taskCategories is available -->
        <div v-if="categoryOptions.length > 0" class="flex flex-col">
            <label class="font-semibold">Category</label>
            <Select
                :disabled="fieldDisabled('task_category_id')"
                class="w-full"
                v-model="form.task_category_id"
                :options="categoryOptions"
                optionValue="id"
                placeholder="Select Category"
                showClear
                :class="{ 'p-invalid': form.errors.task_category_id }"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-2">
                        <i
                            v-if="getSelectValue(slotProps.value, categoryOptions)?.icon"
                            :class="getSelectValue(slotProps.value, categoryOptions)?.icon"
                            class="text-lg"
                        ></i>
                        <Tag
                            :value="getSelectValue(slotProps.value, categoryOptions)?.name"
                            :severity="getSelectValue(slotProps.value, categoryOptions)?.severity"
                        />
                    </div>
                    <span v-else>{{ slotProps.placeholder }}</span>
                </template>
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <i v-if="slotProps.option.icon" :class="slotProps.option.icon" class="text-lg"></i>
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="flex-1" />
                    </div>
                </template>
            </Select>
            <small v-if="form.errors.task_category_id" class="p-error text-red-500">
                {{ form.errors.task_category_id }}
            </small>
        </div>

        <div v-if="!props.hideParentTaskField" class="flex flex-col">
            <label class="font-semibold">Parent Task</label>
            <TreeSelect
                class="w-full"
                v-model="selectedParentId"
                :options="parentTreeOptions"
                placeholder="Select Parent Task"
                showClear
                filter
                filterMode="lenient"
                :disabled="fieldDisabled('parent_id')"
            />
            <small v-if="form.errors.parent_id" class="p-error text-red-500">
                {{ form.errors.parent_id }}
            </small>
        </div>

        <div class="flex flex-col">
            <label class="font-semibold">Assigned Member</label>
            <MultiSelect
                :disabled="fieldDisabled('assign_users')"
                v-model="selectedMembers"
                display="chip"
                :options="formattedMemberOption"
                optionLabel="name"
                filter
                :showClear="false"
                placeholder="Select Member"
                :maxSelectedLabels="3"
                class="w-full"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Start Date</label>
                <DatePicker
                    :disabled="fieldDisabled('start_date')"
                    class="w-full"
                    v-model="form.start_date"
                    dateFormat="yy-mm-dd"
                    showIcon
                    :class="{ 'p-invalid': form.errors.start_date }"
                />
                <small v-if="form.errors.start_date" class="p-error text-red-500">{{ form.errors.start_date }}</small>
            </div>

            <div>
                <label class="font-semibold">
                    Due Date
                    <span v-if="isInProgressStatus" class="text-red-500">*</span>
                </label>
                <DatePicker
                    :disabled="fieldDisabled('due_date')"
                    class="w-full"
                    v-model="form.due_date"
                    dateFormat="yy-mm-dd"
                    showIcon
                    :minDate="minDueDate"
                    :class="{ 'p-invalid': form.errors.due_date || validationErrors.due_date }"
                />
                <small v-if="form.errors.due_date || validationErrors.due_date" class="p-error text-red-500">
                    {{ form.errors.due_date || validationErrors.due_date }}
                </small>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="font-semibold">Type <span class="text-red-500">*</span></label>
                <Select
                    :disabled="fieldDisabled('type_id')"
                    class="w-full"
                    v-model="form.type_id"
                    :options="props.taskTypes"
                    optionValue="id"
                    placeholder="Select Type"
                    showClear
                    :class="{ 'p-invalid': form.errors.type_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.taskTypes)?.name"
                                :severity="getSelectValue(slotProps.value, props.taskTypes)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.type_id" class="p-error text-red-500">
                    {{ form.errors.type_id }}
                </small>
            </div>

            <div>
                <label class="font-semibold">Status <span class="text-red-500">*</span></label>
                <Select
                    class="w-full"
                    v-model="form.status_id"
                    :options="statusOption"
                    optionValue="id"
                    placeholder="Select Status"
                    showClear
                    :class="{ 'p-invalid': form.errors.status_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.taskStatuses)?.name"
                                :severity="getSelectValue(slotProps.value, props.taskStatuses)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.status_id" class="p-error text-red-500">
                    {{ form.errors.status_id }}
                </small>
            </div>

            <div>
                <label class="font-semibold">Priority <span class="text-red-500">*</span></label>
                <Select
                    :disabled="fieldDisabled('priority_id')"
                    class="w-full"
                    v-model="form.priority_id"
                    :options="props.taskPriorities"
                    optionValue="id"
                    placeholder="Select Priority"
                    showClear
                    :class="{ 'p-invalid': form.errors.priority_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.taskPriorities)?.name"
                                :severity="getSelectValue(slotProps.value, props.taskPriorities)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.priority_id" class="p-error text-red-500">
                    {{ form.errors.priority_id }}
                </small>
            </div>
        </div>

        <div class="flex flex-col">
            <label class="font-semibold">Tags</label>
            <AutoComplete
                :disabled="fieldDisabled('tags')"
                v-model="selectedTags"
                multiple
                optionLabel="name"
                :suggestions="filteredTags"
                @complete="search"
                @keydown.enter.prevent="addNewTag"
                fluid
            >
                <template #option="slotProps">
                    <div class="flex items-center gap-2" :class="{ 'font-bold text-blue-600': slotProps.option.isNew }">
                        <span v-if="!slotProps.option.id" class="font-bold">{{ slotProps.option.name }}</span>
                        <span v-else>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </AutoComplete>
            <small v-if="Object.keys(form.errors).some((key) => key.startsWith('add_tag'))" class="p-error text-red-500"> Invalid tag data. </small>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Archived</label>
                <Select
                    :disabled="fieldDisabled('is_archived')"
                    class="w-full"
                    v-model="form.is_archived"
                    :options="[
                        { label: 'No', value: false },
                        { label: 'Yes', value: true },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Archived Status"
                />
            </div>
            <div>
                <label class="font-semibold">Progress (%)</label>
                <InputNumber
                    v-model="form.progress_value"
                    class="w-full"
                    placeholder="0 - 100"
                    :min="0"
                    :max="100"
                    showButtons
                    disabled
                    @update:modelValue="onProgressChange"
                    :class="{ 'p-invalid': form.errors.progress_value }"
                />
                <small class="text-muted-color">Progress automatically follows task status</small>
                <small v-if="form.errors.progress" class="p-error text-red-500">{{ form.errors.progress }}</small>
            </div>
        </div>

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
