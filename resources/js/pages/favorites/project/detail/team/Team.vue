<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { getInitials } from '@/lib/utils';
import { Head, router } from '@inertiajs/vue3';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import { computed } from 'vue';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import MemberForm from './MemberForm.vue';
import type { AssignableUser, RoleOption, TeamMember } from './types';

interface Props extends ShellProps {
    members: TeamMember[];
    roles: RoleOption[];
    users: AssignableUser[];
}

const props = defineProps<Props>();

const { canAction } = useProjectPermissions(props.policy);

const canCreate = computed(() => canAction('project_member', 'create'));
const canUpdate = computed(() => canAction('project_member', 'update'));
const canDelete = computed(() => canAction('project_member', 'delete'));

const overlay = useOverlay();
const confirm = useConfirmDialog();

const memberForm = overlay.create(MemberForm);

const addMember = () => {
    memberForm.open({ projectId: props.project.id, roles: props.roles, users: props.users });
};

const editMember = (member: TeamMember) => {
    memberForm.open({ projectId: props.project.id, roles: props.roles, users: props.users, value: member });
};

const removeMember = async (member: TeamMember) => {
    const confirmed = await confirm({
        title: 'Remove Member',
        description: `Are you sure you want to remove ${member.user.name} from this project?`,
    });

    if (confirmed) {
        router.delete(route('project.members.destroy', { projectEncoded: props.project.id, memberEncoded: member.id }), {
            preserveScroll: true,
        });
    }
};

const columns: TableColumn<TeamMember>[] = [
    { id: 'user', header: 'User' },
    { id: 'role', header: 'Role' },
    { id: 'status', header: 'Status' },
    { id: 'actions' },
];

const getDropdownActions = (member: TeamMember): DropdownMenuItem[] => {
    const items: DropdownMenuItem[] = [];

    if (canUpdate.value) {
        items.push({ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => editMember(member) });
    }

    if (canDelete.value) {
        items.push({ label: 'Remove', icon: 'i-lucide-trash', color: 'error', onSelect: () => removeMember(member) });
    }

    return items;
};
</script>

<template>
    <Head :title="`Team - ${project.title}`" />

    <ProjectShellLayout>
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">Members</h3>
            <UButton v-if="canCreate" label="Add Member" icon="i-lucide-plus" size="sm" @click="addMember" />
        </div>

        <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
            <UTable :data="members" :columns="columns">
                <template #user-cell="{ row }">
                    <div class="flex items-center gap-2">
                        <UAvatar :src="row.original.user.avatar_url ?? undefined" :alt="row.original.user.name" :text="getInitials(row.original.user.name)" size="sm" />
                        <div class="flex flex-col">
                            <span class="font-medium">{{ row.original.user.name }}</span>
                            <span class="text-xs text-muted">{{ row.original.user.email }}</span>
                        </div>
                    </div>
                </template>

                <template #role-cell="{ row }">
                    <UBadge color="neutral" variant="subtle">{{ row.original.role.name }}</UBadge>
                </template>

                <template #status-cell="{ row }">
                    <UBadge :color="row.original.is_active ? 'success' : 'neutral'" variant="subtle">
                        {{ row.original.is_active ? 'Active' : 'Inactive' }}
                    </UBadge>
                </template>

                <template #actions-cell="{ row }">
                    <div v-if="canUpdate || canDelete" class="flex justify-end">
                        <UDropdownMenu :items="getDropdownActions(row.original)" :content="{ align: 'end', side: 'bottom' }">
                            <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" aria-label="Actions" />
                        </UDropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <EmptyState icon="i-lucide-users" title="No members yet." />
                </template>
            </UTable>
        </UCard>
    </ProjectShellLayout>
</template>
