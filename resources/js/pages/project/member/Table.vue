<script setup lang="ts">
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';
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

const remove = (m: ProjectMember) => {
    Swal.fire({
        icon: 'warning',
        title: `Remove ${m.user.name}?`,
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(
                route('project.members.destroy', {
                    projectEncoded: props.projectId,
                    memberEncoded: m.id,
                }),
                {
                    onSuccess: () => Swal.fire('Deleted', 'Member removed', 'success'),
                    preserveScroll: true,
                },
            );
        }
    });
};
</script>

<template>
    <div class="card p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Members</h3>
            <Button label="Add Member" icon="pi pi-plus" @click="emit('add')" />
        </div>

        <DataTable :value="props.members" tableStyle="min-width: 50rem">
            <Column header="#" class="w-16 text-center">
                <template #body="{ index }">
                    {{ index + 1 }}
                </template>
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
                <template #body="{ data }">
                    {{ data.role?.name || '-' }}
                </template>
            </Column>

            <Column header="Status" class="w-28">
                <template #body="{ data }">
                    <span
                        class="rounded px-2 py-1 text-xs"
                        :class="data.is_active ? 'bg-green-200 dark:bg-green-900/40' : 'bg-gray-200 dark:bg-gray-700'"
                    >
                        {{ data.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </template>
            </Column>

            <Column header="Action" class="w-40">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button icon="pi pi-pencil" size="small" @click="emit('edit', data)" />
                        <Button icon="pi pi-trash" size="small" severity="danger" @click="remove(data)" />
                    </div>
                </template>
            </Column>

            <template #empty>
                <p class="py-6 text-center">No members found</p>
            </template>
        </DataTable>
    </div>
</template>
