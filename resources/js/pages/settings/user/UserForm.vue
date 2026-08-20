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
                    <div class="flex flex-col gap-2">
                        <Label value="Name" required />
                        <UInput v-model="form.name" placeholder="Enter Name" />
                        <InputError v-if="form.errors.name" :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Email" required />
                        <UInput v-model="form.email" type="email" placeholder="Enter Email" autocomplete="off" />
                        <InputError v-if="form.errors.email" :message="form.errors.email" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Team" required />
                        <USelectMenu
                            v-model="form.team_uuid"
                            :items="teams"
                            label-key="name"
                            value-key="uuid"
                            placeholder="Select a team"
                            class="w-full"
                        />
                        <InputError v-if="form.errors.team_uuid" :message="form.errors.team_uuid" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Role" required />
                        <USelectMenu
                            v-model="form.role_id"
                            :items="teamRoles"
                            label-key="label"
                            value-key="id"
                            :disabled="!form.team_uuid"
                            placeholder="Select a role"
                            class="w-full"
                        />
                        <InputError v-if="form.errors.role_id" :message="form.errors.role_id" />
                    </div>

                    <div class="flex items-center justify-between gap-2 py-2">
                        <Label value="Active" />
                        <USwitch v-model="form.is_active" />
                    </div>
                </div>
            </UCard>

            <UCard title="Password Information" description="Please provide at least 8 characters." :ui="{ body: 'sm:py-0' }">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label value="Password" :required="!props.user?.uuid" />
                        <UInput v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Password" autocomplete="new-password">
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
                        <InputError v-if="form.errors.password" :message="form.errors.password" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Password Confirmation" />
                        <UInput
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Password Confirmation"
                            autocomplete="new-password"
                        />
                        <InputError v-if="form.errors.password_confirmation" :message="form.errors.password_confirmation" />
                    </div>
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
