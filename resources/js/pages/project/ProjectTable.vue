<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import UploadDialog from '@/components/UploadDialog.vue';
import { Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import ProjectForm from './ProjectForm.vue';

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

const goToCreate = () => {
    router.visit(route('site.create'));
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
            selected.value = props.projects?.find((item) => item.uuid === event.item.menuKey);
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
        title: `Are you sure want to delete ${project.name} project?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('project.destroy', project.uuid), {
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

            <!-- <div class="flex gap-2">
                <Button label="Import" text raised @click="visibleImportDialog = true">
                    <template #icon>
                        <Icon name="Upload" />
                    </template>
                </Button>

                <Button as="a" :href="route('project.export')" label="Export" text raised>
                    <template #icon>
                        <Icon name="Download" />
                    </template>
                </Button>

                <Button label="Add Project" raised @click="visibleForm = true">
                    <template #icon>
                        <Icon name="Plus" />
                    </template>
                </Button>
            </div> -->
            <SplitButton
                class="p-button-raised"
                :model="splitButtonItems"
                @click="goToCreate"
                size="small"
            >
                <Icon name="Plus" />
                <span>Add Project</span>
            </SplitButton>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="projects"
                v-model:filters="filters"
                data-key="uuid"
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

                <Column field="start_date" header="Start Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column field="finish_date" header="Finish Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.finish_date).format('YYYY-MM-DD') }}
                    </template>
                </Column>

                <Column field="plan_site" header="Plan Site" sortable></Column>
                <Column field="plan_cctv" header="Plan CCTV" sortable></Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column>
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.uuid" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectForm v-model:visible="visibleForm" :value="selected" />
    <UploadDialog v-model:visible="visibleImportDialog" :verify-url="route('project.verify-import')" template-url="/templates/Template Import Project.xlsx" header="Import Project" />
</template>
