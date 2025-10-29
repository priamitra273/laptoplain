<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api';
import Icon from '@/components/Icon.vue';
import moment from 'moment';
import DropdownButton from '@/components/DropdownButton.vue';
import { RoleList, Team } from '@/types';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2'

interface Props {
    roles?: RoleList[]
}

const props = withDefaults(defineProps<Props>(), {
    roles: () => []
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const items: MenuItem[] = [
    {
        label: 'Edit',
        command(event) {
            router.visit(route('role.edit', event.item.menuKey))
        },
    },
    {
        label: 'Delete',
        command(event) {
            destroy(event.item.data)
        },
    }
];

const destroy = (role: RoleList) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${role.name} role?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('role.destroy', role.id), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success')
                }
            })
        }
    });
};

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

            <Button raised as-child v-slot="slotProps">
                <Link :href="route('role.create')" :class="slotProps.class">
                    <Icon name="plus" />
                    <span>Add Role</span>
                </Link>
            </Button>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable :value="roles" v-model:filters="filters" data-key="id" paginator :rows="25"
                :rowsPerPageOptions="[25, 50, 100]" :globalFilterFields="['name', 'team_name']" striped-rows row-hover>
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="team_name" header="Team"></Column>
                <Column field="label" header="Name"></Column>

                <Column field="is_active" header="Status">
                    <template #body="{ data }">
                        <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Active' : 'Nonactive'" />
                    </template>
                </Column>

                <Column field="created_at" header="Created Date" sortable>
                    <template #body="{ data }">
                        {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                    </template>
                </Column>

                <Column>
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.id" />
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
</template>