<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api';
import Icon from '@/components/Icon.vue';
import moment from 'moment';
import DropdownButton from '@/components/DropdownButton.vue';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2'
import { Department } from '@/types';
import DepartmentForm from './DepartmentForm.vue';

interface Props {
    departments?: Department[]
}

const props = withDefaults(defineProps<Props>(), {
    departments: () => []
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false)
const selected = ref<Department>()

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            selected.value = props.departments?.find((item) => item.uuid === event.item.menuKey)
            visibleForm.value = true;
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data)
        },
    }
];

const destroy = (department: Department) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${department.name} department?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('department.destroy', department.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success')
                }
            })
        }
    });
};

watch(visibleForm, (newValue) => {
    if (!newValue) {
        selected.value = undefined
    }
})

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

            <Button label="Add Department" raised @click="visibleForm = true">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable :value="departments" v-model:filters="filters" data-key="uuid" paginator :rows="25"
                :rowsPerPageOptions="[25, 50, 100]" :globalFilterFields="['name']" striped-rows
                row-hover>
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="name" header="Name"></Column>

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
                    <p class="text-center">
                        No Data
                    </p>
                </template>
            </DataTable>
        </div>
    </div>

    <DepartmentForm v-model:visible="visibleForm" :value="selected" />
</template>