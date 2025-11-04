<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import ProjectForm from './Form.vue';

interface Props {
    projects?: Project[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const visibleImportDialog = ref<boolean>(false);
const selected = ref<Project>();

// Mapping label & warna status / priority
const statusLabels: Record<number, { label: string; color: string }> = {
    1: { label: 'Pending', color: 'bg-yellow-100 text-yellow-700' },
    2: { label: 'In Progress', color: 'bg-blue-100 text-blue-700' },
    3: { label: 'Completed', color: 'bg-green-100 text-green-700' },
};

const priorityLabels: Record<number, { label: string; color: string }> = {
    1: { label: 'Low', color: 'bg-green-100 text-green-700' },
    2: { label: 'Medium', color: 'bg-yellow-100 text-yellow-700' },
    3: { label: 'High', color: 'bg-red-100 text-red-700' },
};

const goToCreate = () => {
    visibleForm.value = true;
};

const splitButtonItems: MenuItem[] = [
    {
        label: 'Import',
        icon: 'pi pi-upload',
        command: () => {
            visibleImportDialog.value = true;
        },
    },
    {
        label: 'Export',
        icon: 'pi pi-download',
        command: () => {
            window.open(route('project.export'), '_blank');
        },
    },
];

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.projects?.find((item) => item.id === event.item.menuKey);
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

const destroy = (project: Project) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete "${project.title}"?`,
        text: 'This action cannot be undone!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('project.destroy', project.id), {
                onSuccess() {
                    Swal.fire('Deleted!', 'Project deleted successfully.', 'success');
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
        <!-- Toolbar -->
        <div class="flex items-center justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search Project..." />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <SplitButton class="p-button-raised" :model="splitButtonItems" @click="goToCreate" size="small">
                <Icon name="Plus" />
                <span>Add Project</span>
            </SplitButton>
        </div>

        <!-- Data Table -->
        <div class="card overflow-hidden">
            <DataTable
                :value="projects"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['title', 'description']"
                striped-rows
                row-hover
            >
                <Column header="No" class="w-12 text-center">
                    <template #body="{ index }">{{ index + 1 }}</template>
                </Column>

                <Column field="emoji" header="Emoji" class="w-20 text-center">
                    <template #body="{ data }">{{ data.emoji || '-' }}</template>
                </Column>

                <Column field="title" header="Title" sortable></Column>

                <Column field="description" header="Description" sortable>
                    <template #body="{ data }">
                        {{ data.description || '-' }}
                    </template>
                </Column>

                <Column field="status_id" header="Status" sortable>
                    <template #body="{ data }">
                        <span
                            v-if="statusLabels[data.status_id]"
                            :class="['rounded-full px-2 py-1 text-xs font-medium', statusLabels[data.status_id].color]"
                        >
                            {{ statusLabels[data.status_id].label }}
                        </span>
                        <span v-else>-</span>
                    </template>
                </Column>

                <Column field="priority_id" header="Priority" sortable>
                    <template #body="{ data }">
                        <span
                            v-if="priorityLabels[data.priority_id]"
                            :class="['rounded-full px-2 py-1 text-xs font-medium', priorityLabels[data.priority_id].color]"
                        >
                            {{ priorityLabels[data.priority_id].label }}
                        </span>
                        <span v-else>-</span>
                    </template>
                </Column>

                <Column field="start_date" header="Start Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column field="due_date" header="Due Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.due_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column field="progress" header="Progress" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-full rounded bg-gray-200">
                                <div class="h-2 rounded bg-blue-500" :style="{ width: data.progress + '%' }"></div>
                            </div>
                            <span class="text-xs text-gray-600">{{ data.progress }}%</span>
                        </div>
                    </template>
                </Column>

                <Column field="created_at" header="Created" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Action" class="w-16 text-center">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-4 text-center text-gray-500">No Project Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectForm v-model:visible="visibleForm" :value="selected" />
</template>
