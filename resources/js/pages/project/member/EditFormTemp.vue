<script setup lang="ts">
import { InertiaForm, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Swal from 'sweetalert2';
import type { ProjectMember } from '..';

interface Props {
    projectId: string;
    member: ProjectMember;
    roles: { id: string; name: string }[];
}

interface Form {
    _method: 'PUT';
    project_role_id: string | null;
    is_active: boolean;
    [key: string]: any;
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);

const form: InertiaForm<Form> = useForm({
    _method: 'PUT',
    project_role_id: props.member.role.id,
    is_active: props.member.is_active,
});

const save = () => {
    form.put(route('project.members.update', { projectEncoded: props.projectId, memberEncoded: props.member.id }), {
        onSuccess: () => {
            emit('saved');
            Swal.fire('Success', 'Member updated', 'success');
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <Dropdown v-model="form.project_role_id" :options="props.roles" optionLabel="name" optionValue="id" />

        <Dropdown
            v-model="form.is_active"
            :options="[
                { label: 'Active', value: true },
                { label: 'Inactive', value: false },
            ]"
            optionLabel="label"
            optionValue="value"
        />

        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" />
            <Button label="Save" icon="pi pi-check" @click="save" />
        </div>
    </div>
</template>
