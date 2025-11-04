<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import moment from 'moment';
import Swal from 'sweetalert2';
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
    description: string | null;
    emoji: string | null;
    status_id: number | null;
    priority_id: number | null;
    owner_id?: number | null;
    owned_id?: number | null;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (e: 'update:visible', value: boolean): void }>();

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(value) {
        emits('update:visible', value);
    },
});

const formHeader = computed(() => (props.value?.id ? 'Edit Project' : 'Create New Project'));

const form: InertiaForm<ProjectForm> = useForm({
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

    const payload = form.transform((data) => ({
        ...data,
        start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
        due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
    }));

    const successMessage = isEdit ? 'Project successfully updated!' : 'Project successfully created!';

    if (isEdit) {
        payload.put(url, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Success', successMessage, 'success');
                visible.value = false;
            },
            onError: () => {
                Swal.fire('Error', 'Please fix the errors below.', 'error');
            },
        });
    } else {
        payload.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Success', successMessage, 'success');
                visible.value = false;
            },
            onError: () => {
                Swal.fire('Error', 'Please fix the errors below.', 'error');
            },
        });
    }
};

const show = (): void => {
    form.reset();
    form.clearErrors();

    if (props.value) {
        form.title = props.value.title ?? '';
        form.description = props.value.description ?? '';
        form.emoji = props.value.emoji ?? '';
        form.status_id = props.value.status_id ?? null;
        form.priority_id = props.value.priority_id ?? null;
        form.owner_id = props.value.owner_id ?? null;
        form.owned_id = props.value.owned_id ?? null;
        form.start_date = props.value.start_date ? moment(props.value.start_date, 'YYYY-MM-DD', true).toDate() : null;
        form.due_date = props.value.due_date ? moment(props.value.due_date, 'YYYY-MM-DD', true).toDate() : null;
    }
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
};

// Clear errors when input changes
vueWatch(
    () => form,
    () => {
        Object.keys(form.errors).forEach((key) => {
            if (form[key] !== undefined) {
                delete form.errors[key];
            }
        });
    },
    { deep: true },
);
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <!-- Title -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="title">Project Title</Label>
                <InputText v-model="form.title" id="title" placeholder="Enter Project Title" />
                <InputError :message="form.errors.title" />
            </div>

            <!-- Start Date -->
            <div class="flex flex-col gap-2">
                <Label for="start_date">Start Date</Label>
                <DatePicker v-model="form.start_date" input-id="start_date" show-icon fluid date-format="yy-mm-dd" placeholder="Enter Start Date" />
                <InputError :message="form.errors.start_date" />
            </div>

            <!-- Due Date -->
            <div class="flex flex-col gap-2">
                <Label for="due_date">Due Date</Label>
                <DatePicker
                    v-model="form.due_date"
                    input-id="due_date"
                    show-icon
                    fluid
                    date-format="yy-mm-dd"
                    :min-date="form.start_date ?? undefined"
                    placeholder="Enter Due Date"
                />
                <InputError :message="form.errors.due_date" />
            </div>

            <!-- Status -->
            <div class="flex flex-col gap-2">
                <Label for="status_id">Status</Label>
                <Dropdown v-model="form.status_id" :options="props.statuses" optionLabel="name" optionValue="id" placeholder="Select Status" />
                <InputError :message="form.errors.status_id" />
            </div>

            <!-- Priority -->
            <div class="flex flex-col gap-2">
                <Label for="priority_id">Priority</Label>
                <Dropdown v-model="form.priority_id" :options="props.priorities" optionLabel="name" optionValue="id" placeholder="Select Priority" />
                <InputError :message="form.errors.priority_id" />
            </div>

            <!-- Emoji -->
            <div class="flex flex-col gap-2">
                <Label for="emoji">Emoji</Label>
                <InputText v-model="form.emoji" id="emoji" placeholder="e.g. 🚀" maxlength="2" />
                <InputError :message="form.errors.emoji" />
            </div>

            <!-- Description -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="description">Description</Label>
                <Textarea v-model="form.description" id="description" placeholder="Enter Project Description" rows="4" />
                <InputError :message="form.errors.description" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>
