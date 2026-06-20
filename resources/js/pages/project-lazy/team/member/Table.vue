<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { RoleOption, SlimUser, TeamMember } from '@/pages/project-lazy';

interface Props {
    projectId: string;
    members: TeamMember[];
    roles: RoleOption[];
    users: SlimUser[];
    hasPermission: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'add'): void;
    (e: 'edit', member: TeamMember): void;
}>();

const confirm = useConfirm();
const toast = useToast();

const remove = (member: TeamMember) => {
    confirm.require({
        message: `Are you sure you want to remove ${member.user.name}?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, remove',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        rejectClass: 'p-button-text',
        accept: () => {
            router.delete(
                route('project.members.destroy', {
                    projectEncoded: props.projectId,
                    memberEncoded: member.id,
                }),
                {
                    preserveScroll: true,
                    onError: () => {
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete member', life: 3000 });
                    },
                },
            );
        },
    });
};
</script>

<template>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Members</h3>
        <Button v-if="props.hasPermission" label="Add Member" icon="pi pi-plus" @click="emit('add')" />
    </div>

    <div class="w-full overflow-x-auto">
        <DataTable :value="props.members" class="w-full min-w-[600px]">
            <Column header="#" class="w-10 text-center">
                <template #body="{ index }">{{ index + 1 }}</template>
            </Column>

            <Column header="User">
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ data.user?.name }}</span>
                        <span class="text-xs opacity-70">{{ data.user?.email }}</span>
                    </div>
                </template>
            </Column>

            <Column header="Role">
                <template #body="{ data }">{{ data.role?.name || '-' }}</template>
            </Column>

            <Column header="Status" class="w-24 text-center">
                <template #body="{ data }">
                    <span
                        class="rounded px-2 py-1 text-xs"
                        :class="data.is_active ? 'bg-green-200 dark:bg-green-900/40' : 'bg-gray-200 dark:bg-gray-700'"
                    >
                        {{ data.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </template>
            </Column>

            <Column v-if="props.hasPermission" header="Action" class="w-28 text-center">
                <template #body="{ data }">
                    <div class="flex items-center justify-center gap-1">
                        <Button icon="pi pi-pencil" size="small" text @click="emit('edit', data)" />
                        <Button icon="pi pi-trash" size="small" text severity="danger" @click="remove(data)" />
                    </div>
                </template>
            </Column>

            <template #empty>
                <p class="py-6 text-center">No members found</p>
            </template>
        </DataTable>
    </div>
</template>
