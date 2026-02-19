<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Menu } from '@/types';
import { MenuItem } from 'primevue/menuitem';
import { AvailableRoute } from './type';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import MenuForm from './MenuForm.vue';
import moment from 'moment'
import DropdownButton from '@/components/DropdownButton.vue';
import Swal from 'sweetalert2';
import { FilterMatchMode } from '@primevue/core/api';

import 'sweetalert2/dist/sweetalert2.min.css';
import { can } from '@/lib/utils';

interface Props {
    menu?: Menu[];
    parent_menu?: Menu[];
    available_routes?: AvailableRoute[]
}

const props = defineProps<Props>()

const visible = ref<boolean>(false);
const selectedMenu = ref<Menu>();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const items: MenuItem[] = [];

if (can('menu.update')) {
    items.push({
        label: 'Edit',
        command(event) {
            selectedMenu.value = props.menu?.find((item) => item.uuid === event.item.menuKey)
            visible.value = true;
        },
    })
}

if (can('menu.delete')) {
    items.push({
        label: 'Delete',
        command(event) {
            destroy(event.item.data)
        },
    })
}

const destroy = (menu: Menu) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${menu.label} menu?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('menu.destroy', menu.uuid), {
                onSuccess() {
                    Swal.fire('Success', 'Success delete data', 'success')
                }
            })
        }
    });
};

watch(visible, (newValue: Boolean) => {
    if (!newValue) {
        selectedMenu.value = {
            uuid: '',
            label: '',
            icon: '',
            sequence_number: 1,
            is_active: false,
            created_at: '',
            updated_at: ''
        }
    }
})
</script>

<template>
    <Head title="Menu" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Menu" description="Manage all menu" />

            <div class="flex flex-col gap-4">
                <div class="flex justify-between gap-2">
                    <IconField>
                        <InputText v-model="filters.global.value" placeholder="Search" />
                        <InputIcon>
                            <Icon name="search" />
                        </InputIcon>
                    </IconField>

                    <Button label="Add Menu" raised @click="visible = true" :disabled="!can('menu.create')" >
                        <template #icon>
                            <Icon name="Plus" />
                        </template>
                    </Button>
                </div>

                <div class="card">
                    <DataTable :value="menu" v-model:filters="filters" data-key="uuid" paginator :rows="25"
                        :rowsPerPageOptions="[25, 50, 100]" :globalFilterFields="['label', 'parent', 'route']">

                        <Column field="label" header="Label" sortable></Column>
                        <Column field="parent" header="Parent" sortable></Column>

                        <Column field="icon" header="Icon">
                            <template #body="{ data }">
                                <Icon :name="data.icon" />
                            </template>
                        </Column>

                        <Column field="route" header="Route Name" sortable></Column>
                        <Column field="sequence_number" header="Sequence" sortable></Column>

                        <Column field="is_active" header="Status" sortable>
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'"
                                    :value="data.is_active ? 'Active' : 'Nonactive'" />
                            </template>
                        </Column>

                        <Column field="created_at" header="Created Date" sortable>
                            <template #body="{ data }">
                                {{ moment(data.created_at).format('DD MMM YYYY, HH:mm') }}
                            </template>
                        </Column>

                        <Column v-if="can('menu.update') || can('menu.delete')">
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
        </div>

        <MenuForm v-model:visible="visible" :value="selectedMenu" :available_routes="available_routes"
            :parent_menu="parent_menu" />
    </AppLayout>
</template>