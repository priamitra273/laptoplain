<script setup lang="ts">
import { InertiaForm, useForm } from '@inertiajs/vue3';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

import type { ProjectMember, Tag as TagData, Task, TaskPriority, TaskStatus, TaskType } from '..';

interface Props {
    parentId: string | null;
    projectId: string;
    task: Task | null;
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
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

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);
const toast = useToast();

const existedMembers = computed<ProjectMemberSimple[]>(() => props.task?.users?.map((u) => ({ id: u.id, name: u.name })) ?? []);

const selectedMembers = ref<ProjectMemberSimple[]>([]);

const formattedMemberOption = computed<ProjectMemberSimple[]>(() => props.members.map((m) => ({ id: m.user.id, name: m.user.name })));

watch(
    existedMembers,
    (val) => {
        selectedMembers.value = val;
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

const tagOptions = computed<TagData[]>(() => props.tags);
const selectedTags = ref<TagData[]>([]);
const filteredTags = ref<TagData[]>([]);

watch(
    () => props.task?.tags,
    (tags) => {
        if (tags && Array.isArray(tags)) {
            // masuk ke selectedTags
            selectedTags.value = tags.map((t) => ({
                id: t.id, // hashed id
                name: t.name,
                severity: t.severity ?? '',
            }));
        }
    },
    { immediate: true },
);

// === SEARCH MIRIP PRIMEVUE EXAMPLE ===
const search = (event: any) => {
    const query = event.query.trim().toLowerCase();

    // Jika query kosong → tampilkan semua yang belum dipilih
    if (!query.length) {
        filteredTags.value = tagOptions.value.filter((tag) => !selectedTags.value.some((sel) => sel.id === tag.id));
        return;
    }

    // Filter tag yang cocok dengan query dan belum dipilih
    let result = tagOptions.value
        .filter((tag) => tag.name.toLowerCase().includes(query))
        .filter((tag) => !selectedTags.value.some((sel) => sel.id === tag.id));

    // Cek apakah nama sudah ada di existing atau selected
    const existsInExisting = tagOptions.value.some((tag) => tag.name.toLowerCase() === query);
    const existsInSelected = selectedTags.value.some((tag) => tag.name.toLowerCase() === query);

    // Jika belum ada → tambahkan opsi "tag baru"
    if (!existsInExisting && !existsInSelected) {
        const newTag = {
            id: '',
            name: event.query.trim(),
            severity: '', // dibiarkan kosong, akan diisi addNewTag
        };

        // Pastikan tag baru muncul di urutan paling atas
        result = [newTag, ...result];
    }

    filteredTags.value = result;
};

// === ADD NEW TAG ===
const addNewTag = (event: any) => {
    const inputValue = event.target.value.trim();
    if (!inputValue) return;

    const normalized = inputValue.toLowerCase();

    // Cek apakah nama sudah ada di existing
    const existsInExisting = tagOptions.value.some((tag) => tag.name.toLowerCase() === normalized);

    // Cek apakah sudah dipilih
    const existsInSelected = selectedTags.value.some((tag) => tag.name.toLowerCase() === normalized);

    // Jika nama ada di existing → tidak membuat tag baru (pilih lewat click)
    if (existsInExisting || existsInSelected) {
        event.target.value = '';
        return;
    }

    // === Jika sampai sini, berarti user ingin membuat tag baru ===

    // random severity → nanti bisa diganti dengan mekanisme input user
    const severities = ['primary', 'secondary', 'success', 'info', 'warn', 'danger', 'contrast'];
    const randomSeverity = severities[Math.floor(Math.random() * severities.length)];

    const newTag: TagData = {
        id: '',
        name: inputValue,
        severity: randomSeverity,
    };

    selectedTags.value.push(newTag);

    event.target.value = ''; // reset input
};

const isEdit = computed(() => !!props.task);
const routeName = computed(() => (isEdit.value ? 'project.tasks.update' : 'project.tasks.store'));

const submit = () => {
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
        form.put(route(routeName.value, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Task updated', life: 3000 });
            },
        });
    } else {
        form.post(route(routeName.value, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Task added', life: 3000 });
            },
        });
    }
};

const hasChild = computed(() => {
    return Boolean(props.task && Array.isArray(props.task.children) && props.task.children.length > 0);
});

