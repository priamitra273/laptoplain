<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { getInitials } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import { computed, inject } from 'vue';
import TeamMemberForm from './TeamMemberForm.vue';
import type { TeamMember, TeamRole, TeamUserOption } from './types';

interface Props {
    projectId: string;
    members?: TeamMember[];
    roles?: TeamRole[];
    users?: TeamUserOption[];
}

const props = withDefaults(defineProps<Props>(), {
    members: () => [],
    roles: () => [],
    users: () => [],
});

const policy = inject(ProjectPolicyKey, null);
const { canAction } = useProjectPermissions(policy);
const canManage = computed(
    () => canAction('project_member', 'create') || canAction('project_member', 'update') || canAction('project_member', 'delete'),
);

const toast = useToast();
const confirm = useConfirmDialog();
const overlay = useOverlay();
const memberForm = overlay.create(TeamMemberForm);

const columns: TableColumn<TeamMember>[] = [
    { accessorKey: 'user', header: 'User' },
    { accessorKey: 'role', header: 'Role' },
    { accessorKey: 'is_active', header: 'Status' },
    { id: 'actions' },
];

const openAdd = () => memberForm.open({ projectId: props.projectId, roles: props.roles, users: props.users });
const openEdit = (member: TeamMember) => memberForm.open({ projectId: props.projectId, roles: props.roles, member });

const handleRemove = async (member: TeamMember) => {
    const confirmed = await confirm({
        title: 'Remove Member',
        description: `Are you sure want to remove "${member.user.name}" from this project?`,
    });

    if (confirmed) {
        router.delete(route('project.members.destroy', { projectEncoded: props.projectId, memberEncoded: member.id }), {
            preserveScroll: true,
            onError: (errors) => {
                const message = Object.values(errors)[0];
                toast.add({ title: 'Failed', description: message ? String(message) : 'Could not remove member.', color: 'error' });
            },
        });
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Members</h3>
            <UButton v-if="canManage" label="Add Member" icon="i-lucide-plus" @click="openAdd" />
        </div>

        <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
            <UTable :data="members" :columns="columns">
                <template #user-cell="{ row }">
                    <div class="flex items-center gap-2">
                        <UAvatar
                            :src="row.original.user.avatar_url ?? undefined"
                            :alt="row.original.user.name"
                            :text="getInitials(row.original.user.name)"
                            size="sm"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ row.original.user.name }}</p>
                            <p class="truncate text-xs text-muted">{{ row.original.user.email }}</p>
                        </div>
                    </div>
                </template>

                <template #role-cell="{ row }">
                    {{ row.original.role.name }}
                </template>

                <template #is_active-cell="{ row }">
                    <UBadge :color="row.original.is_active ? 'success' : 'neutral'" variant="subtle">
                        {{ row.original.is_active ? 'Active' : 'Inactive' }}
                    </UBadge>
                </template>

                <template #actions-cell="{ row }">
                    <div v-if="canManage" class="flex justify-end gap-0.5">
                        <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" size="xs" @click="openEdit(row.original)" />
                        <UButton icon="i-lucide-trash" color="error" variant="ghost" size="xs" @click="handleRemove(row.original)" />
                    </div>
                </template>

                <template #empty>
                    <p class="text-center text-sm text-muted">No members yet.</p>
                </template>
            </UTable>
        </UCard>
    </div>
</template>
