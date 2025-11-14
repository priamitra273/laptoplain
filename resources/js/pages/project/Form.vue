<script setup lang="ts">
import Label from '@/components/ui/label/Label.vue';
import { useForm } from '@inertiajs/vue3';
import moment from 'moment';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dropdown from 'primevue/dropdown';
import Editor from 'primevue/editor';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import { computed, watch as vueWatch } from 'vue';

interface Props {
    value?: any;
    visible: boolean;
    statuses: { id: number; name: string }[];
    priorities: { id: number; name: string }[];
}

interface ProjectForm {
    title: string;
    start_date: Date | null;
    due_date: Date | null;
    description: string;
    emoji: string | null;
    status_id: number | null;
    priority_id: number | null;
    owner_id?: number | null;
    owned_id?: number | null;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (e: 'update:visible', value: boolean): void }>();
const toast = useToast();

const formHeader = computed(() => (props.value?.id ? 'Edit Project' : 'Create New Project'));

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(value) {
        emits('update:visible', value);
    },
});

const form = useForm<ProjectForm>({
    title: '',
    start_date: null,
    due_date: null,
    description: '',
    emoji: '',
    status_id: null,
    priority_id: null,
    owner_id: null,
    owned_id: null,
});

const save = (): void => {
    const isEdit = !!props.value?.id;
    const url = isEdit ? route('project.update', props.value.id) : route('project.store');

    form.transform((data) => ({
        ...data,
        start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
        due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
    }));

    const successMessage = isEdit ? 'Project updated successfully.' : 'Project created successfully.';

    const onSuccess = () => {
        visible.value = false;
        hide(); // ✅ pastikan form direset setelah sukses
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: successMessage,
            life: 3000,
        });
    };

    if (isEdit) {
        form.put(url, { preserveScroll: true, onSuccess });
    } else {
        form.post(url, { preserveScroll: true, onSuccess });
    }
};

const show = (): void => {
    form.reset();
    form.clearErrors();

    if (props.value) {
        form.title = props.value.title ?? '';
        form.description = props.value.description ?? '';
        form.emoji = props.value.emoji ?? '';
        form.status_id = props.value.status_id ?? props.value.status?.id ?? null;
        form.priority_id = props.value.priority_id ?? props.value.priority?.id ?? null;
        form.owner_id = props.value.owner_id ?? null;
        form.owned_id = props.value.owned_id ?? null;
        form.start_date = props.value.start_date ? moment(props.value.start_date).toDate() : null;
        form.due_date = props.value.due_date ? moment(props.value.due_date).toDate() : null;
    }
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
};

vueWatch(
    () => form.data(),
    () => {
        Object.keys(form.errors).forEach((key) => {
            if (form[key] !== undefined) delete form.errors[key];
        });
    },
    { deep: true },
);
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="title">Project Title</Label>
                <InputText v-model="form.title" id="title" placeholder="Enter Project Title" fluid />
                <small v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</small>
            </div>

            <!-- Dates -->
            <div class="flex flex-col gap-2">
                <Label for="start_date">Start Date</Label>
                <DatePicker v-model="form.start_date" input-id="start_date" show-icon fluid date-format="yy-mm-dd" />
                <small v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">{{ form.errors.start_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="due_date">Due Date</Label>
                <DatePicker
                    v-model="form.due_date"
                    input-id="due_date"
                    show-icon
                    fluid
                    date-format="yy-mm-dd"
                    :min-date="form.start_date ?? undefined"
                />
                <small v-if="form.errors.due_date" class="mt-1 text-sm text-red-500">{{ form.errors.due_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="status_id">Status</Label>
                <Dropdown
                    v-model="form.status_id"
                    :options="props.statuses"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Status"
                    class="w-full"
                />
                <small v-if="form.errors.status_id" class="mt-1 text-sm text-red-500">{{ form.errors.status_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="priority_id">Priority</Label>
                <Dropdown
                    v-model="form.priority_id"
                    :options="props.priorities"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Priority"
                    class="w-full"
                />
                <small v-if="form.errors.priority_id" class="mt-1 text-sm text-red-500">{{ form.errors.priority_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="emoji">Emoji</Label>
                <InputText v-model="form.emoji" id="emoji" placeholder="e.g. 🚀" maxlength="2" />
                <small v-if="form.errors.emoji" class="mt-1 text-sm text-red-500">{{ form.errors.emoji }}</small>
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="description">Description</Label>
                <Editor v-model="form.description" editorStyle="height: 200px" />
                <small v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</small>
            </div>

            <!-- Buttons -->
            <div class="col-span-2 flex justify-end gap-2">
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="
                        () => {
                            visible = false;
                            hide();
                        }
                    "
                />
                <Button label="Save" type="submit" :loading="form.processing" :disabled="form.processing" />
            </div>
        </form>
    </Drawer>
</template>
