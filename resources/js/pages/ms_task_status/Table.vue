<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { getSeverityLabel } from '@/constants';
import { TaskStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import TaskStatusForm from './Form.vue';

interface Props {
    task_statuses?: TaskStatus[];
    hasPermission?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    task_statuses: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref(false);
const selected = ref<TaskStatus | undefined>(undefined);

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

const destroy = (task_status: TaskStatus) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${task_status.name}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
        },
        acceptProps: {
            label: 'Yes, Delete it',
            severity: 'danger',
        },
        accept: () => {
            router.delete(route('task-status.destroy', task_status.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task status', life: 3000 });
                },
            });
        },
    });
};

// Reset form saat Drawer ditutup
watch(visibleForm, (newVal) => {
    if (!newVal) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Toolbar -->
        <div class="flex justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search" />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button label="Add Task Status" raised @click="visibleForm = true" v-if="hasPermission">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- DataTable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="props.task_statuses"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                :globalFilterFields="['name', 'severity']"
                striped-rows
                row-hover
            >
                <Column header="No" :style="{ width: '50px' }">
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

                <Column field="score" header="Score" sortable>
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.score }}</span>
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

    <TaskStatusForm v-model:visible="visibleForm" :value="selected" />
</template>
