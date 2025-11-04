<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { MsProjectStatus } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { computed } from 'vue';

interface Props {
    value?: MsProjectStatus;
    visible: boolean;
}

interface ProjectStatusForm {
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
    return props.value?.id ? 'Edit Project Status' : 'Create New Project Status';
});

const form: InertiaForm<ProjectStatusForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const save = (): void => {
    const url = props.value?.id ? route('ms_project_status.update', props.value.id) : route('ms_project_status.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
            visible.value = false;
        },
    });
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
    form._method = 'POST';
};

const show = (): void => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
};

// Clear error messages as user types
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        { debounce: 500, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid grid-cols-1 gap-6" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <Label for="name">Status Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Status Name" />
                <InputError :message="form.errors.name" v-if="form.errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="severity">Severity</Label>
                <InputText v-model="form.severity" id="severity" placeholder="Enter Severity" />
                <InputError :message="form.errors.severity" v-if="form.errors.severity" />
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
