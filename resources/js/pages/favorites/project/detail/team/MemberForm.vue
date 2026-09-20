<script setup lang="ts">
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AssignableUser, RoleOption, TeamMember } from './types';

interface Props {
    projectId: string;
    roles: RoleOption[];
    users: AssignableUser[];
    value?: TeamMember;
}

interface MemberFormData {
    _method: string;
    user_id: string | null;
    project_role_id: string | null;
    is_active: boolean;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ close: [boolean] }>();

const isEdit = computed(() => !!props.value);
const title = computed(() => (isEdit.value ? 'Edit Member' : 'Add Member'));

const userItems = computed(() => props.users.map((user) => ({ id: user.id, label: user.name, email: user.email })));
const roleItems = computed(() => props.roles.map((role) => ({ id: role.id, label: role.name })));

const userIdModel = computed({
    get: () => form.user_id ?? undefined,
    set: (value?: string) => {
        form.user_id = value ?? null;
    },
});

const roleIdModel = computed({
    get: () => form.project_role_id ?? undefined,
    set: (value?: string) => {
        form.project_role_id = value ?? null;
    },
});

const form: InertiaForm<MemberFormData> = useForm<MemberFormData>({
    _method: 'POST',
    user_id: null,
    project_role_id: null,
    is_active: true,
});

const open = () => {
    form.user_id = props.value?.user.id ?? null;
    form.project_role_id = props.value?.role.id ?? null;
    form.is_active = props.value?.is_active ?? true;
};

const save = () => {
    if (isEdit.value) {
        form._method = 'PUT';
        form.post(route('project.members.update', { projectEncoded: props.projectId, memberEncoded: props.value!.id }), {
            preserveScroll: true,
            onSuccess: () => emits('close', true),
        });

        return;
    }

    form._method = 'POST';
    form.post(route('project.members.store', { projectEncoded: props.projectId }), {
        preserveScroll: true,
        onSuccess: () => emits('close', true),
    });
};
</script>

<template>
    <UModal :title="title" @enter="open">
        <template #body>
            <div class="grid gap-6">
                <UFormField v-if="!isEdit" name="user_id" label="User" required :error="form.errors.user_id">
                    <USelectMenu
                        v-model="userIdModel"
                        :items="userItems"
                        value-key="id"
                        label-key="label"
                        searchable
                        placeholder="Select a user"
                        class="w-full"
                    >
                        <template #item-label="{ item }">
                            <div class="flex flex-col">
                                <span>{{ item.label }}</span>
                                <span class="text-xs text-muted">{{ item.email }}</span>
                            </div>
                        </template>
                    </USelectMenu>
                </UFormField>

                <UFormField name="project_role_id" label="Role" required :error="form.errors.project_role_id">
                    <USelectMenu
                        v-model="roleIdModel"
                        :items="roleItems"
                        value-key="id"
                        label-key="label"
                        placeholder="Select a role"
                        class="w-full"
                    />
                </UFormField>

                <UFormField v-if="isEdit" name="is_active" :error="form.errors.is_active">
                    <USwitch v-model="form.is_active" label="Active" />
                </UFormField>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </UModal>
</template>
