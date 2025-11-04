<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { ProjectRole } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import ProjectRoleForm from './Form.vue';

interface Props {
    project_roles?: ProjectRole[];
}

const props = withDefaults(defineProps<Props>(), {
    project_roles: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<ProjectRole>();

const goToCreate = () => {
    router.visit(route('site.create'));
};

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.project_roles?.find((item) => item.id === event.item.menuKey);
            visibleForm.value = true;
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
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${project_role.name} project role?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('ms_project_role.destroy', project_role.id), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
        }
    });
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Action Table -->
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button label="Add Project Role" raised @click="visibleForm = true">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="project_roles"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name']"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="name" header="Name" sortable></Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column>
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectRoleForm v-model:visible="visibleForm" :value="selected" />
</template>
