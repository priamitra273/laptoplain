<script setup lang="ts">
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Label from '@/components/ui/label/Label.vue';
import { RoleForm, RoleFormProps } from './type';
import SetupPermission from './partials/SetupPermission.vue';
import Swal from 'sweetalert2'
import InputError from '@/components/InputError.vue';

const props = withDefaults(defineProps<RoleFormProps>(), {
    pageTitle: 'Add Role',
    total_menu: 0
});

const form: InertiaForm<RoleForm> = useForm({
    _method: 'POST',
    label: props.role?.label,
    team_uuid: props.role?.team_uuid,
    is_active: props.role?.is_active ?? false,
    permissions: props.role?.permissions ?? []
});

const save = () => {
    const url = props.role?.id ? route('role.update', props.role.id) : route('role.store');

    if (props.role?.id) form._method = 'PUT';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success')
        },
        onError(e) {
            console.log(e)
        }
    })
}

</script>

<template>

    <Head :title="pageTitle" />

    <AppLayout>
        <form class="flex flex-col gap-6" @submit.prevent="save">
            <Card>
                <template #content>
                    <Heading title="Role Information" description="Please fill the required fields." />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label>Team</Label>
                            <Select v-model="form.team_uuid" :options="props.teams" option-value="uuid"
                                option-label="name" class="w-full" placeholder="Select a team" filter />
                            <InputError :message="form.errors.team_uuid" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label>Role Name</Label>
                            <InputText v-model="form.label" class="w-full" />
                            <InputError :message="form.errors.label" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label class="py-2 flex justify-between items-center cursor-pointer">
                                <span>Active</span>
                                <ToggleSwitch v-model="form.is_active" />
                            </Label>
                        </div>
                    </div>
                </template>
            </Card>

            <SetupPermission 
                v-model="form.permissions"
                :menu="props.menu" 
                :menu-permissions="props.menu_permissions" 
                :total-menu="props.total_menu"/>

            <div class="flex gap-3 justify-end">
                <Button label="Back" severity="secondary" @click="router.visit(route('role.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" @click="save"/>
            </div>
        </form>
    </AppLayout>
</template>