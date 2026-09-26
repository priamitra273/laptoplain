<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Role, Team, UserList } from '@/types';
import { Head, Link, useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

interface Props {
    pageTitle?: string;
    user?: UserList;
    teams: Team[];
    roles: Role[];
}

interface UserFormData {
    _method: string;
    name: string;
    email: string;
    team_uuid?: string;
    role_id?: number;
    is_active: boolean;
    password: string;
    password_confirmation: string;
    [key: string]: any;
}

const props = withDefaults(defineProps<Props>(), {
    pageTitle: 'Add User',
});

const form: InertiaForm<UserFormData> = useForm({
    _method: props.user?.uuid ? 'PUT' : 'POST',
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    team_uuid: props.user?.team_uuid,
    role_id: props.user?.role_id,
    is_active: props.user?.is_active ?? true,
    password: '',
    password_confirmation: '',
});

const teamRoles = computed(() => props.roles.filter((role) => role.team_uuid === form.team_uuid));

watch(
    () => form.team_uuid,
    () => {
        form.role_id = undefined;
    },
);

const showPassword = ref(false);

const save = (): void => {
    form.post(props.user?.uuid ? route('user.update', props.user.uuid) : route('user.store'), {
        preserveScroll: true,
    });
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        {
            debounce: 500,
            maxWait: 1000,
        },
    );
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :title="pageTitle">
        <form class="flex flex-col gap-6" autocomplete="off" @submit.prevent="save">
            <UCard title="User Information" description="Please fill the required fields." :ui="{ body: 'sm:py-0' }">
                <div class="grid gap-6 md:grid-cols-2">
                    <UFormField label="Name" name="name" required :error="form.errors.name"
                        ><UInput v-model="form.name" placeholder="Enter Name" class="w-full"
                    /></UFormField>

                    <UFormField label="Email" name="email" required :error="form.errors.email"
                        ><UInput v-model="form.email" type="email" placeholder="Enter Email" autocomplete="off" class="w-full"
                    /></UFormField>

                    <UFormField label="Team" name="team_uuid" required :error="form.errors.team_uuid">
                        <USelectMenu
                            v-model="form.team_uuid"
                            :items="teams"
                            label-key="name"
                            value-key="uuid"
                            placeholder="Select a team"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Role" name="role_id" required :error="form.errors.role_id">
                        <USelectMenu
                            v-model="form.role_id"
                            :items="teamRoles"
                            label-key="label"
                            value-key="id"
                            :disabled="!form.team_uuid"
                            placeholder="Select a role"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Active" orientation="horizontal" class="items-center py-2">
                        <USwitch v-model="form.is_active" />
                    </UFormField>
                </div>
            </UCard>

            <UCard title="Password Information" description="Please provide at least 8 characters." :ui="{ body: 'sm:py-0' }">
                <div class="grid gap-6 md:grid-cols-2">
                    <UFormField label="Password" name="password" :required="!props.user?.uuid" :error="form.errors.password">
                        <UInput
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Password"
                            autocomplete="new-password"
                            class="w-full"
                        >
                            <template #trailing>
                                <UButton
                                    color="neutral"
                                    variant="link"
                                    size="sm"
                                    :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                />
                            </template>
                        </UInput>
                    </UFormField>

                    <UFormField label="Password Confirmation" name="password_confirmation" :error="form.errors.password_confirmation">
                        <UInput
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Password Confirmation"
                            autocomplete="new-password"
                            class="w-full"
                        />
                    </UFormField>
                </div>
            </UCard>

            <div class="flex justify-end gap-3">
                <Link :href="route('user.index')">
                    <UButton label="Back" color="neutral" variant="outline" />
                </Link>
                <UButton label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
