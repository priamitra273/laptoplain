<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { getSeverityLabel } from '@/constants';
import { TaskPriority } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import TaskPriorityForm from './Form.vue';

interface Props {
    task_priorities?: TaskPriority[];
}

const props = withDefaults(defineProps<Props>(), {
    task_priorities: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<TaskPriority | undefined>(undefined);

const confirm = useConfirm();
const toast = useToast();

const items = [
    {
        label: 'Edit',
        command(event: any) {
            const id = event.item.menuKey;
            selected.value = props.task_priorities.find((i) => i.id === id);
            visibleForm.value = true;
        },
    },
    {
        label: 'Delete',
        command(event: any) {
            destroy(event.item.data);
        },
    },
];

const destroy = (taskPriority: TaskPriority) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${taskPriority.name}"?`,
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
            router.delete(route('task-priority.destroy', taskPriority.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task priority', life: 3000 });
                },
            });
        },
    });
};

watch(visibleForm, (newValue) => {
    if (!newValue) selected.value = undefined;
});
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

            <Button label="Add Task Priority" raised @click="visibleForm = true">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

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
                <Column header="No" style="width: 5%">
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

                <Column header="Action" style="width: 10%">
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

    <TaskPriorityForm v-model:visible="visibleForm" :value="selected" />
</template>
