<script setup lang="ts">
import { ref, watch } from 'vue';
import { InertiaForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Label from '@/components/ui/label/Label.vue';
import { Form, Stream } from '../type';
import axios, { AxiosError } from 'axios';
import InputError from '@/components/InputError.vue';

interface Props {
    stream: Stream;
    form: InertiaForm<Form>;
}

const checking = ref<boolean|null>(null);
const isOnline = ref<boolean>(false);

const props = defineProps<Props>();

const errorMsg = ref()

watch(() => props.form.ip_static, async (value) => {
    checking.value = true;
    errorMsg.value = '';
    
    try {
        const result = await axios.post(route('stream.health'), {
            ip_static: value
        });
        
        isOnline.value = result.data.results.ip_static;
    } catch (error: any | Error | AxiosError) {
        errorMsg.value = error.response.data.message
    }
    
    checking.value = false;
})
</script>

<template>
    <Card>
        <template #content>
            <Heading title="Device Information" description="Please fill the required fileds." />

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <Label>Mac Address</Label>
                    <InputText :value="stream.mac_address" fluid disabled />
                </div>

                <div>
                    <Label>IP DHCP</Label>
                    <InputText :value="stream.ip_dhcp" fluid disabled />
                </div>

                <div>
                    <Label for="ip_static">IP Static</Label>

                    <IconField>
                        <InputText v-model="form.ip_static" id="ip_static" fluid />
                        <InputIcon class="pi pi-spin pi-spinner" v-if="checking !== null && checking"/>

                        <template v-if="checking !== null">
                            <InputIcon class="pi pi-check !text-green-600" v-if="!checking && isOnline" v-tooltip.bottom="'Online'" />
                            <InputIcon class="pi pi-times !text-red-600" v-if="!checking && !isOnline" v-tooltip.bottom="'Offline'"/>
                        </template>
                    </IconField>

                    <InputError :message="errorMsg ?? form.errors.ip_static" class="mt-1" />
                </div>
            </div>
        </template>
    </Card>
</template>
