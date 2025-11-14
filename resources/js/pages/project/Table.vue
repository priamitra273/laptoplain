<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
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

const toast = useToast();
const confirm = useConfirm();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Project | undefined>(undefined);

const goToCreate = () => {
    selected.value = undefined;
    visibleForm.value = true;
};

const stripHtml = (html: string | null): string => {
    if (!html) return '';
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
};

const items: MenuItem[] = [
    {
        label: 'View Detail',
        command(event) {
            const data = event.item.data;
            router.visit(route('project.show', { encoded: data.id }));
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
            confirmDelete(event.item.data);
        },
    },
];

const onCellEditComplete = ({ data, newValue, field }) => {
    if (data[field] === newValue) return;

    let payload: any = { ...data };
    if (field === 'start_date' || field === 'due_date') {
        payload[field] = moment(newValue).format('YYYY-MM-DD');
    } else {
        payload[field] = newValue;
    }

    payload.start_date = moment(payload.start_date).format('YYYY-MM-DD');
    payload.due_date = moment(payload.due_date).format('YYYY-MM-DD');

    router.put(route('project.update', data.encoded || data.id), payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Updated',
                detail: `${field} updated successfully.`,
                life: 2000,
            });
        },
    });
};

// Delete confirmation
const confirmDelete = (project: Project) => {
    confirm.require({
        message: `Are you sure you want to delete "${project.title}"?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Yes, Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('project.destroy', { project: project.id }), {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Deleted',
                        detail: 'Project deleted successfully.',
                        life: 3000,
                    });
                },
            });
        },
    });
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
                editMode="cell"
                @cell-edit-complete="onCellEditComplete"
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

                <!-- INLINE EDIT TITLE -->
                <Column field="title" header="Title" sortable>
                    <template #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>

                <!-- INLINE EDIT DESCRIPTION WITH QUILL -->
                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <div v-html="data.description"></div>
                    </template>

                    <template #editor="{ data, field }">
                        <Editor v-model="data[field]" editorStyle="height: 200px">
                            <template #toolbar>
                                <span class="ql-formats">
                                    <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                    <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                    <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                                </span>
                            </template>
                        </Editor>
                    </template>
                </Column>

                <!-- INLINE EDIT STATUS -->
                <Column field="status_id" header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status?.name" :severity="data.status?.severity" />
                    </template>
                    <template #editor="{ data }">
                        <Dropdown v-model="data.status_id" :options="props.statuses" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <!-- INLINE EDIT PRIORITY -->
                <Column field="priority_id" header="Priority">
                    <template #body="{ data }">
                        <Tag :value="data.priority?.name" :severity="data.priority?.severity" />
                    </template>
                    <template #editor="{ data }">
                        <Dropdown v-model="data.priority_id" :options="props.priorities" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <!-- INLINE EDIT START DATE -->
                <Column field="start_date" header="Start">
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>
                    <template #editor="{ data, field }">
                        <DatePicker v-model="data[field]" date-format="yy-mm-dd" />
                    </template>
                </Column>

                <!-- INLINE EDIT DUE DATE -->
                <Column field="due_date" header="Due">
                    <template #body="{ data }">
                        {{ moment(data.due_date).format('YYYY-MM-DD') }}
                    </template>
                    <template #editor="{ data, field }">
                        <DatePicker v-model="data[field]" date-format="yy-mm-dd" />
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
    <ConfirmDialog />
    <Toast />
</template>
