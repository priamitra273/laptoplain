<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { ProjectRole } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

interface Props {
    project_roles?: ProjectRole[];
    hasPermission?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    project_roles: () => [],
});

const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const confirm = useConfirm();
const toast = useToast();

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            router.visit(route('project-role.edit', event.item.menuKey));
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    },
];

const destroy = (project_role: ProjectRole) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${project_role.name}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
        },
        acceptProps: {
            label: 'Yes, Delete',
            severity: 'danger',
        },
        accept: () => {
            router.delete(route('project-role.destroy', project_role.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete project role', life: 3000 });
                },
            });
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button label="Add Project Role" raised @click="router.visit(route('project-role.create'))" v-if="hasPermission">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <div class="card overflow-hidden">
            <DataTable
                :value="project_roles"
                v-model:first="first"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name']"
                striped-rows
                row-hover
            >
                <Column header="No" style="width: 5%">
                    <template #body="{ index }">
                        {{ first + index + 1 }}
                    </template>
                </Column>

                <Column field="name" header="Name" sortable></Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Actions" style="width: 10%" v-if="hasPermission">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-4 text-center">No Data Available</p>
                </template>
            </DataTable>
        </div>
    </div>
</template>
