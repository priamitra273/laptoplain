<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { ProjectMember } from '..';

interface Props {
    projectId: string;
    members: ProjectMember[];
    roles: { id: string; name: string }[];
    users: { id: string; name: string }[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'add'): void;
    (e: 'edit', member: ProjectMember): void;
}>();

const confirm = useConfirm();
const toast = useToast();

const remove = (member: ProjectMember) => {
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
                    onSuccess: () => {
                        toast.add({
                            severity: 'success',
                            summary: 'Success',
                            detail: 'Member removed successfully',
                            life: 2000,
                        });
                    },
                },
            );
        },
    });
};
</script>

<template>
    <!-- ConfirmDialog Global -->
    <ConfirmDialog />

    <!-- Toast -->
    <Toast />

    <div class="card p-4 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Members</h3>
            <Button label="Add Member" icon="pi pi-plus" @click="emit('add')" />
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

                <Column header="Action" class="w-28 text-center">
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
    </div>
</template>
