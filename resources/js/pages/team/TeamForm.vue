<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2'
import Label from '@/components/ui/label/Label.vue';
import { watchDebounced } from '@vueuse/core';
import { FormDataConvertible } from '@inertiajs/core';
import { Team } from '@/types';

type FormDataType = Record<string, FormDataConvertible>;

interface Props {
    value?: Team;
    visible: boolean;
}

interface TeamForm {
    _method: string;
    string?: string;
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
    return props.value?.uuid ? 'Edit Team' : 'Create New Team'
})

const form: InertiaForm<TeamForm> = useForm({
    _method: 'POST',
    name: ''
});

const save = (): void => {
    const url = props.value?.uuid ? route('team.update', props.value.uuid) : route('team.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST'

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success')
            visible.value = false
        }
    })
}

const hide = (): void => {
    form.name = ''
}

const show = (): void => {
    form.name = props.value?.name
}

// watching form changes
for (const key in form.data()) {
    watchDebounced(() => form[key], () => {
        form.errors[key] = undefined
    }, {
        debounce: 500,
        maxWait: 1000
    })
}

</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[30vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <div class="grid gap-8">
            <div class="flex flex-col gap-2">
                <Label>Team Name</Label>
                <InputText v-model="form.name" placeholder="Team Name" />
                <InputError :message="form.errors.name" v-if="form.errors.name" />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save"/>
            </div>
        </template>
    </Drawer>
</template>