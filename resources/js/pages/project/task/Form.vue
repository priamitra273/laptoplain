<script setup lang="ts">
import { InertiaForm, useForm, usePage } from '@inertiajs/vue3';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

import moment from 'moment';
import type { ProjectMember, Tag as TagData, Task, TaskPriority, TaskStatus, TaskType } from '..';

interface Props {
    parentId: string | null;
    projectId: string;
    task: Task | null;
    tasks: Task[];
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    tags: TagData[];
    members: ProjectMember[];
    isDeveloper: boolean
}

interface Form {
    _method: 'POST' | 'PUT';
    title: string;
    description: string;
    project_id: string;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
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

interface TreeNodeOption {
    key: string;
    label: string;
    children?: TreeNodeOption[];
}

const toDate = (value?: string | null): Date | null => (value ? new Date(value) : null);

const minDueDate = computed(() => (form.start_date ? form.start_date : undefined));

const props = defineProps<Props>();

const emit = defineEmits(['close', 'saved']);
const toast = useToast();

const existedMembers = computed<ProjectMemberSimple[]>(() => props.task?.users?.map((u) => ({ id: u.id, name: u.name })) ?? []);

const selectedMembers = ref<ProjectMemberSimple[]>([]);

const authUser = computed(() => usePage().props.auth.user);

const formattedMemberOption = computed<ProjectMemberSimple[]>(() => props.members.map((m) => ({ id: m.user.id, name: m.user.name })));

const selectedParent = ref<Record<string, boolean> | null>(props.task?.parent_id ? { [props.task.parent_id]: true } : null);

const statusOption = computed(() => {
    return props.isDeveloper ?
        props.taskStatuses.filter((status) => ['In Progress', 'In Review'].includes(status.name)) :
        props.taskStatuses
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

const parentTreeOptions = computed<TreeNodeOption[]>(() => {
    const excludeIds = new Set<string>();

    if (props.task) {
        excludeIds.add(props.task.id);
        collectDescendants(props.task).forEach((id) => excludeIds.add(id));
    }

    const build = (tasks: Task[]): TreeNodeOption[] => {
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
    parent_id: props?.parentId ?? null,
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

// =====================
// Validation
// =====================
const validationErrors = ref<Record<string, string>>({});

const isToDoStatus = computed(() => {
    const status = props.taskStatuses.find((s) => s.id === form.status_id);
    return status?.name?.toLowerCase() === 'to do';
});

const validate = (): boolean => {
    const errors: Record<string, string> = {};

    if (!form.title?.trim()) {
        errors.title = 'Title is required.';
    }

    if (!form.status_id) {
        errors.status_id = 'Status is required.';
    }

    if (!form.priority_id) {
        errors.priority_id = 'Priority is required.';
    }

    if (!form.type_id) {
        errors.type_id = 'Type is required.';
    }

    if (!selectedMembers.value.length) {
        errors.assign_users = 'At least one member must be assigned.';
    }

    if (!isToDoStatus.value && !form.due_date) {
        errors.due_date = 'Due date is required for this status.';
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Auto-clear validation errors when fields change
watch(
    () => form.title,
    () => {
        delete validationErrors.value.title;
    },
);
watch(
    () => form.status_id,
    () => {
        delete validationErrors.value.status_id;
    },
);
watch(
    () => form.priority_id,
    () => {
        delete validationErrors.value.priority_id;
    },
);
watch(
    () => form.type_id,
    () => {
        delete validationErrors.value.type_id;
    },
);
watch(
    () => form.due_date,
    () => {
        delete validationErrors.value.due_date;
    },
);
watch(
    selectedMembers,
    () => {
        delete validationErrors.value.assign_users;
    },
    { deep: true },
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

    if (selectedParent.value) {
        form.parent_id = Object.keys(selectedParent.value)[0];
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
                selectedParent.value = {};
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
                selectedParent.value = {};
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

const getSelectValue = (id: string, options: TaskType[] | TaskStatus[] | TaskPriority[]) => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div v-if="isEdit" class="flex flex-col">
            <label class="font-semibold">Parent Task</label>
            <TreeSelect v-model="selectedParent" :options="parentTreeOptions" placeholder="Select Parent Task" class="w-full" showClear />
        </div>

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
            <div v-if="isDeveloper" class="p-3 border rounded-md min-h-[200px] bg-surface-50 dark:bg-surface-900" v-html="form.description"></div>
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

        <div class="flex flex-col">
            <label class="font-semibold">Assigned Member <span class="text-red-500">*</span></label>
            <MultiSelect
            :disabled="isDeveloper"
                v-model="selectedMembers"
                display="chip"
                :options="formattedMemberOption"
                optionLabel="name"
                filter
                placeholder="Select Member"
                :maxSelectedLabels="3"
                class="w-full"
                :class="{ 'p-invalid': form.errors.assign_users || validationErrors.assign_users }"
            />
            <small v-if="form.errors.assign_users || validationErrors.assign_users" class="p-error text-red-500">
                {{ form.errors.assign_users || validationErrors.assign_users }}
            </small>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Start Date</label>
                <DatePicker
                :disabled="isDeveloper"
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
                    <span v-if="!isToDoStatus" class="text-red-500">*</span>
                </label>
                <DatePicker
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
                :disabled="isDeveloper"
                    class="w-full"
                    v-model="form.type_id"
                    :options="props.taskTypes"
                    optionValue="id"
                    placeholder="Select Type"
                    :class="{ 'p-invalid': form.errors.type_id || validationErrors.type_id }"
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
                <small v-if="form.errors.type_id || validationErrors.type_id" class="p-error text-red-500">
                    {{ form.errors.type_id || validationErrors.type_id }}
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
                    :class="{ 'p-invalid': form.errors.status_id || validationErrors.status_id }"
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
                <small v-if="form.errors.status_id || validationErrors.status_id" class="p-error text-red-500">
                    {{ form.errors.status_id || validationErrors.status_id }}
                </small>
            </div>

            <div>
                <label class="font-semibold">Priority <span class="text-red-500">*</span></label>
                <Select
                :disabled="isDeveloper"
                    class="w-full"
                    v-model="form.priority_id"
                    :options="props.taskPriorities"
                    optionValue="id"
                    placeholder="Select Priority"
                    :class="{ 'p-invalid': form.errors.priority_id || validationErrors.priority_id }"
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
                <small v-if="form.errors.priority_id || validationErrors.priority_id" class="p-error text-red-500">
                    {{ form.errors.priority_id || validationErrors.priority_id }}
                </small>
            </div>
        </div>

        <div class="flex flex-col">
            <label class="font-semibold">Tags</label>
            <AutoComplete
            :disabled="isDeveloper"
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
                :disabled="isDeveloper"
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
