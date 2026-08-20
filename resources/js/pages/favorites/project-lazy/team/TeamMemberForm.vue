<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { TeamMember, TeamRole, TeamUserOption } from './types';

interface Props {
    projectId: string;
    roles?: TeamRole[];
    users?: TeamUserOption[];
    member?: TeamMember | null;
}

const props = withDefaults(defineProps<Props>(), {
    roles: () => [],
    users: () => [],
    member: null,
});

const emits = defineEmits<{ close: [boolean] }>();

const isEdit = computed(() => !!props.member);

interface AddFormData {
    user_id?: string;
    project_role_id?: string;
    [key: string]: any;
}

interface EditFormData {
    project_role_id?: string;
    is_active: boolean;
    [key: string]: any;
}

const addForm = useForm<AddFormData>({ user_id: undefined, project_role_id: undefined });
const editForm = useForm<EditFormData>({
    project_role_id: props.member?.role.id,
    is_active: props.member?.is_active ?? true,
});

const submit = () => {
    if (isEdit.value && props.member) {
        editForm.put(route('project.members.update', { projectEncoded: props.projectId, memberEncoded: props.member.id }), {
            onSuccess: () => emits('close', true),
        });
    } else {
        addForm.post(route('project.members.store', { projectEncoded: props.projectId }), {
            onSuccess: () => emits('close', true),
        });
    }
};
</script>

<template>
    <UModal :title="isEdit ? 'Edit Member' : 'Add Member'" :ui="{ footer: 'justify-end' }">
        <template #body>
            <div class="flex flex-col gap-4">
                <template v-if="!isEdit">
                    <div class="flex flex-col gap-2">
                        <Label value="User" required />
                        <USelectMenu
                            v-model="addForm.user_id"
                            :items="users"
                            label-key="name"
                            value-key="id"
                            placeholder="Select user"
                            class="w-full"
                        >
                            <template #item-leading="{ item }">
                                <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" size="xs" />
                            </template>
                        </USelectMenu>
                        <InputError v-if="addForm.errors.user_id" :message="addForm.errors.user_id" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Role" required />
                        <USelectMenu
                            v-model="addForm.project_role_id"
                            :items="roles"
                            label-key="name"
                            value-key="id"
                            placeholder="Select role"
                            class="w-full"
                        />
                        <InputError v-if="addForm.errors.project_role_id" :message="addForm.errors.project_role_id" />
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-col gap-2">
                        <Label value="Role" required />
                        <USelectMenu
                            v-model="editForm.project_role_id"
                            :items="roles"
                            label-key="name"
                            value-key="id"
                            placeholder="Select role"
                            class="w-full"
                        />
                        <InputError v-if="editForm.errors.project_role_id" :message="editForm.errors.project_role_id" />
                    </div>

                    <div class="flex items-center justify-between gap-2 py-2">
                        <Label value="Active" />
                        <USwitch v-model="editForm.is_active" />
                    </div>
                </template>
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', false)" />
            <UButton
                label="Save"
                :loading="isEdit ? editForm.processing : addForm.processing"
                :disabled="isEdit ? editForm.processing : addForm.processing"
                @click="submit"
            />
        </template>
    </UModal>
</template>
