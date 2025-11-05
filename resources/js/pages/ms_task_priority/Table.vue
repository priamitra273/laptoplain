<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { TaskPriority } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import TaskPriorityForm from './Form.vue';

interface Props {
    task_priorities?: TaskPriority[];
}

const props = withDefaults(defineProps<Props>(), {
    task_priorities: () => [],
});

const filters = ref({
    global: { value: '', matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref(false);
const selected = ref<TaskPriority | null>(null);

const openCreate = () => {
    selected.value = null;
    visibleForm.value = true;
};

const openEdit = (taskPriority: TaskPriority) => {
    selected.value = taskPriority;
    visibleForm.value = true;
};

const destroy = (taskPriority: TaskPriority) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete "${taskPriority.name}"?`,
        text: 'This action cannot be undone!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('task-priority.destroy', taskPriority.id), {
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Task priority has been deleted.', 'success');
                },
            });
        }
    });
};

const items = [
    {
        label: 'Edit',
        command: (event: any) => openEdit(event.item.data),
    },
    {
        label: 'Delete',
        command: (event: any) => destroy(event.item.data),
    },
];

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = null;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- 🔹 Action Bar -->
        <div class="flex justify-between gap-2">
            <!-- Search -->
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <!-- Add Button -->
            <Button label="Add Task Priority" raised @click="openCreate">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- 🔹 Table -->
        <div class="card overflow-hidden">
            <DataTable
                :value="task_priorities"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name', 'severity']"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">{{ index + 1 }}</template>
                </Column>

                <Column field="name" header="Name" sortable />
                <Column field="severity" header="Severity" sortable />

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Action">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-4 text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>

    <!-- 🔹 Modal Form -->
    <TaskPriorityForm v-model:visible="visibleForm" :value="selected" />
</template>
