<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2'
import Label from '@/components/ui/label/Label.vue';
import { watchDebounced } from '@vueuse/core';
import { DeviceExtended } from './type';

interface Props {
    value?: DeviceExtended;
    visible: boolean;
}

interface DeviceForm {
    _method: "POST" | "PUT";
    mac_address: string|null;
    ip_dhcp: string|null;
    ip_static: string|null;
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
    return props.value?.uuid ? 'Edit Device' : 'Add New Device'
})

const form: InertiaForm<DeviceForm> = useForm({
    _method: 'POST',
    mac_address: null,
    ip_dhcp: null,
    ip_static: null
});

const save = (): void => {
    const url = props.value?.uuid ? route('device.update', props.value.uuid) : route('device.store');

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
    form._method = 'POST'
}

const show = (): void => {
    form.mac_address = props.value?.mac_address ?? null;
    form.ip_dhcp = props.value?.ip_dhcp ?? null;
    form.ip_static = props.value?.ip_static ?? null;
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
        <form class="grid md:grid-cols-2 gap-6" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <Label for="mac_address">Mac Address</Label>
                <InputText v-model="form.mac_address" id="mac_address" placeholder="Enter Mac Address" />
                <InputError :message="form.errors.mac_address" v-if="form.errors.mac_address" />
            </div>
            
            <div class="flex flex-col gap-2">
                <Label for="ip_dhcp">IP DHCP</Label>
                <InputText v-model="form.ip_dhcp" id="ip_dhcp" placeholder="Enter IP DHCP" />
                <InputError :message="form.errors.ip_dhcp" v-if="form.errors.ip_dhcp" />
            </div>
            
            <div class="flex flex-col gap-2">
                <Label for="ip_static">IP Static</Label>
                <InputText v-model="form.ip_static" id="ip_static" placeholder="Enter IP Static" />
                <InputError :message="form.errors.ip_static" v-if="form.errors.ip_static" />
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
