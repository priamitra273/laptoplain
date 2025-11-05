<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { ProjectPriority } from '@/types';
import { InertiaForm, useForm, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { computed, watch } from 'vue';

interface Props {
    value?: ProjectPriority;
    visible: boolean;
}

interface ProjectPriorityForm {
    _method: 'POST' | 'PUT';
    name: string;
    severity: string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const formHeader = computed(() => {
    return props.value?.id ? 'Edit Project Priority' : 'Create New Project Priority';
});

const form: InertiaForm<ProjectPriorityForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const page = usePage();
watch(
    () => page.props.flash.success,
    (msg) => {
        if (msg) Swal.fire('Success', msg as string, 'success');
    },
);
watch(
    () => page.props.flash.error,
    (msg) => {
        if (msg) Swal.fire('Error', msg as string, 'error');
    },
);

const show = (): void => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
    form._method = 'POST';
};

const save = (): void => {
    const isEdit = !!props.value?.id;
    const url = isEdit ? route('project-priority.update', props.value.id) : route('project-priority.store');

    form._method = isEdit ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            visible.value = false;
            form.reset();
        },
        onError(errors) {
            // error dari controller langsung tampil di form.errors
            console.error('Validation Errors:', errors);
            Swal.fire('Validation Error', 'Please check the highlighted fields.', 'error');
        },
        onFinish() {
            form.processing = false;
        },
    });
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => delete form.errors[key],
        { debounce: 500, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <!-- Name -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Project Name" />
                <InputError :message="form.errors.name" />
            </div>

            <!-- Severity -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="severity">Severity</Label>
                <InputText v-model="form.severity" id="severity" placeholder="Enter Severity" />
                <InputError :message="form.errors.severity" />
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