const getSelectValue = (id: string, options: TaskType[] | TaskStatus[] | TaskPriority[]) => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- TITLE -->
        <div>
            <label class="font-semibold">Title</label>
            <InputText v-model="form.title" class="w-full" placeholder="Task title" :class="{ 'p-invalid': form.errors.title }" />
            <small v-if="form.errors.title" class="p-error text-red-500">{{ form.errors.title }}</small>
        </div>

        <!-- DESCRIPTION -->
        <div>
            <label class="font-semibold">Description</label>
            <Textarea v-model="form.description" rows="4" class="w-full" :class="{ 'p-invalid': form.errors.description }" />
            <small v-if="form.errors.description" class="p-error text-red-500">{{ form.errors.description }}</small>
        </div>

        <!-- ASSIGNED MEMBERS -->
        <div class="flex flex-col">
            <label class="font-semibold">Assigned Member</label>
            <MultiSelect
                v-model="selectedMembers"
                display="chip"
                :options="formattedMemberOption"
                optionLabel="name"
                filter
                placeholder="Select Member"
                :maxSelectedLabels="3"
                class="w-full"
                :class="{ 'p-invalid': form.errors.assign_users }"
            />
            <small v-if="form.errors.assign_users" class="p-error text-red-500">{{ form.errors.assign_users }}</small>
        </div>

        <!-- START & DUE DATE -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Start Date</label>
                <DatePicker class="w-full" v-model="form.start_date" dateFormat="yy-mm-dd" showIcon :class="{ 'p-invalid': form.errors.start_date }" />
                <small v-if="form.errors.start_date" class="p-error text-red-500">{{ form.errors.start_date }}</small>
            </div>

            <div>
                <label class="font-semibold">Due Date</label>
                <DatePicker
                    class="w-full"
                    v-model="form.due_date"
                    dateFormat="yy-mm-dd"
                    showIcon
                    :minDate="minDueDate"
                    :class="{ 'p-invalid': form.errors.due_date }"
                />
                <small v-if="form.errors.due_date" class="p-error text-red-500">{{ form.errors.due_date }}</small>
            </div>
        </div>

        <!-- TYPE / STATUS / PRIORITY -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="font-semibold">Type</label>
                <Select
                    class="w-full"
                    v-model="form.type_id"
                    :options="props.taskTypes"
                    optionValue="id"
                    placeholder="Select Type"
                    :class="{ 'p-invalid': form.errors.type_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag 
                                :value="getSelectValue(slotProps.value, props.taskTypes)?.name" 
                                :severity="getSelectValue(slotProps.value, props.taskTypes)?.severity" 
                            />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex w-full">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.type_id" class="p-error text-red-500">{{ form.errors.type_id }}</small>
            </div>

            <div>
                <label class="font-semibold">Status</label>
                <Select
                    class="w-full"
                    v-model="form.status_id"
                    :options="props.taskStatuses"
                    optionValue="id"
                    placeholder="Select Status"
                    :class="{ 'p-invalid': form.errors.status_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag 
                                :value="getSelectValue(slotProps.value, props.taskStatuses)?.name" 
                                :severity="getSelectValue(slotProps.value, props.taskStatuses)?.severity" 
                            />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex w-full">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.status_id" class="p-error text-red-500">{{ form.errors.status_id }}</small>
            </div>

            <div>
                <label class="font-semibold">Priority</label>
                <Select
                    class="w-full"
                    v-model="form.priority_id"
                    :options="props.taskPriorities"
                    optionValue="id"
                    placeholder="Select Priority"
                    :class="{ 'p-invalid': form.errors.priority_id }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag 
                                :value="getSelectValue(slotProps.value, props.taskPriorities)?.name" 
                                :severity="getSelectValue(slotProps.value, props.taskPriorities)?.severity" 
                            />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex w-full">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.priority_id" class="p-error text-red-500">{{ form.errors.priority_id }}</small>
            </div>
        </div>

        <div class="flex flex-col">
            <label class="font-semibold">Tags</label>
            <AutoComplete
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
                        <span v-if="!slotProps.option.id" class="font-bold">
                            {{ slotProps.option.name }}
                        </span>
                        <span v-else>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </AutoComplete>
            <small v-if="form.errors.assign_users" class="p-error text-red-500">{{ form.errors.assign_users }}</small>
        </div>

        <!-- ARCHIVED & PROGRESS -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Archived</label>
                <Select
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
                    :disabled="hasChild"
                    :class="{ 'p-invalid': form.errors.progress }"
                />
                <small v-if="form.errors.progress" class="p-error text-red-500">{{ form.errors.progress }}</small>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" />
            <Button v-if="!isEdit" label="Create Task" @click="submit" />
            <Button v-else label="Update Task" severity="warning" @click="submit" />
        </div>

        <!-- Toast -->
        <Toast />
    </div>
</template>
