<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { ProjectPriority } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import ProjectPriorityForm from './Form.vue';

interface Props {
    project_priorities?: ProjectPriority[];
}

const props = withDefaults(defineProps<Props>(), {
    project_priorities: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<ProjectPriority | undefined>();

// --- Flash message global dari Laravel ---
const page = usePage();
watch(
    () => page.props.flash.success,
    (msg) => msg && Swal.fire('Success', msg as string, 'success'),
);
watch(
    () => page.props.flash.error,
    (msg) => msg && Swal.fire('Error', msg as string, 'error'),
);

// --- Action Items untuk Dropdown ---
const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.project_priorities?.find((item) => item.id === event.item.menuKey);
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

// --- Hapus data ---
const destroy = (project_priority: ProjectPriority) => {
    Swal.fire({
        icon: 'warning',
        title: `Delete "${project_priority.name}"?`,
        text: 'This action cannot be undone!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('project-priority.destroy', project_priority.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Project Priority deleted.', 'success'),
            });
        }
    });
};

// --- Reset form ketika drawer ditutup ---
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

            <Button label="Add Project Priority" raised @click="visibleForm = true">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- DataTable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="project_priorities"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name', 'severity']"
                striped-rows
                row-hover
            >
                <Column header="No" style="width: 80px; text-align: center">
                    <template #body="{ index }">{{ index + 1 }}</template>
                </Column>

                <Column field="name" header="Name" sortable></Column>
                <Column field="severity" header="Severity" sortable></Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Action" style="width: 100px; text-align: center">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-3 text-center">No data available.</p>
                </template>
            </DataTable>
        </div>
    </div>

    <!-- Drawer Form -->
    <ProjectPriorityForm v-model:visible="visibleForm" :value="selected" />
</template>
