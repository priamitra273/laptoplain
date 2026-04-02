<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { getSeverityLabel } from '@/constants';
import { can } from '@/lib/utils';
import { TaskCategory } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import ProjectPriorityForm from './Form.vue';

interface Props {
    task_categories?: TaskCategory[];
}

const props = withDefaults(defineProps<Props>(), {
    task_categories: () => [],
});

const confirm = useConfirm();
const toast = useToast();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<TaskCategory | undefined>(undefined);

const items: MenuItem[] = [];

if (can('task-category.update')) {
    items.push({
        label: 'Edit',
        command(event) {
            const id = event.item.menuKey;
            selected.value = props.task_categories.find((item) => item.id === id);
            visibleForm.value = true;
        },
    })
}

if (can('task-category.delete')) {
    items.push({
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    })
}

const destroy = (project_priority: TaskCategory) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${project_priority.name}"?`,
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
            router.delete(route('task-category.destroy', project_priority.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task category', life: 3000 });
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

            <Button label="Add Task Category" raised @click="visibleForm = true" :disabled="!can('task-category.create')">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <div class="card overflow-hidden">
            <DataTable
                :value="task_categories"
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

                <Column field="name" header="Name" sortable></Column>
                <Column field="icon" header="Icon" sortable>
                    <template #body="{ data }">
                        <Tag :severity="data.severity">
                            <i :class="data.icon" class="text-lg px-1" />
                        </Tag>
                    </template>
                </Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column header="Actions" style="width: 10%" v-if="can('task-category.update') || can('task-category.delete')">
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

    <ProjectPriorityForm v-model:visible="visibleForm" :value="selected" />
</template>
