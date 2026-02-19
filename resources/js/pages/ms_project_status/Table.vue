<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { can } from '@/lib/utils';
import { getSeverityLabel } from '@/constants';
import { MsProjectStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import FormProjectStatus from './Form.vue';

interface Props {
    statuses?: MsProjectStatus[];
}

const props = withDefaults(defineProps<Props>(), {
    statuses: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<MsProjectStatus | undefined>(undefined);

const confirm = useConfirm();
const toast = useToast();

const items = [];

if (can('project-status.update')) {
    items.push({
        label: 'Edit',
        command(event: any) {
            const id = event.item.menuKey;
            selected.value = props.statuses.find((i) => i.id === id);
            visibleForm.value = true;
        },
    })
}

if (can('project-status.delete')) {
    items.push({
        label: 'Delete',
        command(event: any) {
            destroy(event.item.data);
        },
    })
}

const destroy = (status: MsProjectStatus) => {
    confirm.require({
        message: 'This action cannot be undone!',
        header: `Are you sure want to delete "${status.name}"?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
        },
        acceptProps: {
            label: 'Yes, Delete',
            severity: 'danger',
        },
        accept: () => {
            router.delete(route('project-status.destroy', status.id), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete project status', life: 3000 });
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

            <Button label="Add Project Status" raised @click="visibleForm = true" :disabled="!can('project-status.create')">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <div class="card overflow-hidden">
            <DataTable
                :value="props.statuses"
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

                <Column header="Actions" style="width: 10%" v-if="can('project-status.update') || can('project-status.delete')">
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

    <FormProjectStatus v-model:visible="visibleForm" :value="selected" />
</template>
