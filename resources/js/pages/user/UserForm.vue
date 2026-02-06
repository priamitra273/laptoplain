<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Role } from '@/types';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { onMounted, ref, watch } from 'vue';
import { UserForm, UserFormProps } from '.';

const props = withDefaults(defineProps<UserFormProps>(), {
    pageTitle: 'Add User',
});

const form: InertiaForm<UserForm> = useForm({
    _method: 'POST',
    name: props.user?.name ?? null,
    email: props.user?.email ?? null,
    is_active: props.user?.is_active ?? true,
    team_uuid: props.user?.team_uuid ?? null,
    role_id: props.user?.role_id ?? null,
    password: '',
    password_confirmation: '',
});

const roles = ref<Role[]>([]);

const save = () => {
    const url = props.user?.uuid ? route('user.update', props.user.uuid) : route('user.store');

    if (props.user?.uuid) form._method = 'PUT';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        },
    });
};

watch(
    () => form.team_uuid,
    () => {
        form.role_id = null;

        roles.value = props.roles.filter((item) => item.team_uuid === form.team_uuid);
    },
);

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        { debounce: 500, maxWait: 1000 },
    );
}

onMounted(() => {
    roles.value = props.roles.filter((item) => item.team_uuid === form.team_uuid);
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout>
        <form class="flex flex-col gap-6" autocomplete="off" @submit.prevent="save">
            <Card>
                <template #content>
                    <Heading title="User Information" description="Please fill the required fields." />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="name">Name</Label>
                            <InputText v-model="form.name" id="name" class="w-full" placeholder="Enter Name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="email">Email</Label>
                            <InputText v-model="form.email" id="email" class="w-full" placeholder="Enter Email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="team">Team</Label>
                            <Select
                                v-model="form.team_uuid"
                                label-id="team"
                                :options="props.teams"
                                option-value="uuid"
                                option-label="name"
                                class="w-full"
                                placeholder="Select a team"
                                filter
                            />
                            <InputError :message="form.errors.team_uuid" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="role">Role</Label>
                            <Select
                                v-model="form.role_id"
                                label-id="role"
                                :options="roles"
                                option-value="id"
                                option-label="label"
                                class="w-full"
                                placeholder="Select a role"
                                filter
                            />
                            <InputError :message="form.errors.role_id" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label class="flex cursor-pointer items-center justify-between py-2">
                                <span>Active</span>
                                <ToggleSwitch v-model="form.is_active" />
                            </Label>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <Heading title="Password Information" description="Please provide at least 8 characters." />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="password">Password</Label>
                            <Password v-model="form.password" input-id="password" class="w-full" input-class="w-full" toggle-mask />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="password_confirmation">Password Confirmation</Label>
                            <Password
                                v-model="form.password_confirmation"
                                input-id="password_confirmation"
                                class="w-full"
                                input-class="w-full"
                                :feedback="false"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-3">
                <Button label="Back" severity="secondary" @click="router.visit(route('user.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </form>
    </AppLayout>
</template>
