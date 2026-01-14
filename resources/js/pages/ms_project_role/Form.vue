<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { ProjectRole } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { useToast } from 'primevue/usetoast';
import { computed } from 'vue';

interface Props {
    value?: ProjectRole;
    visible: boolean;
}

interface ProjectRoleForm {
    _method: 'POST' | 'PUT';
    name: string;
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

const formHeader = computed(() => (props.value?.id ? 'Edit Project Role' : 'Create New Project Role'));

const form: InertiaForm<ProjectRoleForm> = useForm({
    _method: 'POST',
    name: '',
});

const toast = useToast();

const save = () => {
    const url = props.value?.id ? route('project-role.update', props.value.id) : route('project-role.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    const isUpdate = props.value?.id ? true : false;

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => { visible.value = false },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save project role', life: 3000 });
        },
    });
};

const hide = () => {
    form.reset();
    form.clearErrors();
};

const show = () => {
    form.name = props.value?.name ?? '';
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
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter project role name" />
                <InputError :message="form.errors.name" />
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
