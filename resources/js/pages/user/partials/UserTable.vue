<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { can } from '@/lib/utils';
import { UserList } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2';
import { ref } from 'vue';

interface Props {
    users: UserList[];
}

const props = withDefaults(defineProps<Props>(), {
    users: () => [],
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const items: MenuItem[] = [];

if (can('user.update')) {
    items.push({
        label: 'Edit',
        command(event) {
            router.visit(route('user.edit', event.item.menuKey));
        },
    });
}

if (can('user.delete')) {
    items.push({
        label: 'Delete',
        command(event) {
            destroy(event.item.data);
        },
    });
}

const destroy = (user: UserList) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete user ${user.name}?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('user.destroy', user.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success');
                },
            });
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

            <Link v-if="can('user.create')" :href="route('user.create')">
                <Button label="Add User" raised>
                    <template #icon>
                        <Icon name="plus" />
                    </template>
                </Button>
            </Link>

            <Button v-else label="Add User" raised disabled>
                <template #icon>
                    <Icon name="plus" />
                </template>
            </Button>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable
                :value="users"
                v-model:filters="filters"
                data-key="id"
                paginator
                :rows="25"
                :rowsPerPageOptions="[25, 50, 100]"
                :globalFilterFields="['name', 'team_name', 'email']"
                striped-rows
                row-hover
            >
                <Column header="No">
                    <template #body="{ index }">
                        {{ index + 1 }}
                    </template>
                </Column>

                <Column field="team_name" header="Team"></Column>
                <Column field="role_label" header="Role"></Column>
                <Column field="name" header="Name"></Column>
                <Column field="email" header="Email"></Column>

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

                <Column v-if="can('user.update') || can('users.delete')">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" :menu-key="data.uuid" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data</p>
                </template>
            </DataTable>
        </div>
    </div>
</template>
