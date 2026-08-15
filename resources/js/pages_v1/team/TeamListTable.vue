<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3'
import { FilterMatchMode } from '@primevue/core/api';
import Icon from '@/components/Icon.vue';
import moment from 'moment';
import TeamForm from './TeamForm.vue';
import DropdownButton from '@/components/DropdownButton.vue';
import { Team } from '@/types';
import { MenuItem } from 'primevue/menuitem';
import Swal from 'sweetalert2'
import { can } from '@/lib/utils';

interface Props {
    teams?: Team[]
}

const props = withDefaults(defineProps<Props>(), {
    teams: () => []
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false)
const selected = ref<Team>()

const items: MenuItem[] = [];

if (can('team.update')) {
    items.push({
        label: 'Edit',
        command(event) {
            selected.value = props.teams?.find((item) => item.uuid === event.item.menuKey)
            visibleForm.value = true;
        },
    })
}

if (can('team.delete')) {
    items.push({
        label: 'Delete',
        command(event) {
            destroy(event.item.data)
        },
    })
}

const destroy = (team: Team) => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure want to delete ${team.name} team?`,
        text: 'This action cannot be undone, so please proceed with caution!',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: `Cancel`,
        customClass: {
            confirmButton: '!bg-red-500 focus:!ring focus:!ring-red-300'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            router.delete(route('team.destroy', team.uuid), {
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

            <Button label="Add Team" raised @click="visibleForm = true" :disabled="!can('team.create')">
                <template #icon>
                    <Icon name="Plus" />
                </template>
            </Button>
        </div>

        <!-- Datatable -->
        <div class="card overflow-hidden">
            <DataTable :value="teams" v-model:filters="filters" data-key="uuid" paginator :rows="25"
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

                <Column v-if="can('team.update') || can('team.delete')">
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

    <TeamForm v-model:visible="visibleForm" :value="selected" />
</template>