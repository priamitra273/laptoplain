<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { ProjectPriority } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { computed } from 'vue';

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
const emits = defineEmits<{ (event: 'update:visible', value: boolean): void }>();

const visible = computed({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const formHeader = computed(() =>
    props.value?.id ? 'Edit Project Priority' : 'Create New Project Priority'
);

const form: InertiaForm<ProjectPriorityForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const save = () => {
    const url = props.value?.id
        ? route('project-priority.update', props.value.id)
        : route('project-priority.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Success', 'Data has been saved successfully', 'success');
            visible.value = false;
        },
    });
};

const hide = () => {
    form.reset();
    form.clearErrors();
};

const show = () => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => delete form.errors[key],
        { debounce: 400, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer
        v-model:visible="visible"
        class="!w-full md:!w-[40vw]"
        position="right"
        :header="formHeader"
        @show="show"
        @after-hide="hide"
    >
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Priority Name" />
                <InputError :message="form.errors.name" />
            </div>

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
