<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';

interface Member {
    id: number;
    hashid: string;
    user: { id: number; name: string; email: string };
    role: { id: number; name: string };
    is_active: boolean;
}

interface Paginated<T> {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface Props {
    project: { id: number; hashid: string; title: string; emoji?: string };
    members: Paginated<Member>;
    roles: { id: number; name: string }[];
    users: { id: number; name: string }[];
    filters?: { search?: string; role_id?: number | null; active?: string | null; per_page?: number };
}

interface AddMemberForm {
    _method: 'POST'
    user_id:  number | null
    project_role_id: number | null
    [key: string]: any
}

const props = defineProps<Props>();

// Filters
const filters = props.filters ?? {};
const search = ref(filters.search ?? '');
const roleId = ref<number | null>(filters.role_id ?? null);
const active = ref<string | null>(filters.active ?? null);
const perPage = ref(filters.per_page ?? 10);

// Pagination
const first = ref((props.members.current_page - 1) * props.members.per_page);
const onPage = (e: any) => applyQuery(e.page + 1);

// Debounce
let timer: number | undefined;
const debounce = (fn: Function, delay = 400) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(fn, delay);
};

// Apply filters
const applyQuery = (page = 1) => {
    router.get(
        route('project.members.show', props.project.hashid),
        {
            search: search.value,
            role_id: roleId.value,
            active: active.value,
            per_page: perPage.value,
            page,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

// Watch filters
watch([search, roleId, active, perPage], () => debounce(() => applyQuery(1)));

// ==== Add Member ====
const visibleAdd = ref(false);
const formAdd: InertiaForm<AddMemberForm> = useForm({ 
    _method: 'POST',
    user_id: null, 
    project_role_id: null 
});
const openAdd = () => {
    formAdd.reset();
    formAdd.clearErrors();
    visibleAdd.value = true;
};
const saveAdd = () => {
    if (!formAdd.user_id || !formAdd.project_role_id) {
        Swal.fire('Error', 'User and Role must be selected', 'error');
        return;
    }

    router.post(route('project.members.store', props.project.hashid), formAdd, {
        onSuccess: () => {
            Swal.fire('Success', 'Member added', 'success');
            visibleAdd.value = false;
            applyQuery();
        },
        onError: () => Swal.fire('Error', 'Please check the form', 'error'),
        preserveScroll: true,
    });
};

// ==== Edit Member ====
const visibleEdit = ref(false);
const editing = ref<Member | null>(null);
const formEdit = useForm({ project_role_id: null as number | null, is_active: true as boolean });

const openEdit = (m: Member) => {
    editing.value = m;
    formEdit.project_role_id = m.role?.id ?? null;
    formEdit.is_active = !!m.is_active;
    formEdit.clearErrors();
    visibleEdit.value = true;
};

const saveEdit = () => {
    if (!editing.value || !formEdit.project_role_id) return;

    router.put(route('project.members.update', { project: props.project.hashid, member: editing.value.hashid }), formEdit, {
        onSuccess: () => {
            Swal.fire('Success', 'Member updated', 'success');
            visibleEdit.value = false;
            applyQuery();
        },
        onError: () => Swal.fire('Error', 'Please check the form', 'error'),
        preserveScroll: true,
    });
};

// ==== Delete Member ====
const remove = (m: Member) => {
    Swal.fire({
        icon: 'warning',
        title: `Remove ${m.user.name}?`,
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(
                route('project.members.destroy', {
                    encoded: props.project.hashid, // <--- ini harus sesuai nama parameter di route
                    memberEncoded: m.hashid, // <--- ini juga sesuai
                }),
                {
                    onSuccess: () => Swal.fire('Deleted', 'Member removed', 'success'),
                    preserveScroll: true,
                },
            );
        }
    });
};

// Reset filters
const resetFilters = () => {
    search.value = '';
    roleId.value = null;
    active.value = null;
    perPage.value = 10;
    applyQuery();
};
</script>

<template>
    <Head :title="`Members - ${props.project.title}`" />
    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading :title="`Project Members`" :description="`Manage Member ${props.project.title}`" />

            <!-- Toolbar Filters -->
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="flex-1">
                    <label class="mb-1 block font-semibold">Search</label>
                    <InputText v-model="search" placeholder="Cari nama, email, atau role..." class="w-full" />
                </div>

                <div class="w-full md:w-56">
                    <label class="mb-1 block font-semibold">Role</label>
                    <Dropdown v-model="roleId" :options="props.roles" optionLabel="name" optionValue="id" placeholder="All roles" class="w-full" />
                </div>

                <div class="w-full md:w-40">
                    <label class="mb-1 block font-semibold">Status</label>
                    <Dropdown
                        v-model="active"
                        :options="[
                            { label: 'All', value: null },
                            { label: 'Active', value: '1' },
                            { label: 'Inactive', value: '0' },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full"
                    />
                </div>

                <div class="w-full md:w-36">
                    <label class="mb-1 block font-semibold">Rows</label>
                    <Dropdown v-model="perPage" :options="[10, 25, 50, 100]" placeholder="10" class="w-full" />
                </div>

                <div class="flex gap-2">
                    <Button label="Reset" severity="secondary" @click="resetFilters" />
                    <Button label="Add Member" icon="pi pi-user-plus" @click="openAdd" />
                </div>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <DataTable
                    :value="props.members.data"
                    data-key="hashid"
                    :totalRecords="props.members.total"
                    :rows="props.members.per_page"
                    :first="first"
                    paginator
                    lazy
                    @page="onPage"
                    striped-rows
                    row-hover
                >
                    <Column header="#" class="w-16 text-center">
                        <template #body="{ index }">
                            {{ (props.members.current_page - 1) * props.members.per_page + index + 1 }}
                        </template>
                    </Column>

                    <Column header="User">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ data.user?.name }}</span>
                                <span class="text-xs opacity-70">{{ data.user?.email }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Role">
                        <template #body="{ data }">
                            {{ data.role?.name || '-' }}
                        </template>
                    </Column>

                    <Column header="Status" class="w-28">
                        <template #body="{ data }">
                            <span
                                class="rounded px-2 py-1 text-xs"
                                :class="data.is_active ? 'bg-green-200 dark:bg-green-900/40' : 'bg-gray-200 dark:bg-gray-700'"
                            >
                                {{ data.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Action" class="w-40">
                        <template #body="{ data }">
                            <div class="flex gap-2">
                                <Button icon="pi pi-pencil" size="small" @click="openEdit(data)" />
                                <Button icon="pi pi-trash" size="small" severity="danger" @click="remove(data)" />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <p class="py-6 text-center">No members found</p>
                    </template>
                </DataTable>
            </div>

            <!-- Add Member Modal -->
            <Dialog header="Add Member" v-model:visible="visibleAdd" :modal="true" :closable="true" class="w-96">
                <div class="flex flex-col gap-4">
                    <Dropdown v-model="formAdd.user_id" :options="props.users" optionLabel="name" optionValue="id" placeholder="Select user" />
                    <Dropdown
                        v-model="formAdd.project_role_id"
                        :options="props.roles"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select role"
                    />
                    <Button label="Save" icon="pi pi-check" @click="saveAdd" />
                </div>
            </Dialog>

            <!-- Edit Member Modal -->
            <Dialog header="Edit Member" v-model:visible="visibleEdit" :modal="true" :closable="true" class="w-96">
                <div class="flex flex-col gap-4">
                    <Dropdown
                        v-model="formEdit.project_role_id"
                        :options="props.roles"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select role"
                    />
                    <Dropdown
                        v-model="formEdit.is_active"
                        :options="[
                            { label: 'Active', value: true },
                            { label: 'Inactive', value: false },
                        ]"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select status"
                    />
                    <Button label="Save" icon="pi pi-check" @click="saveEdit" />
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>
