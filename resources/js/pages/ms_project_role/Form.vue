<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2'
import Label from '@/components/ui/label/Label.vue';
import { watchDebounced } from '@vueuse/core';
import { ProjectRole } from '@/types';
import moment from 'moment';

interface Props {
    value?: ProjectRole;
    visible: boolean;
}

interface ProjectRoleForm {
    _method: "POST" | "PUT";
    name: string;
    [key: string]: any;
}

const props = defineProps<Props>()

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>()

const visible = computed<boolean>({
    get() {
        return props.visible
    },
    set(newValue) {
        emits('update:visible', newValue)
    }
});

const formHeader = computed(() => {
    return props.value?.id ? 'Edit Project Role' : 'Create New Project Role'
})

const form: InertiaForm<ProjectRoleForm> = useForm({
    _method: 'POST',
    name: '',
    severity: ''
});

const save = (): void => {
    const url = props.value?.id ? route('ms_project_role.update', props.value.id) : route('ms_project_role.store');

    form._method = props.value?.id ? 'PUT' : 'POST'

    form.post(url, {
            preserveScroll: true,
            onSuccess() {
                Swal.fire('Success', 'Successfully save data', 'success')
                visible.value = false
            }
        })
}

const hide = (): void => {
    form._method = 'POST'

}

const show = (): void => {
    form.name = props.value?.name ?? '';
}

// watching form changes
for (const key in form.data()) {
    watchDebounced(() => form[key], () => {
        delete form.errors[key]
    }, { debounce: 500, maxWait: 1000 })
}

</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show"
        @after-hide="hide">
        <form class="grid md:grid-cols-2 gap-8" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Project Name" />
                <InputError :message="form.errors.name" v-if="form.errors.name" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>