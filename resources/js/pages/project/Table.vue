<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Tag from 'primevue/tag';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import ProjectForm from './Form.vue';

interface Props {
    projects?: Project[];
    statuses: { id: number; name: string }[];
    priorities: { id: number; name: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Project | undefined>(undefined);

const goToCreate = () => {
    selected.value = undefined;
    visibleForm.value = true;
};

const items: MenuItem[] = [
    {
        label: 'View Detail',
        command(event) {
            router.get(route('project.show', event.item.data.id)); // ✅ FIX
        },
    },
    {
        label: 'Edit',
        command(event) {
            selected.value = event.item.data;
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
        title: `Delete "${project.title}"?`,
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('project.destroy', project.id), {
                // ✅ FIX
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Project deleted successfully.', 'success');
                },
            });
        }
    });
};

const stripHtml = (html: string | null): string => {
    if (!html) return '';
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
};

watch(visibleForm, (val) => {
    if (!val) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Search + Add -->
        <div class="flex items-center justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search Project..." />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button icon="pi pi-plus" label="Add Project" @click="goToCreate" />
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <DataTable
                :value="projects"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                :globalFilterFields="['title', 'description']"
                striped-rows
                row-hover
            >
                <Column header="No" class="w-12 text-center">
                    <template #body="{ index }">{{ index + 1 }}</template>
                </Column>

                <Column field="title" header="Title" sortable />

                <Column field="description" header="Description" sortable>
                    <template #body="{ data }">
                        {{ stripHtml(data.description) || '-' }}
                    </template>
                </Column>

                <Column header="Status" sortable>
                    <template #body="{ data }">
                        <Tag :value="data.status?.name" :severity="data.status?.severity" class="px-3 py-1 text-sm" />
                    </template>
                </Column>

                <Column header="Priority" sortable>
                    <template #body="{ data }">
                        <Tag :value="data.priority?.name" :severity="data.priority?.severity" class="px-3 py-1 text-sm" />
                    </template>
                </Column>

                <Column field="start_date" header="Start" sortable>
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column field="due_date" header="Due" sortable>
                    <template #body="{ data }">
                        {{ moment(data.due_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column header="Action">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectForm v-model:visible="visibleForm" :value="selected" :statuses="props.statuses" :priorities="props.priorities" />
</template>
