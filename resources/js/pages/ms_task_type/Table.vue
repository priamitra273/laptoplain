<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { getSeverityLabel } from '@/constants';
import { TaskType } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import TaskTypeForm from './Form.vue';

interface Props {
    task_types?: TaskType[];
    hasPermission?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    task_types: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref(false);
const selected = ref<TaskType | undefined>(undefined);

const confirm = useConfirm();
const toast = useToast();

const items: MenuItem[] = [
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

const destroy = (task_type: TaskType) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${task_type.name}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
        },
        acceptProps: {
            label: 'Delete',
            severity: 'danger',
        },
        accept: () => {
            router.delete(route('task-type.destroy', task_type.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task type', life: 3000 });
                },
            });
        },
    });
};

// Reset state setelah Drawer ditutup
watch(visibleForm, (newVal) => {
    if (!newVal) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Toolbar -->
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search..." />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button label="Add Task Type" raised @click="visibleForm = true" v-if="hasPermission">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- DataTable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="props.task_types"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                :globalFilterFields="['name', 'severity']"
                striped-rows
                row-hover
            >
                <Column header="No" :style="{ width: '60px' }">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="name" header="Name" sortable />
                <Column field="severity" header="Severity" sortable>
                    <template #body="{ data }">
                        <Tag :severity="data.severity" :value="getSeverityLabel(data.severity)"></Tag>
                    </template>
                </Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Actions" v-if="hasPermission">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" />
                    </template>
                </Column>

                <template #empty>
                    <p class="py-4 text-center">No Data Found</p>
                </template>
            </DataTable>
        </div>
    </div>

    <TaskTypeForm v-model:visible="visibleForm" :value="selected" />
</template>
