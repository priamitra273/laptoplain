<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Role, Team } from '@/types';
import { Head, Link, useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import PermissionMatrix from './PermissionMatrix.vue';
import type { MenuNode, MenuPermission } from './permissions';

interface Props {
    pageTitle?: string;
    role?: Role;
    teams: Team[];
    menu: MenuNode[];
    menu_permissions: MenuPermission[];
}

interface RoleFormData {
    _method: string;
    team_uuid?: string;
    label: string;
    is_active: boolean;
    permissions: string[];
    [key: string]: any;
}

const props = withDefaults(defineProps<Props>(), {
    pageTitle: 'Add Role',
});

const form: InertiaForm<RoleFormData> = useForm({
    _method: props.role?.id ? 'PUT' : 'POST',
    team_uuid: props.role?.team_uuid,
    label: props.role?.label ?? '',
    is_active: props.role?.is_active ?? false,
    permissions: props.role?.permissions ?? [],
});

const save = (): void => {
    form.post(props.role?.id ? route('role.update', props.role.id) : route('role.store'), {
        preserveScroll: true,
    });
};

// watching form changes
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
        <form class="flex flex-col gap-6" @submit.prevent="save">
            <UCard title="Role Information" description="Please fill the required fields." :ui="{ body: 'sm:py-0' }">
                <div class="grid gap-6 md:grid-cols-2">
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

                    <UFormField label="Role Name" name="label" required :error="form.errors.label">
                        <UInput v-model="form.label" placeholder="Role Name" class="w-full" />
                    </UFormField>

                    <UFormField label="Active">
                        <USwitch v-model="form.is_active" :label="form.is_active ? 'Active' : 'Nonactive'" class="py-2" />
                    </UFormField>
                </div>
            </UCard>

            <UFormField name="permissions" :error="form.errors.permissions" class="flex flex-col gap-4">
                <PermissionMatrix v-model="form.permissions" :menu="menu" :menu-permissions="menu_permissions" />
            </UFormField>

            <div class="flex justify-end gap-3">
                <Link :href="route('role.index')">
                    <UButton label="Back" color="neutral" variant="outline" />
                </Link>
                <UButton label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
