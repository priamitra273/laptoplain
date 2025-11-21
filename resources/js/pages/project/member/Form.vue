<script setup lang="ts">
import { ref } from 'vue';
import { InertiaForm, router, useForm } from '@inertiajs/vue3';
import Dropdown from 'primevue/dropdown';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import InputError from '@/components/InputError.vue';
import Swal from 'sweetalert2';

interface Props {
    projectId: string
    users: { id: string; name: string }[]
    roles: { id: string; name: string }[]
}

interface Form {
    _method: 'POST'
    user_id: string | null
    project_role_id: string | null
    [key: string]: any
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);

const filteredUsers = ref(props.users);
const selectedUser = ref(null);

const form : InertiaForm<Form> = useForm({
    _method: 'POST',
    user_id: null,
    project_role_id: null,
});

const searchUser = (event: { query: string }) => {
    const query = event.query.toLowerCase();
    filteredUsers.value = props.users.filter(u =>
        u.name.toLowerCase().includes(query)
    );
};

const onSelect = (value: any) => {
    form.user_id = value.id;
};

const save = () => {
    form.post(route('project.members.store', { projectEncoded: props.projectId }), {
        onSuccess: () => {
            emit('saved')
            Swal.fire('Success', 'Member added', 'success');
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <AutoComplete
            v-model="selectedUser"
            :suggestions="filteredUsers"
            optionLabel="name"
            @complete="searchUser"
            @update:modelValue="onSelect"
            placeholder="Select user"
            inputClass="w-full"
            dropdown
        />
        <InputError :message="form.errors?.user_id" />

        <Dropdown 
            v-model="form.project_role_id" 
            :options="props.roles" 
            optionLabel="name" 
            optionValue="id" 
            placeholder="Select role" 
        />
        <InputError :message="form.errors?.project_role_id" />

        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" />
            <Button label="Save" icon="pi pi-check" @click="save" />
        </div>
    </div>
</template>
