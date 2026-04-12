<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { ProjectRoleOption } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import type { ProjectMember } from '..';

interface Props {
    projectId: string;
    member: ProjectMember;
    roles: ProjectRoleOption[];
}

interface Form {
    _method: 'PUT';
    project_role_id: string | null;
    is_active: boolean;
    [key: string]: any;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);
const toast = useToast();

const statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false },
];

const form: InertiaForm<Form> = useForm({
    _method: 'PUT',
    project_role_id: props.member.role.id,
    is_active: props.member.is_active,
});

const save = () => {
    form.put(route('project.members.update', { projectEncoded: props.projectId, memberEncoded: props.member.id }), {
        onSuccess: () => {
            emit('saved');
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update member', life: 3000 });
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <Select v-model="form.project_role_id" :options="props.roles" optionLabel="name" optionValue="id" />
        <InputError :message="form.errors.project_role_id" />

        <Select v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value" />
        <InputError :message="form.errors.is_active" />

        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" :disabled="form.processing" />
            <Button label="Save" icon="pi pi-check" @click="save" :disabled="form.processing" :loading="form.processing" />
        </div>
    </div>
</template>
